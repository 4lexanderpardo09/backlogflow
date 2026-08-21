<?php

namespace App\Helpers;

/**
 * Pure math for "what % of this sprint's committed backlog got done",
 * kept separate from the database so it's unit testable. Mirrors the
 * pattern in Progress.php: the Model/Service layer feeds it plain data,
 * this class has no DB awareness at all.
 */
class SprintProgress
{
    /**
     * @param array<int, array{status_code: string}> $backlogItems every backlog item assigned to the sprint
     */
    public static function completionPercent(array $backlogItems): float
    {
        if ($backlogItems === []) {
            return 0.0;
        }

        $completed = count(array_filter($backlogItems, fn (array $b) => $b['status_code'] === 'completed'));

        return round(($completed / count($backlogItems)) * 100, 1);
    }
}
