<?php

namespace App\Helpers;

/**
 * Pure math for "how far along is this sprint": the activity-weighted
 * progress of every backlog item assigned to it (a backlog with more
 * activities carries more weight). Kept DB-agnostic and unit testable;
 * the Model/Service layer feeds it plain data.
 */
class SprintProgress
{
    /**
     * @param array<int, array{progress_percent: float|int, activity_count: int}> $backlogItems
     */
    public static function completionPercent(array $backlogItems): float
    {
        $normalised = array_map(
            fn (array $b) => [
                'progress_percent' => (float) ($b['progress_percent'] ?? 0),
                'activity_count' => (int) ($b['activity_count'] ?? 0),
            ],
            $backlogItems
        );

        return round(Progress::projectProgress($normalised), 1);
    }
}
