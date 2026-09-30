<?php

namespace App\Helpers;

/**
 * Pure math for the daily support log: how long things took and how much of
 * the day-to-day work actually went through the help desk.
 */
class SupportLog
{
    /** 0 → "0 min", 45 → "45 min", 60 → "1 h", 90 → "1 h 30 min". */
    public static function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest . ' min';
        }

        return $rest === 0 ? $hours . ' h' : $hours . ' h ' . $rest . ' min';
    }

    /**
     * @param array<int,array{in_helpdesk:mixed,time_minutes:mixed}> $rows
     * @return array{total:int,helpdesk:int,outside_helpdesk:int,outside_pct:float,total_minutes:int,avg_minutes:int}
     */
    public static function summarize(array $rows): array
    {
        $total = count($rows);
        $helpdesk = count(array_filter($rows, fn (array $r) => (int) $r['in_helpdesk'] === 1));
        $minutes = (int) array_sum(array_map(fn (array $r) => (int) $r['time_minutes'], $rows));

        return [
            'total' => $total,
            'helpdesk' => $helpdesk,
            'outside_helpdesk' => $total - $helpdesk,
            'outside_pct' => $total === 0 ? 0.0 : round(($total - $helpdesk) / $total * 100, 1),
            'total_minutes' => $minutes,
            'avg_minutes' => $total === 0 ? 0 : (int) round($minutes / $total),
        ];
    }
}
