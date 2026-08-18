<?php

namespace App\Helpers;

/**
 * Small HTML-rendering helpers used only by views, so tables/badges/progress
 * bars look the same everywhere without copy-pasting markup into every
 * .php view file.
 */
class Ui
{
    public static function priorityBadge(?string $code): string
    {
        if ($code === null) {
            return '<span class="badge badge-gray">' . Labels::NOT_DEFINED . '</span>';
        }

        $class = match ($code) {
            'critical' => 'badge-red',
            'high' => 'badge-yellow',
            'medium' => 'badge-blue',
            default => 'badge-gray',
        };

        return '<span class="badge ' . $class . '">' . htmlspecialchars(Labels::get('priority', $code)) . '</span>';
    }

    public static function statusBadge(string $group, ?string $code): string
    {
        if ($code === null) {
            return '<span class="badge badge-gray">' . Labels::NOT_DEFINED . '</span>';
        }

        $positive = ['completed'];
        $negative = ['overdue', 'blocked', 'cancelled', 'delayed'];

        $class = match (true) {
            in_array($code, $positive, true) => 'badge-green',
            in_array($code, $negative, true) => 'badge-red',
            $code === 'in_progress' => 'badge-blue',
            default => 'badge-gray',
        };

        return '<span class="badge ' . $class . '">' . htmlspecialchars(Labels::get($group, $code)) . '</span>';
    }

    public static function trafficLight(string $color): string
    {
        $label = Labels::get('traffic_light', $color);

        return '<span class="traffic-light"><span class="traffic-dot traffic-' . htmlspecialchars($color) . '"></span>'
            . htmlspecialchars($label) . '</span>';
    }

    public static function contractAlertBadge(string $bucket): string
    {
        $severity = ContractAlert::severity($bucket);
        $class = match ($severity) {
            'red' => 'badge-red',
            'yellow' => 'badge-yellow',
            default => 'badge-green',
        };

        return '<span class="badge ' . $class . '">' . htmlspecialchars(Labels::get('contract_alert', $bucket)) . '</span>';
    }

    public static function progressBar(float $percent): string
    {
        $percent = max(0, min(100, $percent));
        $completeClass = $percent >= 100 ? ' complete' : '';

        return '<div class="progress-bar"><div class="progress-bar-fill' . $completeClass . '" style="width:' . $percent . '%"></div></div>'
            . '<div style="font-size:11.5px;color:#6b7280;margin-top:2px;">' . round($percent, 1) . '%</div>';
    }

    public static function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return Labels::NOT_DEFINED;
        }

        return date('d/m/Y', strtotime($date));
    }

    /** Inline SVG warning-triangle icon (no emoji), for inconsistency alerts like manual vs. system status. */
    public static function warningIcon(string $title): string
    {
        return '<svg class="icon-warning" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="' . htmlspecialchars($title) . '">'
            . '<title>' . htmlspecialchars($title) . '</title>'
            . '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path>'
            . '<line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'
            . '</svg>';
    }

    /**
     * @param bool $isComplete when true, "days remaining" is meaningless (the
     *        item is already done) so we show a neutral dash instead of a
     *        misleading "N días de atraso" on a finished row.
     */
    public static function daysRemainingLabel(?int $days, bool $isComplete = false): string
    {
        if ($isComplete) {
            return '<span class="text-muted">—</span>';
        }

        if ($days === null) {
            return Labels::NOT_DEFINED;
        }

        if ($days < 0) {
            return '<span class="priority-critica">' . abs($days) . ' días de atraso</span>';
        }

        if ($days <= 5) {
            return '<span class="priority-alta">' . $days . ' días</span>';
        }

        return $days . ' días';
    }

    /**
     * The single headline number for a dashboard (e.g. "Avance general"),
     * meant to sit at the top of a kpiStrip() card as its own row — not as
     * a separate card, so the dashboard doesn't accumulate an extra box
     * just for one number.
     */
    public static function kpiHero(string $value, string $label): string
    {
        return '<div class="kpi-hero-row">'
            . '<span class="kpi-hero-value">' . htmlspecialchars($value) . '</span>'
            . '<span class="kpi-hero-label">' . htmlspecialchars($label) . '</span>'
            . '</div>';
    }

    /** One compact stat cell (icon + value + label), meant to sit inside a kpiStrip() row — no border/shadow of its own, just a divider from its CSS neighbor. */
    public static function kpiStat(string $value, string $label, string $iconPaths, string $accent = ''): string
    {
        $accentClass = $accent !== '' ? ' accent-' . $accent : '';

        return '<div class="kpi-stat' . $accentClass . '">'
            . '<div class="kpi-stat-value"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $iconPaths . '</svg>' . htmlspecialchars($value) . '</div>'
            . '<div class="kpi-stat-label">' . htmlspecialchars($label) . '</div>'
            . '</div>';
    }

    /**
     * Wraps kpiHero()+kpiStat() output in a single card, so an entire
     * dashboard's KPI section is one box instead of one box per number.
     */
    public static function kpiStrip(string $hero, string $stats): string
    {
        return '<div class="card kpi-strip-card">' . $hero . '<div class="kpi-strip">' . $stats . '</div></div>';
    }
}
