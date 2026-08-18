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

    public static function daysLate(?string $dueDate, ?string $today = null): int
    {
        $remaining = self::daysRemaining($dueDate, $today);

        if ($remaining === null || $remaining >= 0) {
            return 0;
        }

        return abs($remaining);
    }
}
