<?php

namespace App\Services\Projects;

use App\Helpers\SprintProgress;
use App\Models\Activity;
use App\Models\Sprint;

/**
 * Builds the Gantt rows for the Cronograma tab: the sprints that touch the
 * chosen projects, their backlog items, and the activities of those backlog
 * items (all activities of a sprint backlog count as "planned"). Bars use
 * the sprint's own dates as the range; activities fall back to their own
 * start/due dates when set.
 */
class CronogramaService
{
    public function __construct(
        private readonly Sprint $sprintModel = new Sprint(),
        private readonly Activity $activityModel = new Activity(),
    ) {
    }

    /**
     * @param int[] $projectIds
     * @return array{window_start: string, window_end: string, rows: array<int, array>}
     */
    public function gantt(array $projectIds): array
    {
        $sprints = $this->sprintModel->forProjectIds($projectIds);
        $projectIds = array_map('intval', $projectIds);
        $rows = [];
        $starts = [];
        $ends = [];

        foreach ($sprints as $sprint) {
            $backlogs = array_values(array_filter(
                $this->sprintModel->backlogs((int) $sprint['id']),
                fn (array $b) => in_array((int) $b['project_id'], $projectIds, true)
            ));

            $starts[] = $sprint['start_date'];
            $ends[] = $sprint['end_date'];

            $rows[] = [
                'label' => ($sprint['name'] ?: 'Sprint #' . $sprint['id']),
                'kind' => 'sprint',
                'start' => $sprint['start_date'],
                'end' => $sprint['end_date'],
                'progress' => SprintProgress::completionPercent($backlogs),
                'light' => null,
            ];

            foreach ($backlogs as $b) {
                $rows[] = [
                    'label' => $b['description'],
                    'kind' => 'backlog',
                    'start' => $sprint['start_date'],
                    'end' => $sprint['end_date'],
                    'progress' => (float) $b['progress_percent'],
                    'light' => null,
                ];

                foreach ($this->activityModel->byBacklog((int) $b['id']) as $a) {
                    $rows[] = [
                        'label' => $a['name'],
                        'kind' => 'activity',
                        'start' => $a['start_date'] ?: $sprint['start_date'],
                        'end' => $a['due_date'] ?: ($a['end_date'] ?: $sprint['end_date']),
                        'progress' => (float) $a['progress_percent'],
                        'light' => (int) $a['progress_percent'] >= 100
                            ? 'green'
                            : (!empty($a['due_date']) && $a['due_date'] < date('Y-m-d') ? 'red' : 'yellow'),
                    ];
                }
            }
        }

        $windowStart = $starts ? min($starts) : date('Y-m-01');
        $windowEnd = $ends ? max($ends) : date('Y-m-t');

        return ['window_start' => $windowStart, 'window_end' => $windowEnd, 'rows' => $rows];
    }
}
