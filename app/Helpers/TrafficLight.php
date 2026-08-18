<?php

namespace App\Helpers;

/**
 * Project traffic-light rules (spec section 6): considers progress,
 * estimated end date, overdue activities and critical-priority activities.
 * Reused as-is by the SLA module for its own compliance semaphore
 * (Services\Sla\ComplianceService), so the "what counts as red" logic only
 * lives here once.
 */
class TrafficLight
{
    public const GREEN = 'green';
    public const YELLOW = 'yellow';
    public const RED = 'red';

    /**
     * @param float $progressPercent 0-100
     * @param string|null $estimatedEndDate Y-m-d
     * @param int $overdueActivities count of activities past due with progress < 100
     * @param int $criticalOpenActivities count of open critical/high priority activities
     * @param bool $isCompleted true when the project's own status is "completed"
     */
    public static function forProject(
        float $progressPercent,
        ?string $estimatedEndDate,
        int $overdueActivities,
        int $criticalOpenActivities,
        bool $isCompleted,
        ?string $today = null
    ): string {
        if ($isCompleted) {
            return self::GREEN;
        }

        $today ??= date('Y-m-d');
        $pastDeadline = $estimatedEndDate !== null && $estimatedEndDate < $today;

        if ($pastDeadline || $overdueActivities >= 2) {
            return self::RED;
        }

        if ($overdueActivities >= 1 || $criticalOpenActivities >= 1) {
            return self::YELLOW;
        }

        $daysRemaining = DateMath::daysRemaining($estimatedEndDate, $today);
        if ($daysRemaining !== null && $daysRemaining <= 10 && $progressPercent < 90) {
            return self::YELLOW;
        }

        return self::GREEN;
    }
}
