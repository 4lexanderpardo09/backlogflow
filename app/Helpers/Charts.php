<?php

namespace App\Helpers;

/**
 * Renders dashboard charts as Chart.js canvases. Chart.js is vendored
 * locally at public/assets/js/chart.umd.min.js (loaded once, synchronously,
 * in the page <head> — see app/Views/layout/main.php) so there is no live
 * CDN dependency at runtime, only a one-time local file.
 */
class Charts
{
    private const PALETTE = ['#2563eb', '#059669', '#d97706', '#dc2626', '#64748b', '#7c3aed', '#0891b2', '#db2777'];

    private static int $counter = 0;

    /** @return string[] */
    private static function repeatingPalette(int $count): array
    {
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = self::PALETTE[$i % count(self::PALETTE)];
        }

        return $colors;
    }

    /**
     * Horizontal bar chart — best for comparing many labeled values
     * (progress per project, per developer, per type...).
     *
     * @param array<string,int|float> $data label => value
     */
    public static function horizontalBar(array $data, string $label = ''): string
    {
        if ($data === []) {
            return '<p class="empty-state">Sin datos</p>';
        }

        $id = 'bf-chart-' . (++self::$counter);
        $height = max(160, count($data) * 34);
        $labels = json_encode(array_map('strval', array_keys($data)), JSON_UNESCAPED_UNICODE);
        $values = json_encode(array_values($data));
        $labelJson = json_encode($label, JSON_UNESCAPED_UNICODE);
        $colors = json_encode(self::repeatingPalette(count($data)));

        return <<<HTML
            <div class="chart-wrap" style="height:{$height}px;">
                <canvas id="{$id}"></canvas>
            </div>
            <script>
            (function () {
                new Chart(document.getElementById('{$id}'), {
                    type: 'bar',
                    data: {
                        labels: {$labels},
                        datasets: [{
                            label: {$labelJson},
                            data: {$values},
                            backgroundColor: {$colors},
                            borderRadius: 4,
                            maxBarThickness: 22
                        }]
                    },
                    options: BFCharts.baseOptions({
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } },
                            y: { grid: { display: false } }
                        }
                    })
                });
            })();
            </script>
            HTML;
    }

    /**
     * Doughnut chart with a built-in legend — best for a small set of
     * mutually-exclusive categories (status/priority/criticality mix).
     *
     * @param array<string,int|float> $data label => value
     */
    public static function donut(array $data): string
    {
        if ($data === [] || array_sum($data) <= 0) {
            return '<p class="empty-state">Sin datos</p>';
        }

        $id = 'bf-chart-' . (++self::$counter);
        $labels = json_encode(array_map('strval', array_keys($data)), JSON_UNESCAPED_UNICODE);
        $values = json_encode(array_values($data));
        $colors = json_encode(self::repeatingPalette(count($data)));

        return <<<HTML
            <div class="chart-wrap chart-wrap-donut">
                <canvas id="{$id}"></canvas>
            </div>
            <script>
            (function () {
                var palette = {$colors};
                new Chart(document.getElementById('{$id}'), {
                    type: 'doughnut',
                    data: {
                        labels: {$labels},
                        datasets: [{
                            data: {$values},
                            backgroundColor: palette,
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--color-card').trim() || '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: BFCharts.baseOptions({
                        cutout: '62%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                labels: { boxWidth: 10, padding: 12, font: { size: 11.5 } }
                            }
                        }
                    })
                });
            })();
            </script>
            HTML;
    }
}
