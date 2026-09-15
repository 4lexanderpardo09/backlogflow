<?php

namespace App\Services\Projects;

use App\Helpers\Gantt;
use App\Helpers\Progress;
use App\Helpers\SprintProgress;
use App\Models\Activity;
use App\Models\BacklogItem;
use App\Models\Sprint;

/**
 * Builds the Gantt rows for the Cronograma tab: the sprints that touch the
 * chosen projects, their backlog items, and the activities of those backlog
 * items (all activities of a sprint backlog count as "planned"). Bars use
 * the sprint's own dates as the range; activities fall back to their own
 * start/due dates when set.
 *
 * Backlog items not planned in any sprint are grouped under "Sin sprint
 * asignado", their bars spanning their activities' own dates — otherwise a
 * project tracked only by dates would show an empty chart. Only rows that
 * overlap the visible window are returned, so a large platform stays light.
 */
class CronogramaService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly Activity $activityModel = new Activity(),
        private readonly BacklogItem $backlogModel = new BacklogItem(),
    ) {
    }

    /**
     * @param int[] $projectIds
     * @param ?string $from Window start (Y-m-d) chosen by the user; together with $to it
     *                      replaces the automatic window (sprint span, else current month).
     * @return array{window_start: string, window_end: string, rows: array<int, array>}
     */
    public function gantt(array $projectIds, ?string $from = null, ?string $to = null): array
    {
        $projectIds = array_map('intval', $projectIds);
        $groups = [];
        $sprintStarts = [];
        $sprintEnds = [];
        $inSprint = [];

        foreach ($this->sprintModel->forProjectIds($projectIds) as $sprint) {
            $backlogs = array_values(array_filter(
                $this->sprintModel->backlogs((int) $sprint['id']),
                fn (array $b) => in_array((int) $b['project_id'], $projectIds, true)
            ));

            $sprintStarts[] = $sprint['start_date'];
            $sprintEnds[] = $sprint['end_date'];

            $activitiesByBacklog = $this->activitiesByBacklog(array_column($backlogs, 'id'));
            $children = [];
            foreach ($backlogs as $b) {
                $inSprint[(int) $b['id']] = true;
                $children[] = [
                    'row' => [
                        'label' => $b['description'],
                        'kind' => 'backlog',
                        'start' => $sprint['start_date'],
                        'end' => $sprint['end_date'],
                        'progress' => (float) $b['progress_percent'],
                        'light' => null,
                    ],
                    'activities' => array_map(
                        fn (array $a) => $this->activityRow(
                            $a,
                            $a['start_date'] ?: $sprint['start_date'],
                            $a['due_date'] ?: ($a['end_date'] ?: $sprint['end_date'])
                        ),
                        $activitiesByBacklog[(int) $b['id']] ?? []
                    ),
                ];
            }

            $groups[] = [
                'row' => [
                    'label' => ($sprint['name'] ?: 'Sprint #' . $sprint['id']),
                    'kind' => 'sprint',
                    'start' => $sprint['start_date'],
                    'end' => $sprint['end_date'],
                    'progress' => SprintProgress::completionPercent($backlogs),
                    'light' => null,
                ],
                'backlogs' => $children,
            ];
        }

        $unplanned = $this->unplannedGroup($projectIds, $inSprint);
        if ($unplanned !== null) {
            $groups[] = $unplanned;
        }

        if ($from !== null && $to !== null && $from <= $to) {
            [$windowStart, $windowEnd] = [$from, $to];
        } elseif ($sprintStarts !== []) {
            [$windowStart, $windowEnd] = [min($sprintStarts), max($sprintEnds)];
        } else {
            [$windowStart, $windowEnd] = [date('Y-m-01'), date('Y-m-t')];
        }

        return [
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'rows' => $this->visibleRows($groups, $windowStart, $windowEnd),
        ];
    }

    /**
     * Backlog items of the chosen projects that no sprint includes, with bars
     * spanning their dated activities. Activities without any date can't be
     * placed on a timeline, so they (and backlogs with only those) are skipped.
     *
     * @param int[] $projectIds
     * @param array<int, true> $inSprint backlog ids already drawn under a sprint
     */
    private function unplannedGroup(array $projectIds, array $inSprint): ?array
    {
        $backlogs = [];
        foreach ($projectIds as $projectId) {
            foreach ($this->backlogModel->byProject($projectId) as $b) {
                if (!isset($inSprint[(int) $b['id']])) {
                    $backlogs[] = $b;
                }
            }
        }

        $activitiesByBacklog = $this->activitiesByBacklog(array_column($backlogs, 'id'));
        $children = [];
        $weights = [];

        foreach ($backlogs as $b) {
            $activities = $activitiesByBacklog[(int) $b['id']] ?? [];
            $rows = [];
            foreach ($activities as $a) {
                $start = $a['start_date'] ?: ($a['due_date'] ?: $a['end_date']);
                $end = $a['due_date'] ?: ($a['end_date'] ?: $a['start_date']);
                if (!$start || !$end) {
                    continue;
                }
                $rows[] = $this->activityRow($a, $start, $end);
            }
            if ($rows === []) {
                continue;
            }

            $children[] = [
                'row' => [
                    'label' => $b['description'],
                    'kind' => 'backlog',
                    'start' => min(array_column($rows, 'start')),
                    'end' => max(array_column($rows, 'end')),
                    'progress' => (float) $b['progress_percent'],
                    'light' => null,
                ],
                'activities' => $rows,
            ];
            $weights[] = ['progress_percent' => (float) $b['progress_percent'], 'activity_count' => count($activities)];
        }

        if ($children === []) {
            return null;
        }

        return [
            'row' => [
                'label' => 'Sin sprint asignado',
                'kind' => 'sprint',
                'start' => min(array_map(fn (array $c) => $c['row']['start'], $children)),
                'end' => max(array_map(fn (array $c) => $c['row']['end'], $children)),
                'progress' => Progress::projectProgress($weights),
                'light' => null,
            ],
            'backlogs' => $children,
        ];
    }

    /** Flattens sprint → backlog → activity groups, keeping only what the window shows. */
    private function visibleRows(array $groups, string $windowStart, string $windowEnd): array
    {
        $inWindow = fn (array $row): bool => Gantt::overlapsWindow($windowStart, $windowEnd, $row['start'], $row['end']);
        $rows = [];

        foreach ($groups as $group) {
            $kept = [];
            foreach ($group['backlogs'] as $backlog) {
                $activities = array_values(array_filter($backlog['activities'], $inWindow));
                if ($activities !== [] || $inWindow($backlog['row'])) {
                    $kept[] = [$backlog['row'], ...$activities];
                }
            }

            if ($kept === [] && !$inWindow($group['row'])) {
                continue;
            }

            $rows[] = $group['row'];
            foreach ($kept as $backlogRows) {
                array_push($rows, ...$backlogRows);
            }
        }

        return $rows;
    }

    private function activityRow(array $activity, string $start, string $end): array
    {
        return [
            'label' => $activity['name'],
            'kind' => 'activity',
            'start' => $start,
            'end' => $end,
            'progress' => (float) $activity['progress_percent'],
            'light' => (int) $activity['progress_percent'] >= 100
                ? 'green'
                : (!empty($activity['due_date']) && $activity['due_date'] < date('Y-m-d') ? 'red' : 'yellow'),
        ];
    }

    /**
     * @param array<int, int|string> $backlogIds
     * @return array<int, array<int, array>> activities grouped by backlog_item_id
     */
    private function activitiesByBacklog(array $backlogIds): array
    {
        $grouped = [];
        foreach ($this->activityModel->byBacklogIds(array_map('intval', $backlogIds)) as $a) {
            $grouped[(int) $a['backlog_item_id']][] = $a;
        }

        return $grouped;
    }
}
