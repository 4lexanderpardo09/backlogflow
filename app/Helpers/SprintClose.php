<?php

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Rules of the close wizard and of a sprint once it is closed:
 *  - an activity is terminada only in status "completed"; "En revisión (PR)"
 *    and "En pruebas (ST)" are still pending and go to the next sprint by default;
 *  - each pending activity is moved to the next sprint, returned to the
 *    backlog (no sprint) or cancelled — cancelling needs a reason;
 *  - a closed sprint is read-only;
 *  - the next sprint starts the business day after the close and lasts the
 *    same calendar days as the one closed.
 */
class SprintClose
{
    public const MOVE = 'move';
    public const RETURN = 'return';
    public const CANCEL = 'cancel';

    public const ACTIONS = [self::MOVE, self::RETURN, self::CANCEL];

    /** Outcome stored in sprint_activity for each decision. */
    public const OUTCOME = [self::MOVE => 'moved', self::RETURN => 'returned', self::CANCEL => 'cancelled'];

    public static function isFinished(string $statusCode): bool
    {
        return $statusCode === 'completed';
    }

    public static function isEditable(array $sprint): bool
    {
        return ($sprint['status'] ?? 'open') === 'open';
    }

    /** Open and its end date already passed: "Sprint vencido sin cerrar". */
    public static function isOverdueOpen(array $sprint, string $today): bool
    {
        return self::isEditable($sprint) && $sprint['end_date'] < $today;
    }

    /** A pending activity whose due date falls after the end of the sprint. */
    public static function dueAfterEnd(?string $dueDate, string $sprintEnd, string $statusCode): bool
    {
        return $dueDate !== null && $dueDate !== '' && $dueDate > $sprintEnd
            && !self::isFinished($statusCode) && $statusCode !== Progress::CANCELLED;
    }

    /** Default decision for a pending activity in the wizard. */
    public static function defaultAction(): string
    {
        return self::MOVE;
    }

    /**
     * Checks the wizard's decisions for the pending activities.
     *
     * @param int[] $pendingIds activity ids that are not terminadas
     * @param array<int, array{action?:string, reason?:string}> $decisions activity id => decision
     * @return string[] error messages (empty = valid)
     */
    public static function validate(array $pendingIds, array $decisions, bool $hasTarget): array
    {
        $errors = [];
        $missing = 0;
        $noReason = 0;
        $moves = 0;

        foreach ($pendingIds as $id) {
            $action = $decisions[$id]['action'] ?? '';
            if (!in_array($action, self::ACTIONS, true)) {
                $missing++;
            } elseif ($action === self::CANCEL && trim((string) ($decisions[$id]['reason'] ?? '')) === '') {
                $noReason++;
            } elseif ($action === self::MOVE) {
                $moves++;
            }
        }

        if ($missing > 0) {
            $errors[] = "Falta decidir qué pasa con $missing actividad(es) sin terminar.";
        }
        if ($noReason > 0) {
            $errors[] = "Escribe el motivo de cancelación de $noReason actividad(es).";
        }
        if ($moves > 0 && !$hasTarget) {
            $errors[] = 'Elige o crea el siguiente sprint para las actividades que se mueven.';
        }

        return $errors;
    }

    /** @param array<int, array{action?:string}> $decisions */
    public static function needsTarget(array $decisions): bool
    {
        foreach ($decisions as $d) {
            if (($d['action'] ?? '') === self::MOVE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Suggested dates of the next sprint: starts the first business day after
     * the close and lasts the same calendar days as the sprint closed.
     *
     * @return array{start_date:string, end_date:string}
     */
    public static function suggestedDates(string $closeDate, string $prevStart, string $prevEnd): array
    {
        $start = BusinessDays::next($closeDate);
        $length = max(1, DateMath::daysBetween($prevStart, $prevEnd));

        return [
            'start_date' => $start,
            'end_date' => (new DateTimeImmutable($start))->modify("+$length days")->format('Y-m-d'),
        ];
    }

    /** "Sprint 4" → "Sprint 5"; a name without a number gets " 2". */
    public static function suggestedName(?string $prevName): string
    {
        $prevName = trim((string) $prevName);
        if ($prevName === '') {
            return '';
        }
        if (preg_match('/^(.*?)(\d+)$/u', $prevName, $m)) {
            return $m[1] . ((int) $m[2] + 1);
        }

        return $prevName . ' 2';
    }

    /** Sprint weeks for a period (the weekly follow-up needs at least one). */
    public static function weeksFor(string $start, string $end): int
    {
        return max(1, (int) ceil(DateMath::daysBetween($start, $end) / 7));
    }
}
