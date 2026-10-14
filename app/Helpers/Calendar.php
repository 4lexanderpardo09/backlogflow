<?php

namespace App\Helpers;

/**
 * Pure layout math for the month calendar of the Cronograma: which weeks a
 * month spans (weeks start on Monday) and how multi-day events are stacked
 * in lanes inside each week, like a wall calendar. No HTML here, so it can
 * be unit tested; the view turns the result into markup.
 */
class Calendar
{
    /** Lanes drawn per week before the rest collapse into "+N más". */
    public const MAX_LANES = 4;

    /**
     * Monday-to-Sunday weeks covering the whole month.
     *
     * @return array<int, array{start:string, days:string[]}>
     */
    public static function monthWeeks(int $year, int $month): array
    {
        $first = sprintf('%04d-%02d-01', $year, $month);
        $last = date('Y-m-t', strtotime($first));
        // ISO day of week: 1 = Monday ... 7 = Sunday.
        $start = date('Y-m-d', strtotime($first . ' -' . ((int) date('N', strtotime($first)) - 1) . ' days'));

        $weeks = [];
        for ($weekStart = $start; $weekStart <= $last; $weekStart = date('Y-m-d', strtotime($weekStart . ' +7 days'))) {
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $days[] = date('Y-m-d', strtotime($weekStart . " +{$i} days"));
            }
            $weeks[] = ['start' => $weekStart, 'days' => $days];
        }

        return $weeks;
    }

    /**
     * Places the events that touch a week into lanes. Longer and earlier
     * events go first so they keep a stable top lane; an event that started
     * before the week or goes on after it is flagged so the bar can show it.
     *
     * @param array<int, array{start:string, end:string}> $events (other keys pass through)
     * @return array<int, array{event:array, col:int, span:int, lane:int, continues_before:bool, continues_after:bool}>
     */
    public static function layoutWeek(string $weekStart, array $events): array
    {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        $inWeek = array_values(array_filter(
            $events,
            fn (array $e) => $e['end'] >= $weekStart && $e['start'] <= $weekEnd
        ));

        $segments = array_map(function (array $e) use ($weekStart, $weekEnd) {
            $from = max($e['start'], $weekStart);
            $to = min($e['end'], $weekEnd);
            $col = DateMath::daysBetween($weekStart, $from);

            return [
                'event' => $e,
                'col' => $col,
                'span' => DateMath::daysBetween($from, $to) + 1,
                'lane' => 0,
                'continues_before' => $e['start'] < $weekStart,
                'continues_after' => $e['end'] > $weekEnd,
            ];
        }, $inWeek);

        usort($segments, fn (array $a, array $b) => [$a['col'], -$a['span']] <=> [$b['col'], -$b['span']]);

        // Greedy lane assignment: the first lane whose last bar ends before this one starts.
        $laneEnds = [];
        foreach ($segments as &$s) {
            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] >= $s['col']) {
                $lane++;
            }
            $laneEnds[$lane] = $s['col'] + $s['span'] - 1;
            $s['lane'] = $lane;
        }
        unset($s);

        return $segments;
    }

    /**
     * How many segments of a week are hidden on each day (0..6) because they
     * sit beyond the visible lanes — the "+N más" counter of that day.
     *
     * @param array<int, array{col:int, span:int, lane:int}> $segments
     * @return int[] seven counters, Monday first
     */
    public static function hiddenPerDay(array $segments, int $maxLanes = self::MAX_LANES): array
    {
        $hidden = array_fill(0, 7, 0);
        foreach ($segments as $s) {
            if ($s['lane'] >= $maxLanes) {
                for ($d = $s['col']; $d < $s['col'] + $s['span']; $d++) {
                    $hidden[$d]++;
                }
            }
        }

        return $hidden;
    }
}
