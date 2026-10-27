<?php

namespace App\Helpers;

/**
 * What a sprint holds, activity by activity (one row of sprint_activity each),
 * and the numbers of its close:
 *
 *  - comprometidas: the activities it started with (origin committed, carried
 *    from the previous sprint, or legacy = in the sprint before the start was
 *    recorded) — they stay committed even if taken out after the start;
 *  - alcance agregado: added after the start date;
 *  - cumplimiento: hours of the comprometidas that are terminadas ÷ hours
 *    comprometidas (the hours they had at the start when recorded). Never
 *    above 100 %; the alcance agregado is reported apart.
 */
class SprintScope
{
    public const COMMITTED = 'committed';
    public const ADDED = 'added';
    public const CARRIED = 'carried';
    public const LEGACY = 'legacy';

    /** Outcomes that take an activity out of the sprint at its close. */
    public const LEFT = ['moved', 'returned', 'cancelled'];

    /** A sprint has started once its start date is past: from then on, what is added is alcance agregado. */
    public static function hasStarted(string $startDate, string $today): bool
    {
        return $today > $startDate;
    }

    /** Origin of an activity added to the sprint today (not carried). */
    public static function originFor(string $startDate, string $today): string
    {
        return self::hasStarted($startDate, $today) ? self::ADDED : self::COMMITTED;
    }

    public static function isCommitted(array $row): bool
    {
        return $row['origin'] !== self::ADDED;
    }

    /** Still part of the sprint: not taken out after the start and not moved/returned/cancelled at a close. */
    public static function isActive(array $row): bool
    {
        return empty($row['removed_at']) && !in_array($row['outcome'] ?? null, self::LEFT, true);
    }

    /** Terminada while in this sprint (one moved and finished later in another sprint doesn't count here). */
    public static function isDone(array $row): bool
    {
        return $row['status_code'] === 'completed' && empty($row['removed_at'])
            && !in_array($row['outcome'] ?? null, self::LEFT, true);
    }

    /** Hours an activity weighs in the commitment: the ones recorded at the start, else its current ones. */
    public static function committedHoursOf(array $row): float
    {
        return $row['committed_hours'] !== null && $row['committed_hours'] !== ''
            ? (float) $row['committed_hours']
            : Progress::hoursOf($row);
    }

    /**
     * Counters and cumplimiento of a sprint from its sprint_activity rows.
     *
     * @param array<int, array{origin:string, status_code:string, estimated_hours:float|string|null,
     *     committed_hours:float|string|null, outcome:?string, removed_at:?string}> $rows
     */
    public static function summary(array $rows): array
    {
        $s = [
            'committed_count' => 0, 'committed_hours' => 0.0, 'committed_completed_hours' => 0.0,
            'committed_from_legacy' => false,
            'added_count' => 0, 'added_hours' => 0.0, 'added_completed_count' => 0, 'added_completed_hours' => 0.0,
            'completed_count' => 0, 'completed_hours' => 0.0,
            'moved_count' => 0, 'returned_count' => 0, 'cancelled_count' => 0, 'removed_count' => 0,
            'carried_count' => 0, 'active_count' => 0,
        ];

        foreach ($rows as $row) {
            $done = self::isDone($row);
            $hours = Progress::hoursOf($row);

            if (self::isCommitted($row)) {
                $committedHours = self::committedHoursOf($row);
                $s['committed_count']++;
                $s['committed_hours'] += $committedHours;
                if ($done) {
                    $s['committed_completed_hours'] += $committedHours;
                }
                if ($row['origin'] === self::LEGACY) {
                    $s['committed_from_legacy'] = true;
                }
                if ($row['origin'] === self::CARRIED) {
                    $s['carried_count']++;
                }
                if (!empty($row['removed_at'])) {
                    $s['removed_count']++;
                }
            } else {
                $s['added_count']++;
                $s['added_hours'] += $hours;
                if ($done) {
                    $s['added_completed_count']++;
                    $s['added_completed_hours'] += $hours;
                }
            }

            if ($done) {
                $s['completed_count']++;
                $s['completed_hours'] += $hours;
            }
            if (self::isActive($row)) {
                $s['active_count']++;
            }
            match ($row['outcome'] ?? null) {
                'moved' => $s['moved_count']++,
                'returned' => $s['returned_count']++,
                'cancelled' => $s['cancelled_count']++,
                default => null,
            };
        }

        foreach (['committed_hours', 'committed_completed_hours', 'added_hours', 'added_completed_hours', 'completed_hours'] as $k) {
            $s[$k] = round($s[$k], 2);
        }
        $s['compliance_percent'] = $s['committed_count'] > 0
            ? min(100.0, SprintProgress::compliancePercent($s['committed_completed_hours'], $s['committed_hours']))
            : 0.0;

        return $s;
    }
}
