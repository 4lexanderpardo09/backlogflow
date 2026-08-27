<?php

namespace App\Helpers;

/**
 * Computes the system-suggested status of an activity from its progress,
 * due date and dependency state. This is shown alongside the manually
 * editable status as a hint/alert — it never overwrites the user's choice.
 */
class ActivityStatus
{
    public const COMPLETED = 'completed';
    public const IN_PROGRESS = 'in_progress';
    public const PENDING = 'pending';
    public const OVERDUE = 'overdue';
    public const BLOCKED = 'blocked';

    public static function compute(
        int $progressPercent,
        ?string $dueDate,
        bool $hasUnresolvedDependency,
        ?string $today = null
    ): string {
        $today ??= date('Y-m-d');

        if ($hasUnresolvedDependency && $progressPercent < 100) {
            return self::BLOCKED;
        }

        if ($progressPercent >= 100) {
            return self::COMPLETED;
        }

        if ($dueDate !== null && $dueDate < $today) {
            return self::OVERDUE;
        }

        if ($progressPercent > 0) {
            return self::IN_PROGRESS;
        }

        return self::PENDING;
    }

    /**
     * The % de avance an activity must have given its manually chosen status.
     * Only "en progreso" keeps a free-form value; "terminada" is always 100
     * and every other state (pendiente, cancelada, bloqueada, vencida) is 0.
     * Enforced server-side so the progress views can't be gamed via the DOM.
     */
    public static function progressForStatus(string $statusCode, int $requestedPercent): int
    {
        return match ($statusCode) {
            self::COMPLETED => 100,
            self::IN_PROGRESS => max(0, min(100, $requestedPercent)),
            default => 0,
        };
    }

    /**
     * True when the activity's due date is within $withinDays of today and
     * it isn't finished yet — used for the "próxima a vencer" alert.
     */
    public static function isDueSoon(int $progressPercent, ?string $dueDate, int $withinDays = 5, ?string $today = null): bool
    {
        if ($progressPercent >= 100 || $dueDate === null) {
            return false;
        }

        $today ??= date('Y-m-d');
        $daysRemaining = DateMath::daysBetween($today, $dueDate);

        return $daysRemaining >= 0 && $daysRemaining <= $withinDays;
    }
}
