<?php

use App\Core\View;
use App\Helpers\Calendar;
use App\Helpers\Gantt;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var string $mode @var array $filters @var array $topProjects @var array $childProjects @var array $developers @var string $today */
$baseQuery = array_filter([
    'r' => 'projects/cronograma/index',
    'project_id' => $filters['project_id'] ?: null,
    'child_id' => $filters['child_id'] ?: null,
    'developer_id' => $filters['developer_id'] ?: null,
]);
$url = fn (array $extra): string => '/index.php?' . http_build_query(array_filter($extra + $baseQuery, fn ($v) => $v !== null && $v !== ''));
$statusOrder = ['pending', 'in_progress', 'completed', 'overdue', 'blocked'];
$weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
?>
<div class="cal-tabs" role="tablist" aria-label="Vista del cronograma">
    <a role="tab" class="cal-tab <?= $mode === 'calendario' ? 'is-active' : '' ?>" aria-selected="<?= $mode === 'calendario' ? 'true' : 'false' ?>" href="<?= htmlspecialchars($url([])) ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
        Calendario
    </a>
    <a role="tab" class="cal-tab <?= $mode === 'linea' ? 'is-active' : '' ?>" aria-selected="<?= $mode === 'linea' ? 'true' : 'false' ?>" href="<?= htmlspecialchars($url(['vista' => 'linea'])) ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h10M7 12h12M5 18h8"></path></svg>
        Línea de tiempo
    </a>
</div>

<form method="get" action="/index.php" class="filters filters-card">
    <input type="hidden" name="r" value="projects/cronograma/index">
    <?php if ($mode === 'linea'): ?><input type="hidden" name="vista" value="linea"><?php endif; ?>
    <?php if ($mode === 'calendario'): ?><input type="hidden" name="mes" value="<?= htmlspecialchars(substr($monthStart, 0, 7)) ?>"><?php endif; ?>
    <div class="filters-head">
        <p class="filters-title">Filtros</p>
        <div class="filters-actions">
            <?php if ($mode === 'linea'): ?><button class="btn btn-secondary" type="submit">Aplicar fechas</button><?php endif; ?>
            <?php if (array_filter($filters) !== [] || !empty($from) || !empty($to)): ?>
                <a class="btn btn-secondary" href="/index.php?r=projects/cronograma/index<?= $mode === 'linea' ? '&vista=linea' : '' ?>">Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="filters-grid">
        <?php View::renderPartial('shared/project-filter', [
            'topProjects' => $topProjects,
            'childProjects' => $childProjects,
            'projectFilter' => $filters['project_id'],
            'childFilter' => $filters['child_id'],
        ]) ?>
        <?php if ($mode === 'calendario'): ?>
            <label class="filter-field"><span>Responsable</span>
                <select name="developer_id" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === $filters['developer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <label class="filter-field"><span>Desde</span>
                <input type="date" name="desde" value="<?= htmlspecialchars($from ?? '') ?>">
            </label>
            <label class="filter-field"><span>Hasta</span>
                <input type="date" name="hasta" value="<?= htmlspecialchars($to ?? '') ?>">
            </label>
        <?php endif; ?>
    </div>
</form>

<?php if ($mode === 'calendario'): ?>
    <?php
    /** @var array $weeks @var array $events @var string $monthStart @var string $monthLabel @var string $prevMonth @var string $nextMonth
     *  @var array $counts @var int $monthTotal @var int $dueSoon */
    $monthKey = substr($monthStart, 0, 7);
    $projectsShown = [];
    foreach ($events as $e) {
        $projectsShown[$e['project_id']] = ['name' => $e['project'], 'color' => $e['color']];
    }
    ?>
    <div class="card kpi-strip-card">
        <div class="kpi-strip">
            <?= Ui::kpiStat((string) $monthTotal, 'Actividades en ' . mb_strtolower(explode(' ', $monthLabel)[0]), '<rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path>') ?>
            <?= Ui::kpiStat((string) $counts['completed'], 'Terminadas', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>', 'success') ?>
            <?= Ui::kpiStat((string) $counts['in_progress'], 'En progreso', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>') ?>
            <?= Ui::kpiStat((string) $counts['overdue'], 'Vencidas', '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>', $counts['overdue'] > 0 ? 'danger' : '') ?>
            <?= Ui::kpiStat((string) $dueSoon, 'Vencen en 7 días', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><path d="M12 9v4M12 17h.01"></path>', $dueSoon > 0 ? 'warning' : '') ?>
        </div>
    </div>

    <div class="card cal-card">
        <div class="cal-header">
            <div class="cal-nav">
                <a class="cal-nav-btn" href="<?= htmlspecialchars($url(['mes' => $prevMonth])) ?>" aria-label="Mes anterior">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                </a>
                <a class="cal-nav-btn" href="<?= htmlspecialchars($url(['mes' => $nextMonth])) ?>" aria-label="Mes siguiente">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                </a>
                <h2 class="cal-title"><?= htmlspecialchars($monthLabel) ?></h2>
                <?php if ($monthKey !== substr($today, 0, 7)): ?>
                    <a class="btn btn-secondary cal-today-btn" href="<?= htmlspecialchars($url(['mes' => substr($today, 0, 7)])) ?>">Hoy</a>
                <?php endif; ?>
            </div>
            <div class="cal-legend" aria-label="Leyenda de estados">
                <?php foreach ($statusOrder as $code): ?>
                    <span class="cal-legend-item"><span class="cal-swatch is-<?= $code ?>"></span><?= htmlspecialchars(Labels::get('activity_status', $code)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (count($projectsShown) > 1): ?>
            <div class="cal-projects" aria-label="Proyectos en el calendario">
                <?php foreach ($projectsShown as $p): ?>
                    <span class="cal-project-chip cal-c<?= $p['color'] % 8 ?>"><?= htmlspecialchars($p['name']) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="cal-grid" role="grid" aria-label="<?= htmlspecialchars($monthLabel) ?>">
            <div class="cal-weekdays" role="row">
                <?php foreach ($weekdays as $i => $wd): ?>
                    <div class="cal-weekday <?= $i >= 5 ? 'is-weekend' : '' ?>" role="columnheader"><?= $wd ?></div>
                <?php endforeach; ?>
            </div>

            <?php foreach ($weeks as $week):
                $segments = Calendar::layoutWeek($week['start'], $events);
                $hidden = Calendar::hiddenPerDay($segments); ?>
                <div class="cal-week" role="row">
                    <div class="cal-days">
                        <?php foreach ($week['days'] as $i => $day):
                            $classes = ['cal-day'];
                            if (substr($day, 0, 7) !== $monthKey) $classes[] = 'is-out';
                            if ($day === $today) $classes[] = 'is-today';
                            if ($i >= 5) $classes[] = 'is-weekend'; ?>
                            <div class="<?= implode(' ', $classes) ?>" role="gridcell">
                                <button type="button" class="cal-daynum" data-cal-day="<?= $day ?>" aria-label="Ver actividades del <?= Ui::formatDate($day) ?>"><?= (int) substr($day, 8, 2) ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cal-events">
                        <?php foreach ($segments as $s):
                            if ($s['lane'] >= Calendar::MAX_LANES) continue;
                            $e = $s['event'];
                            $tip = $e['title'] . "\n" . $e['project'] . ' · ' . $e['backlog'] . "\n"
                                . Ui::formatDate($e['start']) . ($e['end'] !== $e['start'] ? ' – ' . Ui::formatDate($e['end']) : '')
                                . ' · ' . Labels::get('activity_status', $e['status']) . ' · ' . $e['progress'] . '%'; ?>
                            <button type="button"
                                    class="cal-event is-<?= $e['status'] ?> cal-c<?= $e['color'] % 8 ?><?= $s['continues_before'] ? ' cont-before' : '' ?><?= $s['continues_after'] ? ' cont-after' : '' ?>"
                                    style="grid-column: <?= $s['col'] + 1 ?> / span <?= $s['span'] ?>; grid-row: <?= $s['lane'] + 1 ?>;"
                                    data-cal-event="<?= $e['id'] ?>" title="<?= htmlspecialchars($tip) ?>">
                                <?php if ($e['progress'] > 0 && $e['progress'] < 100): ?><span class="cal-event-progress" style="width:<?= $e['progress'] ?>%"></span><?php endif; ?>
                                <span class="cal-event-text"><?= htmlspecialchars($e['title']) ?></span>
                            </button>
                        <?php endforeach; ?>
                        <?php foreach ($hidden as $i => $n): if ($n === 0) continue; ?>
                            <button type="button" class="cal-more" style="grid-column: <?= $i + 1 ?>; grid-row: <?= Calendar::MAX_LANES + 1 ?>;" data-cal-day="<?= $week['days'][$i] ?>">+<?= $n ?> más</button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php /* Phones: the same month as an agenda list, since seven columns don't fit. */ ?>
        <div class="cal-agenda">
            <?php
            $byDay = [];
            foreach ($events as $e) {
                if ($e['end'] < $monthStart || $e['start'] > date('Y-m-t', strtotime($monthStart))) continue;
                $byDay[max($e['start'], $monthStart)][] = $e;
            }
            ksort($byDay);
            ?>
            <?php if ($byDay === []): ?>
                <p class="empty-state">Sin actividades con fecha en este mes.</p>
            <?php endif; ?>
            <?php foreach ($byDay as $day => $list): ?>
                <div class="cal-agenda-day <?= $day === $today ? 'is-today' : '' ?>">
                    <div class="cal-agenda-date"><?= $weekdays[(int) date('N', strtotime($day)) - 1] ?> <strong><?= (int) substr($day, 8, 2) ?></strong></div>
                    <div class="cal-agenda-list">
                        <?php foreach ($list as $e): ?>
                            <button type="button" class="cal-event is-<?= $e['status'] ?> cal-c<?= $e['color'] % 8 ?>" data-cal-event="<?= $e['id'] ?>">
                                <span class="cal-event-text"><?= htmlspecialchars($e['title']) ?></span>
                                <?php if ($e['end'] !== $e['start']): ?><span class="cal-event-range">hasta <?= Ui::formatDate($e['end']) ?></span><?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($events === []): ?>
            <p class="cal-empty-note">No hay actividades con fecha en estas semanas para el filtro elegido. Usa las flechas para cambiar de mes.</p>
        <?php endif; ?>
    </div>

    <dialog id="cal-dialog" class="modal">
        <div class="modal-header">
            <strong id="cal-dialog-title"></strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="modal-body" id="cal-dialog-body"></div>
    </dialog>

    <script type="application/json" id="cal-data"><?= json_encode([
        'events' => $events,
        'statusLabels' => array_combine($statusOrder, array_map(fn ($c) => Labels::get('activity_status', $c), $statusOrder)),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script>
    (function () {
        var data = JSON.parse(document.getElementById('cal-data').textContent);
        var byId = {};
        data.events.forEach(function (e) { byId[e.id] = e; });
        var dialog = document.getElementById('cal-dialog');
        var title = document.getElementById('cal-dialog-title');
        var body = document.getElementById('cal-dialog-body');
        var months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
        function fmt(iso) { var p = iso.split('-'); return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1] + ' ' + p[0]; }
        function range(e) { return e.start === e.end ? fmt(e.start) : fmt(e.start) + ' → ' + fmt(e.end); }
        function badge(e) { return '<span class="cal-status is-' + e.status + '">' + esc(data.statusLabels[e.status] || e.status) + '</span>'; }

        function showEvent(e) {
            title.textContent = e.title;
            body.innerHTML =
                '<div class="cal-detail">'
                + '<div class="cal-detail-row"><span>Estado</span><span>' + badge(e) + '</span></div>'
                + '<div class="cal-detail-row"><span>Fechas</span><span>' + range(e) + '</span></div>'
                + '<div class="cal-detail-row"><span>Avance</span><span><span class="cal-detail-bar"><span style="width:' + e.progress + '%"></span></span> ' + e.progress + '%</span></div>'
                + '<div class="cal-detail-row"><span>Responsable</span><span>' + esc(e.developer) + '</span></div>'
                + '<div class="cal-detail-row"><span>Proyecto</span><span><span class="cal-dot cal-c' + (e.color % 8) + '"></span>' + esc(e.project) + '</span></div>'
                + '<div class="cal-detail-row"><span>Backlog</span><span><a href="/index.php?r=projects/backlog/view/' + e.backlog_id + '">' + esc(e.backlog) + '</a></span></div>'
                + '</div>';
            if (!dialog.open) dialog.showModal();
        }

        function showDay(day) {
            var list = data.events.filter(function (e) { return e.start <= day && e.end >= day; });
            title.textContent = fmt(day) + ' · ' + list.length + (list.length === 1 ? ' actividad' : ' actividades');
            body.innerHTML = list.length === 0
                ? '<p class="empty-state">Sin actividades este día.</p>'
                : '<div class="cal-day-list">' + list.map(function (e) {
                    return '<button type="button" class="cal-day-item" data-cal-event="' + e.id + '">'
                        + '<span class="cal-dot cal-c' + (e.color % 8) + '"></span>'
                        + '<span class="cal-day-item-main"><strong>' + esc(e.title) + '</strong>'
                        + '<span class="text-muted">' + esc(e.project) + ' · ' + range(e) + ' · ' + esc(e.developer) + '</span></span>'
                        + badge(e) + '</button>';
                }).join('') + '</div>';
            if (!dialog.open) dialog.showModal();
        }

        document.addEventListener('click', function (ev) {
            var eventBtn = ev.target.closest('[data-cal-event]');
            if (eventBtn && byId[eventBtn.getAttribute('data-cal-event')]) {
                showEvent(byId[eventBtn.getAttribute('data-cal-event')]);
                return;
            }
            var dayBtn = ev.target.closest('[data-cal-day]');
            if (dayBtn) showDay(dayBtn.getAttribute('data-cal-day'));
        });
    })();
    </script>
<?php else: ?>
    <?php /** @var ?string $from @var ?string $to @var array $gantt */
    $customWindow = $from !== null && $to !== null && $from <= $to;
    $invertedWindow = $from !== null && $to !== null && $from > $to; ?>
    <div class="card">
        <?php if ($filters['project_id'] === 0): ?>
            <p class="empty-state">Elige un proyecto o una plataforma para ver su línea de tiempo.</p>
        <?php else: ?>
            <?php if ($invertedWindow): ?>
                <p style="font-size:12.5px;color:var(--color-warning);margin:0 0 var(--space-md);">
                    «Desde» es posterior a «Hasta», así que se muestra la ventana automática. Corrige las fechas y pulsa Aplicar.
                </p>
            <?php endif; ?>
            <p style="font-size:12.5px;color:var(--color-muted-foreground);margin:0 0 var(--space-md);">
                Sprints, backlogs y actividades con el relleno proporcional al avance. Ventana:
                <?= Ui::formatDate($gantt['window_start']) ?> — <?= Ui::formatDate($gantt['window_end']) ?>
                <?= $customWindow ? '(elegida)' : '(automática: sprints o mes actual)' ?>
            </p>
            <?= Gantt::render($gantt['window_start'], $gantt['window_end'], $gantt['rows']) ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
