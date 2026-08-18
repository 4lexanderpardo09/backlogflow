/**
 * Shared Chart.js defaults/options builder used by every chart the
 * Charts helper (app/Helpers/Charts.php) renders — keeps tooltip/font/grid
 * styling consistent and theme-aware across the app without repeating it
 * in every inline chart script.
 */
window.BFCharts = (function () {
    function cssVar(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    }

    if (window.Chart) {
        Chart.defaults.font.family = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = cssVar('--color-muted-foreground', '#475569');
    }

    function deepMerge(target, source) {
        for (var key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                target[key] = deepMerge(target[key] && typeof target[key] === 'object' ? target[key] : {}, source[key]);
            } else {
                target[key] = source[key];
            }
        }
        return target;
    }

    function baseOptions(overrides) {
        var base = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: cssVar('--color-card', '#ffffff'),
                    titleColor: cssVar('--color-foreground', '#0f172a'),
                    bodyColor: cssVar('--color-foreground', '#0f172a'),
                    borderColor: cssVar('--color-border', '#e4e7eb'),
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 4,
                    cornerRadius: 6
                }
            },
            scales: {}
        };

        var merged = deepMerge(base, overrides || {});

        ['x', 'y'].forEach(function (axis) {
            if (merged.scales && merged.scales[axis]) {
                merged.scales[axis].grid = merged.scales[axis].grid || {};
                if (merged.scales[axis].grid.color === undefined) {
                    merged.scales[axis].grid.color = cssVar('--color-border', '#e4e7eb');
                }
                merged.scales[axis].ticks = merged.scales[axis].ticks || {};
            }
        });

        return merged;
    }

    return { baseOptions: baseOptions };
})();
