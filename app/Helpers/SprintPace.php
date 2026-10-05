<?php

namespace App\Helpers;

/**
 * Pure math for following a sprint week by week: which week it is, how much
 * of the time has gone by and whether the real progress keeps up with it.
 */
class SprintPace
{
    /** Gap (time elapsed % minus progress %) up to which the sprint still counts as "al día". */
    public const ON_TRACK_GAP = 10.0;
    /** Gap up to which it is "en riesgo"; beyond it the sprint is "atrasado". */
    public const AT_RISK_GAP = 25.0;

    /**
     * @return array<int, array{number:int,start:string,end:string}>
     */
    public static function weeks(string $startDate, int $durationWeeks): array
    {
        $weeks = [];
        for ($i = 0; $i < max(1, $durationWeeks); $i++) {
            $weeks[] = [
                'number' => $i + 1,
                'start' => date('Y-m-d', strtotime($startDate . ' +' . ($i * 7) . ' days')),
                'end' => date('Y-m-d', strtotime($startDate . ' +' . ($i * 7 + 6) . ' days')),
            ];
        }

        return $weeks;
    }

    /** Week (1..duration) a date falls in; dates before the start give 1 and after the end the last week. */
    public static function weekOf(string $startDate, int $durationWeeks, string $date): int
    {
        $week = intdiv(max(0, DateMath::daysBetween($startDate, $date)), 7) + 1;

        return min(max(1, $durationWeeks), $week);
    }

    /** 0..100: how much of the sprint's calendar time has passed. */
    public static function elapsedPercent(string $startDate, string $endDate, string $today): float
    {
        $total = DateMath::daysBetween($startDate, $endDate) + 1;
        if ($total <= 0) {
            return 100.0;
        }

        $elapsed = DateMath::daysBetween($startDate, $today) + 1;

        return round(max(0, min(100, $elapsed / $total * 100)), 1);
    }

    /** green | yellow | red: the sprint's progress against the time that has gone by. */
    public static function health(float $elapsedPercent, float $progressPercent): string
    {
        if ($progressPercent >= 100) {
            return 'green';
        }

        $gap = $elapsedPercent - $progressPercent;

        return match (true) {
            $gap <= self::ON_TRACK_GAP => 'green',
            $gap <= self::AT_RISK_GAP => 'yellow',
            default => 'red',
        };
    }
}
