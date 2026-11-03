<?php

namespace App\Helpers;

/**
 * Workload of a sprint per developer (the activity's responsable): how many
 * activities and estimated hours each one has, how many are already
 * terminadas, and how that compares with their capacity in the sprint
 * (business days × hours per day).
 */
class SprintLoad
{
    public const HOURS_PER_DAY = 8;

    /**
     * @param array<int, array{developer_id:int|string, developer_name:string, project_name:string,
     *     status_code:string, estimated_hours:float|string|null}> $activities cancelled ones are left out
     * @return array<int, array{developer_id:int, developer_name:string, projects:string[], activity_count:int,
     *     hours:float, done_hours:float, remaining_hours:float, capacity_hours:float, load_percent:float, overloaded:bool}>
     *     heaviest first
     */
    public static function byDeveloper(array $activities, int $businessDays, int $hoursPerDay = self::HOURS_PER_DAY): array
    {
        $capacity = (float) max(0, $businessDays * $hoursPerDay);
        $rows = [];

        foreach ($activities as $a) {
            if ($a['status_code'] === Progress::CANCELLED) {
                continue;
            }
            $id = (int) $a['developer_id'];
            $rows[$id] ??= [
                'developer_id' => $id, 'developer_name' => (string) $a['developer_name'], 'projects' => [],
                'activity_count' => 0, 'hours' => 0.0, 'done_hours' => 0.0,
            ];
            $hours = Progress::hoursOf($a);
            $rows[$id]['activity_count']++;
            $rows[$id]['hours'] += $hours;
            if ($a['status_code'] === 'completed') {
                $rows[$id]['done_hours'] += $hours;
            }
            if (!in_array($a['project_name'], $rows[$id]['projects'], true)) {
                $rows[$id]['projects'][] = (string) $a['project_name'];
            }
        }

        foreach ($rows as &$r) {
            sort($r['projects']);
            $r['hours'] = round($r['hours'], 2);
            $r['done_hours'] = round($r['done_hours'], 2);
            $r['remaining_hours'] = round($r['hours'] - $r['done_hours'], 2);
            $r['capacity_hours'] = $capacity;
            $r['load_percent'] = $capacity > 0 ? round($r['hours'] / $capacity * 100, 1) : 0.0;
            $r['overloaded'] = $capacity > 0 && $r['hours'] > $capacity;
        }
        unset($r);

        usort($rows, fn (array $a, array $b) => [$b['hours'], $a['developer_name']] <=> [$a['hours'], $b['developer_name']]);

        return array_values($rows);
    }
}
