<?php

namespace App\Helpers;

/**
 * Pure progress-calculation math, kept separate from the database so it can
 * be unit tested without a MariaDB connection. The MariaDB views
 * (vw_backlog_progress, vw_project_progress) implement the same rules for
 * production queries; these functions exist for testing and for any
 * in-memory recalculation the Services layer needs.
 */
class Progress
{
    /**
     * Backlog progress: plain average of its activities' progress.
     *
     * @param int[] $activityPercentages
     */
    public static function backlogProgress(array $activityPercentages): float
    {
        if ($activityPercentages === []) {
            return 0.0;
        }

        return array_sum($activityPercentages) / count($activityPercentages);
    }

    /**
     * Project progress: weighted average of its backlog items' progress,
     * weighted by each backlog item's activity count so backlogs with more
     * work carry more weight than a backlog with a single finished task.
     *
     * @param array<int, array{progress_percent: float, activity_count: int}> $backlogs
     */
    public static function projectProgress(array $backlogs): float
    {
        $totalWeight = array_sum(array_column($backlogs, 'activity_count'));

        if ($totalWeight <= 0) {
            return 0.0;
        }

        $weightedSum = array_sum(array_map(
            fn (array $b) => $b['progress_percent'] * $b['activity_count'],
            $backlogs
        ));

        return $weightedSum / $totalWeight;
    }
}
