<?php

namespace App\Services\Projects;

use App\Helpers\ActivityStatus;
use App\Helpers\DateMath;
use App\Helpers\SprintPace;
use App\Helpers\SprintProgress;
use App\Models\Activity;
use App\Models\Sprint;
use App\Models\SprintCheckin;

/**
 * Everything the sprint detail screen needs to review how the sprint is
 * going at any moment, and the weekly check-ins that leave a record of it.
 */
class SprintReviewService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly Activity $activityModel = new Activity(),
        private readonly SprintCheckin $checkinModel = new SprintCheckin(),
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

        $items = $this->sprintModel->backlogs($sprintId);
        $activities = $this->activitiesWithStatus(array_column($items, 'id'), $today);

        $byBacklog = [];
        foreach ($activities as $a) {
            $byBacklog[(int) $a['backlog_item_id']][] = $a;
        }
        foreach ($items as &$item) {
            $item['activities'] = $byBacklog[(int) $item['id']] ?? [];
            $item['counts'] = SprintProgress::statusCounts($item['activities']);
        }
        unset($item);

        $progress = SprintProgress::completionPercent($items);
        $open = $sprint['status'] === 'open';
        $elapsed = SprintPace::elapsedPercent($sprint['start_date'], $sprint['end_date'], $today);

        $projects = SprintProgress::byProject($items);

        // A project chosen for the sprint but without backlogs yet still shows up.
        $listed = array_column($projects, 'project_id');
        foreach ($this->sprintModel->projects($sprintId) as $p) {
            if (!in_array((int) $p['id'], $listed, true)) {
                $projects[] = ['project_id' => (int) $p['id'], 'project_name' => $p['name'], 'backlog_count' => 0, 'activity_count' => 0, 'progress_percent' => 0.0];
            }
        }
        usort($projects, fn (array $a, array $b) => strcmp($a['project_name'], $b['project_name']));

        foreach ($projects as &$project) {
            $project['items'] = array_values(array_filter($items, fn (array $i) => (int) $i['project_id'] === $project['project_id']));
            $project['counts'] = SprintProgress::statusCounts(array_merge([], ...array_column($project['items'], 'activities')));
        }
        unset($project);

        return [
            'sprint' => $sprint,
            'projects' => $projects,
            'progress' => $open ? $progress : (float) ($sprint['completion_percent'] ?? $progress),
            'elapsed_percent' => $elapsed,
            'health' => SprintPace::health($elapsed, $progress),
            'days_left' => max(0, DateMath::daysBetween($today, $sprint['end_date'])),
            'current_week' => $today < $sprint['start_date'] ? 0 : SprintPace::weekOf($sprint['start_date'], (int) $sprint['duration_weeks'], $today),
            'counts' => SprintProgress::statusCounts($activities),
            'activity_total' => count($activities),
            'alerts' => $this->alerts($activities, $today),
            'weeks' => $this->weeksWithCheckins($sprint, $sprintId, $today),
            'checkin_count' => count($this->checkinModel->forSprint($sprintId)),
        ];
    }

    /**
     * Saves a weekly check-in with a snapshot of the sprint taken right now.
     *
     * @param array{checkin_date:string,registered_by:?int,summary:string,blockers:?string,next_steps:?string} $data
     */
    public function addCheckin(int $sprintId, array $data, ?string $today = null): ?int
    {
        $review = $this->review($sprintId, $today);
        if ($review === null) {
            return null;
        }

        $sprint = $review['sprint'];

        return $this->checkinModel->insert([
            'sprint_id' => $sprintId,
            'checkin_date' => $data['checkin_date'],
            'week_number' => SprintPace::weekOf($sprint['start_date'], (int) $sprint['duration_weeks'], $data['checkin_date']),
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

    /** @param int[] $backlogIds */
    private function activitiesWithStatus(array $backlogIds, string $today): array
    {
        return array_map(function (array $a) use ($today) {
            $blocked = $a['depends_on_activity_id'] !== null && (int) ($a['depends_on_progress'] ?? 0) < 100;
            $a['system_status'] = ActivityStatus::compute((int) $a['progress_percent'], $a['due_date'], $blocked, $today);
            $a['due_soon'] = ActivityStatus::isDueSoon((int) $a['progress_percent'], $a['due_date'], 5, $today);

            return $a;
        }, $this->activityModel->byBacklogIds($backlogIds));
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
