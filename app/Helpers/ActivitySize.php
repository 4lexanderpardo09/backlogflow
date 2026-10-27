<?php

namespace App\Helpers;

/**
 * T-shirt sizes that make estimating an activity quick: picking one fills
 * its estimated hours, which can still be edited by hand. An activity with
 * no estimate (NULL) still counts as 1 h in every progress roll-up
 * (Progress::hoursOf), but is listed as "sin horas estimadas".
 */
class ActivitySize
{
    /** Size => estimated hours. */
    public const HOURS = ['XS' => 1.0, 'S' => 2.0, 'M' => 4.0, 'L' => 8.0, 'XL' => 16.0];

    /** From this many hours an activity is "XL": too big, worth splitting. */
    public const TOO_BIG_HOURS = 16.0;

    /** The size whose hours match exactly, or null (custom hours or not estimated). */
    public static function fromHours(float|int|string|null $hours): ?string
    {
        if ($hours === null || $hours === '') {
            return null;
        }

        $size = array_search((float) $hours, self::HOURS, true);

        return $size === false ? null : $size;
    }

    public static function isTooBig(float|int|string|null $hours): bool
    {
        return $hours !== null && $hours !== '' && (float) $hours >= self::TOO_BIG_HOURS;
    }

    /** Whether an activity has been estimated (NULL / empty = not yet). */
    public static function isEstimated(array $activity): bool
    {
        return isset($activity['estimated_hours']) && $activity['estimated_hours'] !== '' && (float) $activity['estimated_hours'] > 0;
    }

    /**
     * How many of these activities still have no estimate (cancelled ones don't count).
     *
     * @param array<int, array{estimated_hours?: mixed, status_code?: ?string}> $activities
     */
    public static function countUnestimated(array $activities): int
    {
        return count(array_filter(
            $activities,
            fn (array $a) => ($a['status_code'] ?? null) !== Progress::CANCELLED && !self::isEstimated($a)
        ));
    }
}
