<?php

namespace App\Services\Projects;

use App\Helpers\ActivityStatus;
use App\Helpers\ActivitySize;
use App\Helpers\BusinessDays;
use App\Helpers\DateMath;
use App\Helpers\Delay;
use App\Helpers\Progress;
use App\Helpers\SprintClose;
use App\Helpers\SprintPace;
use App\Helpers\SprintProgress;
use App\Helpers\SprintScope;
use App\Models\Sprint;
use App\Models\SprintActivity;
use App\Models\SprintCheckin;
use App\Models\SprintClosure;

/**
 * Everything the sprint detail screen needs to review how the sprint is
 * going at any moment, and the weekly check-ins that leave a record of it.
 */
class SprintReviewService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly SprintActivity $membership = new SprintActivity(),
        private readonly SprintCheckin $checkinModel = new SprintCheckin(),
        private readonly SprintClosure $closureModel = new SprintClosure(),
    ) {
    }

    /** Null when the sprint doesn't exist. */
    public function review(int $sprintId, ?string $today = null): ?array
    {
        $today ??= date('Y-m-d');
        $sprint = $this->sprintModel->find($sprintId);
        if ($sprint === null) {
            return null;
        }

        $open = SprintClose::isEditable($sprint);
        $rows = $this->withStatus($this->membership->forSprint($sprintId), $sprint, $today);
        // Open: what is in the sprint now. Closed: everything it held at the close, with its outcome.
        $shown = array_values(array_filter($rows, fn (array $a) => $open ? SprintScope::isActive($a) : empty($a['removed_at'])));
        // Cancelled activities don't count for progress, counters or alerts.
        $activities = array_values(array_filter($shown, fn (array $a) => $a['status_code'] !== Progress::CANCELLED));

        $byBacklog = [];
        foreach ($shown as $a) {
            $byBacklog[(int) $a['backlog_item_id']][] = $a;
        }
        $items = [];
        foreach ($this->sprintModel->backlogs($sprintId) as $item) {
            if (!isset($byBacklog[(int) $item['id']])) {
                continue;
            }
            // Only the backlog's activities that are in the sprint count here.
            $item['activities'] = $byBacklog[(int) $item['id']];
            $counted = array_values(array_filter($item['activities'], fn (array $a) => $a['status_code'] !== Progress::CANCELLED));
            $item['progress_percent'] = round(Progress::byHours($counted), 1);
            $item['hours_total'] = array_sum(array_map([Progress::class, 'hoursOf'], $counted));
            $item['activity_count'] = count($counted);
            $item['counts'] = SprintProgress::statusCounts($counted);
            $item['unestimated'] = ActivitySize::countUnestimated($counted);
            $items[] = $item;
        }

        $progress = SprintProgress::completionPercent($items);
        $elapsed = SprintPace::elapsedPercent($sprint['start_date'], $sprint['end_date'], $today);

        $projects = SprintProgress::byProject($items);

        // A project chosen for the sprint but without backlogs yet still shows up.
        $listed = array_column($projects, 'project_id');
        foreach ($this->sprintModel->projects($sprintId) as $p) {
            if (!in_array((int) $p['id'], $listed, true)) {
                $projects[] = ['project_id' => (int) $p['id'], 'project_name' => $p['name'], 'backlog_count' => 0, 'activity_count' => 0, 'hours_total' => 0.0, 'progress_percent' => 0.0];
            }
        }
        usort($projects, fn (array $a, array $b) => strcmp($a['project_name'], $b['project_name']));

        foreach ($projects as &$project) {
            $project['items'] = array_values(array_filter($items, fn (array $i) => (int) $i['project_id'] === $project['project_id']));
            $project['counts'] = SprintProgress::statusCounts(array_merge([], ...array_column($project['items'], 'activities')));
        }
        unset($project);

        $scope = SprintScope::summary($rows);

        return [
            'sprint' => $sprint,
            'projects' => $projects,
            // Two separate indicators: avance (hours-weighted progress) and
            // cumplimiento (hours of the committed activities terminadas / hours committed at the start).
            'progress' => $open ? $progress : (float) ($sprint['completion_percent'] ?? $progress),
            'compliance' => $open || $sprint['compliance_percent'] === null ? $scope['compliance_percent'] : (float) $sprint['compliance_percent'],
            'scope' => $scope,
            'is_open' => $open,
            'overdue_open' => SprintClose::isOverdueOpen($sprint, $today),
            'due_after_end' => array_values(array_filter($activities, fn (array $a) => $a['due_after_end'])),
            'removed' => array_values(array_filter($rows, fn (array $a) => !empty($a['removed_at']))),
            'closures' => $this->closureModel->forSprint($sprintId),
            'business_days' => BusinessDays::count($sprint['start_date'], $sprint['end_date']),
            // A closed sprint no longer raises alerts: what was pending already left it.
            'delay' => Delay::fromActivities($open ? $activities : [], $today),
            'unestimated' => ActivitySize::countUnestimated($activities),
            'elapsed_percent' => $elapsed,
            'health' => SprintPace::health($elapsed, $progress),
            'days_left' => max(0, DateMath::daysBetween($today, $sprint['end_date'])),
            'current_week' => $today < $sprint['start_date'] ? 0 : SprintPace::weekOf($sprint['start_date'], (int) $sprint['duration_weeks'], $today),
            'counts' => SprintProgress::statusCounts($activities),
            'activity_total' => count($activities),
            'alerts' => $open ? $this->alerts($activities, $today) : [],
            'weeks' => $this->weeksWithCheckins($sprint, $sprintId, $today),
            'checkin_count' => count($this->checkinModel->forSprint($sprintId)),
        ];
    }

    /**
     * Saves a weekly check-in with a snapshot of the sprint taken right now.
     *
     * @param array{checkin_date:string,week_number:?int,registered_by:?int,summary:string,blockers:?string,next_steps:?string} $data
     */
    public function addCheckin(int $sprintId, array $data, ?string $today = null): ?int
    {
        $review = $this->review($sprintId, $today);
        if ($review === null || !$review['is_open']) {
            return null;
        }

        $sprint = $review['sprint'];

        return $this->checkinModel->insert([
            'sprint_id' => $sprintId,
            'checkin_date' => $data['checkin_date'],
            'week_number' => $this->weekFor($sprint, $data),
            'registered_by' => $data['registered_by'],
            'progress_percent' => $review['progress'],
            'activities_total' => $review['activity_total'],
            'activities_done' => $review['counts']['completed'],
            'activities_overdue' => $review['counts']['overdue'],
            'summary' => $data['summary'],
            'blockers' => $data['blockers'],
            'next_steps' => $data['next_steps'],
        ]);
    }

    /**
     * Edits what was written in a check-in (date, week, who, notes). The
     * progress snapshot is left alone: it is the sprint as it was that day.
     *
     * @param array{checkin_date:string,week_number:?int,registered_by:?int,summary:string,blockers:?string,next_steps:?string} $data
     * @return int|null the sprint id, or null when the check-in doesn't exist
     */
    public function updateCheckin(int $checkinId, array $data): ?int
    {
        $checkin = $this->checkinModel->find($checkinId);
        $sprint = $checkin === null ? null : $this->sprintModel->find((int) $checkin['sprint_id']);
        if ($checkin === null || $sprint === null || !SprintClose::isEditable($sprint)) {
            return null;
        }

        $this->checkinModel->update($checkinId, [
            'checkin_date' => $data['checkin_date'],
            'week_number' => $this->weekFor($sprint, $data),
            'registered_by' => $data['registered_by'],
            'summary' => $data['summary'],
            'blockers' => $data['blockers'],
            'next_steps' => $data['next_steps'],
        ]);

        return (int) $sprint['id'];
    }

    /** The week the user picked, or — when missing or out of the sprint — the one the date falls in. */
    private function weekFor(array $sprint, array $data): int
    {
        $weeks = (int) $sprint['duration_weeks'];
        $picked = (int) ($data['week_number'] ?? 0);

        return $picked >= 1 && $picked <= $weeks
            ? $picked
            : SprintPace::weekOf($sprint['start_date'], $weeks, $data['checkin_date']);
    }

    /** The sprint's activities with their system status and the "vence después del fin del sprint" flag. */
    private function withStatus(array $rows, array $sprint, string $today): array
    {
        return array_map(function (array $a) use ($sprint, $today) {
            $blocked = $a['depends_on_activity_id'] !== null && (int) ($a['depends_on_progress'] ?? 0) < 100;
            $a['system_status'] = ActivityStatus::compute((int) $a['progress_percent'], $a['due_date'], $blocked, $today);
            $a['due_soon'] = ActivityStatus::isDueSoon((int) $a['progress_percent'], $a['due_date'], 5, $today);
            $a['due_after_end'] = SprintClose::dueAfterEnd($a['due_date'], $sprint['end_date'], $a['status_code']);

            return $a;
        }, $rows);
    }

    /** Activities that need attention: overdue, blocked or due within 5 days. */
    private function alerts(array $activities, string $today): array
    {
        $alerts = array_values(array_filter(
            $activities,
            fn (array $a) => in_array($a['system_status'], [ActivityStatus::OVERDUE, ActivityStatus::BLOCKED], true) || $a['due_soon']
        ));

        $severity = fn (array $a) => match (true) {
            $a['system_status'] === ActivityStatus::OVERDUE => 0,
            $a['system_status'] === ActivityStatus::BLOCKED => 1,
            default => 2,
        };
        usort($alerts, fn (array $a, array $b) => [$severity($a), (string) $a['due_date']] <=> [$severity($b), (string) $b['due_date']]);

        return $alerts;
    }

    /** One entry per sprint week with the check-ins registered in it. */
    private function weeksWithCheckins(array $sprint, int $sprintId, string $today): array
    {
        $checkins = $this->checkinModel->forSprint($sprintId);
        $weeks = SprintPace::weeks($sprint['start_date'], (int) $sprint['duration_weeks']);

        foreach ($weeks as &$week) {
            $week['checkins'] = array_values(array_filter($checkins, fn (array $c) => (int) $c['week_number'] === $week['number']));
            $week['is_current'] = $today >= $week['start'] && $today <= $week['end'];
            $week['started'] = $today >= $week['start'];
        }

        return $weeks;
    }
}
