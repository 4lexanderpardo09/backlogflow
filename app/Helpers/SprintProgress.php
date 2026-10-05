<?php

namespace App\Helpers;

/**
 * Pure math for "how far along is this sprint": the activity-weighted
 * progress of every backlog item assigned to it (a backlog with more
 * activities carries more weight). Kept DB-agnostic and unit testable;
 * the Model/Service layer feeds it plain data.
 */
class SprintProgress
{
    /**
     * @param array<int, array{progress_percent: float|int, activity_count: int}> $backlogItems
     */
    public static function completionPercent(array $backlogItems): float
    {
        $normalised = array_map(
            fn (array $b) => [
                'progress_percent' => (float) ($b['progress_percent'] ?? 0),
                'activity_count' => (int) ($b['activity_count'] ?? 0),
            ],
            $backlogItems
        );

        return round(Progress::projectProgress($normalised), 1);
    }

    /**
     * Progress per project of the sprint (a project = a module of the platform),
     * weighted by activities the same way the sprint as a whole is.
     *
     * @param array<int, array{project_id:int|string,project_name:string,progress_percent:float|int,activity_count:int}> $backlogItems
     * @return array<int, array{project_id:int,project_name:string,backlog_count:int,activity_count:int,progress_percent:float}>
     */
    public static function byProject(array $backlogItems): array
    {
        $groups = [];
        foreach ($backlogItems as $item) {
            $groups[(int) $item['project_id']][] = $item;
        }

        $rows = [];
        foreach ($groups as $projectId => $items) {
            $rows[] = [
                'project_id' => $projectId,
                'project_name' => (string) $items[0]['project_name'],
                'backlog_count' => count($items),
                'activity_count' => (int) array_sum(array_map(fn (array $i) => (int) $i['activity_count'], $items)),
                'progress_percent' => self::completionPercent($items),
            ];
        }

        usort($rows, fn (array $a, array $b) => strcmp($a['project_name'], $b['project_name']));

        return $rows;
    }

    /**
     * How many activities are in each system status.
     *
     * @param array<int, array{system_status:string}> $activities
     * @return array<string,int> completed, in_progress, pending, overdue, blocked (always all keys)
     */
    public static function statusCounts(array $activities): array
    {
        $counts = ['completed' => 0, 'in_progress' => 0, 'pending' => 0, 'overdue' => 0, 'blocked' => 0];
        foreach ($activities as $a) {
            if (isset($counts[$a['system_status']])) {
                $counts[$a['system_status']]++;
            }
        }

        return $counts;
    }
}
