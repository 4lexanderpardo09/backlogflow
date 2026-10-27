<?php

namespace App\Helpers;

/**
 * Pure progress-calculation math, kept separate from the database so it can
 * be unit tested without a MariaDB connection. The MariaDB views
 * (vw_backlog_progress, vw_project_progress) implement the same rules for
 * production queries; these functions exist for testing and for any
 * in-memory recalculation the Services layer needs.
 *
 * Every level above the activity (backlog, sub-project, project, platform,
 * sprint) uses the same rule: Σ(estimated hours × % de avance) ÷ Σ(estimated
 * hours) over ALL the activities it contains — never the plain average of its
 * children's percentages — and cancelled activities don't count at all.
 */
class Progress
{
    public const CANCELLED = 'cancelled';

    /**
     * Hours-weighted progress of a set of activities.
     *
     * @param array<int, array{progress_percent: float|int|string, estimated_hours?: float|int|string|null, status_code?: ?string}> $activities
     */
    public static function byHours(array $activities): float
    {
        $hours = 0.0;
        $weighted = 0.0;

        foreach ($activities as $a) {
            if (($a['status_code'] ?? null) === self::CANCELLED) {
                continue;
            }
            $h = self::hoursOf($a);
            $hours += $h;
            $weighted += $h * max(0.0, min(100.0, (float) $a['progress_percent']));
        }

        return $hours > 0 ? $weighted / $hours : 0.0;
    }

    /**
     * Combines groups whose progress is already hours-weighted (backlogs into
     * a sprint, sub-projects into a platform). Weighting each group by its
     * total hours gives exactly the same number as byHours() over all of
     * their activities together.
     *
     * @param array<int, array{progress_percent: float|int|string, hours_total: float|int|string|null}> $groups
     */
    public static function rollUp(array $groups): float
    {
        $hours = 0.0;
        $weighted = 0.0;

        foreach ($groups as $g) {
            $h = max(0.0, (float) ($g['hours_total'] ?? 0));
            $hours += $h;
            $weighted += $h * (float) $g['progress_percent'];
        }

        return $hours > 0 ? $weighted / $hours : 0.0;
    }

    /** Estimated hours of one activity; missing or non-positive counts as 1 (the default for old data). */
    public static function hoursOf(array $activity): float
    {
        $h = (float) ($activity['estimated_hours'] ?? 1);

        return $h > 0 ? $h : 1.0;
    }
}
