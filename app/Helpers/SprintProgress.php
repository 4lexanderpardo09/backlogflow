<?php

namespace App\Helpers;

/**
 * Pure math for "how is this sprint going", two separate numbers:
 *  - avance: hours-weighted progress of every activity in its backlogs
 *    (the same rule as backlogs and projects, cancelled activities excluded);
 *  - cumplimiento: hours of activities already terminadas ÷ the hours that
 *    were committed when the sprint started.
 * Kept DB-agnostic and unit testable; the Model/Service layer feeds it plain data.
 */
class SprintProgress
{
    /**
     * Avance of the sprint from its backlog items (each already hours-weighted).
     *
     * @param array<int, array{progress_percent: float|int, hours_total?: float|int|null}> $backlogItems
     */
    public static function completionPercent(array $backlogItems): float
    {
        return round(Progress::rollUp(array_map(
            fn (array $b) => ['progress_percent' => (float) ($b['progress_percent'] ?? 0), 'hours_total' => (float) ($b['hours_total'] ?? 0)],
            $backlogItems
        )), 1);
    }

    /** Cumplimiento: terminated hours over the hours committed at the start (0 when nothing was committed). */
    public static function compliancePercent(float $doneHours, ?float $committedHours): float
    {
        if ($committedHours === null || $committedHours <= 0) {
            return 0.0;
        }

        return round($doneHours / $committedHours * 100, 1);
    }

    /**
     * Progress per project of the sprint (a project = a module of the platform),
     * weighted by estimated hours the same way the sprint as a whole is.
     *
     * @param array<int, array{project_id:int|string,project_name:string,progress_percent:float|int,activity_count:int,hours_total?:float|int}> $backlogItems
     * @return array<int, array{project_id:int,project_name:string,backlog_count:int,activity_count:int,hours_total:float,progress_percent:float}>
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
                'hours_total' => (float) array_sum(array_map(fn (array $i) => (float) ($i['hours_total'] ?? 0), $items)),
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
