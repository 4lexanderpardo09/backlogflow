<?php

namespace App\Helpers;

/**
 * Status of a backlog, sub-project or project, computed from its activities
 * instead of picked by hand (a "Pendiente" backlog at 100 % or a "Sin
 * iniciar" project at 94 % can no longer happen):
 *
 *  - not_started: no activity has any progress yet (so progress is 0 %);
 *  - completed:   it has activities and every one is terminada or cancelada;
 *  - in_progress: anything else.
 *
 * A few manual statuses mean something the activities can't express
 * ("En análisis", "En pruebas", "Bloqueado", "En pausa", "Cancelado"), so
 * when one of those is set it is kept as-is.
 */
class WorkStatus
{
    public const NOT_STARTED = 'not_started';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';

    /** Manual backlog statuses the calculation never overwrites. */
    public const BACKLOG_MANUAL = ['in_analysis', 'in_testing', 'blocked', 'cancelled'];
    /** Manual project statuses the calculation never overwrites. */
    public const PROJECT_MANUAL = ['on_hold', 'cancelled'];

    /** Computed status → backlog catalog code ("pending" is shown as "Sin iniciar", "in_development" as "En progreso"). */
    private const BACKLOG_CODE = [
        self::NOT_STARTED => 'pending',
        self::IN_PROGRESS => 'in_development',
        self::COMPLETED => 'completed',
    ];

    /**
     * @param array<int, array{progress_percent: float|int|string, status_code: string}> $activities
     */
    public static function derive(array $activities): string
    {
        if ($activities === []) {
            return self::NOT_STARTED;
        }

        $allClosed = true;
        $anyProgress = false;
        foreach ($activities as $a) {
            $code = $a['status_code'];
            if ($code !== 'completed' && $code !== Progress::CANCELLED) {
                $allClosed = false;
            }
            if ($code !== Progress::CANCELLED && ((float) $a['progress_percent'] > 0 || $code === 'completed')) {
                $anyProgress = true;
            }
        }

        return match (true) {
            $allClosed => self::COMPLETED,
            !$anyProgress => self::NOT_STARTED,
            default => self::IN_PROGRESS,
        };
    }

    /** Backlog status code to store: the manual one when it is special, otherwise the computed one. */
    public static function forBacklog(?string $currentCode, string $derived): string
    {
        return in_array($currentCode, self::BACKLOG_MANUAL, true) ? $currentCode : self::BACKLOG_CODE[$derived];
    }

    /** Project status code to store: the manual one when it is special, otherwise the computed one. */
    public static function forProject(?string $currentCode, string $derived): string
    {
        return in_array($currentCode, self::PROJECT_MANUAL, true) ? $currentCode : $derived;
    }
}
