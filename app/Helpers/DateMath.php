<?php

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Small date-math helpers shared by activities (days remaining/overdue) and
 * contracts (expiration alert buckets).
 */
class DateMath
{
    /** Days from $from to $to; negative when $to is in the past relative to $from. */
    public static function daysBetween(string $from, string $to): int
    {
        $fromDate = new DateTimeImmutable($from);
        $toDate = new DateTimeImmutable($to);

        return (int) $fromDate->diff($toDate)->format('%r%a');
    }

    public static function daysElapsed(string $startDate, ?string $today = null): int
    {
        $today ??= date('Y-m-d');

        return max(0, self::daysBetween($startDate, $today));
    }

    public static function daysRemaining(?string $dueDate, ?string $today = null): ?int
    {
        if ($dueDate === null) {
            return null;
        }

        $today ??= date('Y-m-d');

        return self::daysBetween($today, $dueDate);
    }

    /**
     * Whether a record's date range crosses the filter range [from, to]. Either
     * filter edge may be open (null). A missing start/end borrows the other one;
     * a record with no date at all can't match an active date filter.
     */
    public static function rangeOverlaps(?string $start, ?string $end, ?string $from, ?string $to): bool
    {
        if ($from === null && $to === null) {
            return true;
        }

        $start = $start ?: $end;
        $end = $end ?: $start;
        if ($start === null || $start === '') {
            return false;
        }
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return ($to === null || $start <= $to) && ($from === null || $end >= $from);
    }

    public static function daysLate(?string $dueDate, ?string $today = null): int
    {
        $remaining = self::daysRemaining($dueDate, $today);

        if ($remaining === null || $remaining >= 0) {
            return 0;
        }

        return abs($remaining);
    }
}
