<?php

namespace App\Helpers;

/**
 * T-shirt sizes that make estimating an activity easy: picking a size fills
 * the estimated hours, which stay editable by hand. An activity without
 * estimated hours (NULL) weighs 1 h in every roll-up (Progress::hoursOf)
 * until someone estimates it.
 */
class ActivitySize
{
    /** Size code => hours it fills in. */
    public const SIZES = ['XS' => 1.0, 'S' => 2.0, 'M' => 4.0, 'L' => 8.0, 'XL' => 16.0];

    /** Sizes from this one up deserve the "too big" warning. */
    public const TOO_BIG = 'XL';

    public const TOO_BIG_WARNING = 'Actividad muy grande: considera dividirla';

    /** The size whose hours are exactly these, or null (custom or missing hours). */
    public static function sizeFor(float|int|string|null $hours): ?string
    {
        if ($hours === null || $hours === '') {
            return null;
        }
        $key = array_search((float) $hours, self::SIZES, true);

        return $key === false ? null : $key;
    }

    /** Whether the activity has no estimated hours yet. */
    public static function isUnestimated(array $activity): bool
    {
        $h = $activity['estimated_hours'] ?? null;

        return $h === null || $h === '' || (float) $h <= 0;
    }

    /**
     * How many of these activities still need estimated hours (cancelled ones
     * don't count: they're out of every roll-up anyway).
     *
     * @param array<int, array{estimated_hours?: float|int|string|null, status_code?: ?string}> $activities
     */
    public static function countUnestimated(array $activities): int
    {
        return count(array_filter(
            $activities,
            fn (array $a) => ($a['status_code'] ?? null) !== Progress::CANCELLED && self::isUnestimated($a)
        ));
    }
}
