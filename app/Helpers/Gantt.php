<?php

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Server-rendered Gantt chart as plain positioned HTML (no JS charting
 * library, matching the rest of the app). Given a date window and a list of
 * rows, it emits one bar per row positioned/sized as a percentage of the
 * window, filled proportionally to each row's progress.
 *
 * The geometry math is a pure static function so it can be unit tested.
 */
class Gantt
{
    /**
     * Left offset and width of a bar as percentages of the window, clamped to
     * the window edges. Returns [0, 0] when the range falls entirely outside.
     *
     * @return array{0: float, 1: float} [leftPercent, widthPercent]
     */
    public static function barGeometry(string $windowStart, string $windowEnd, ?string $start, ?string $end): array
    {
        $total = DateMath::daysBetween($windowStart, $windowEnd);
        if ($total <= 0) {
            return [0.0, 0.0];
        }

        $start ??= $windowStart;
        $end ??= $windowEnd;
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        // Clamp to the visible window.
        $clampStart = max($start, $windowStart);
        $clampEnd = min($end, $windowEnd);
        if ($clampEnd < $windowStart || $clampStart > $windowEnd) {
            return [0.0, 0.0];
        }

        $left = DateMath::daysBetween($windowStart, $clampStart) / $total * 100;
        $width = DateMath::daysBetween($clampStart, $clampEnd) / $total * 100;

        $left = max(0.0, min(100.0, $left));
        $width = max(0.0, min(100.0 - $left, $width));

        return [round($left, 2), round($width, 2)];
    }

    /**
     * @param array<int, array{label: string, kind?: string, start: ?string, end: ?string, progress?: float|int, light?: ?string}> $rows
     */
    public static function render(string $windowStart, string $windowEnd, array $rows): string
    {
        if ($rows === []) {
            return '<p class="empty-state">Sin actividades planeadas para el filtro elegido.</p>';
        }

        $ticks = '';
        foreach (self::monthTicks($windowStart, $windowEnd) as $label => $pct) {
            $ticks .= sprintf('<span class="gantt-tick" style="left:%.2f%%;">%s</span>', $pct, htmlspecialchars($label));
        }

        $body = '';
        foreach ($rows as $row) {
            [$left, $width] = self::barGeometry($windowStart, $windowEnd, $row['start'] ?? null, $row['end'] ?? null);
            $progress = max(0.0, min(100.0, (float) ($row['progress'] ?? 0)));
            $kind = $row['kind'] ?? 'activity';
            $lightClass = isset($row['light']) && $row['light'] !== null ? ' is-' . $row['light'] : '';

            $bar = $width > 0
                ? sprintf(
                    '<div class="gantt-bar%s" style="left:%.2f%%;width:%.2f%%;"><span class="gantt-bar-fill" style="width:%.1f%%;"></span><span class="gantt-bar-caption">%d%%</span></div>',
                    $lightClass,
                    $left,
                    $width,
                    $progress,
                    (int) round($progress)
                )
                : '<span class="gantt-bar-caption" style="position:static;">sin fechas</span>';

            $body .= sprintf(
                '<div class="gantt-row"><div class="gantt-row-label is-%s">%s</div><div class="gantt-track">%s</div></div>',
                htmlspecialchars($kind),
                htmlspecialchars($row['label']),
                $bar
            );
        }

        return <<<HTML
            <div class="gantt"><div class="gantt-inner">
                <div class="gantt-head">
                    <div class="gantt-row-label">Actividad</div>
                    <div class="gantt-scale">{$ticks}</div>
                </div>
                {$body}
            </div></div>
            HTML;
    }

    /**
     * Month-boundary tick positions (label => percent) inside the window.
     *
     * @return array<string, float>
     */
    private static function monthTicks(string $windowStart, string $windowEnd): array
    {
        $total = DateMath::daysBetween($windowStart, $windowEnd);
        if ($total <= 0) {
            return [];
        }

        $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $cursor = (new DateTimeImmutable($windowStart))->modify('first day of next month');
        $end = new DateTimeImmutable($windowEnd);
        $ticks = [];

        while ($cursor <= $end) {
            $pct = DateMath::daysBetween($windowStart, $cursor->format('Y-m-d')) / $total * 100;
            $ticks[$months[(int) $cursor->format('n') - 1] . ' ' . $cursor->format('y')] = round($pct, 2);
            $cursor = $cursor->modify('+1 month');
        }

        return $ticks;
    }
}
