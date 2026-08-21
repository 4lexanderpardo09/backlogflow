<?php

use App\Helpers\Charts;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $summary @var array $filters @var array $developers @var array $priorities @var array $statuses */
$kpi = $summary['kpi'];
$hasActiveFilters = array_filter($filters) !== [];
?>

<form method="get" action="/index.php" class="filters">
    <input type="hidden" name="r" value="projects/dashboard/index">
    <select name="developer_id" onchange="this.form.submit()">
        <option value="">Todos los desarrolladores</option>
        <?php foreach ($developers as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (string) $d['id'] === $filters['developer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority" onchange="this.form.submit()">
        <option value="">Toda prioridad</option>
        <?php foreach ($priorities as $pr): ?>
            <option value="<?= $pr['code'] ?>" <?= $pr['code'] === $filters['priority'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()">
        <option value="">Todo estado</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s['code'] ?>" <?= $s['code'] === $filters['status'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('project_status', $s['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($hasActiveFilters): ?>
        <a class="btn btn-secondary" href="/index.php?r=projects/dashboard/index">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?= Ui::kpiStrip(
    Ui::kpiHero($kpi['overall_progress'] . '%', 'Avance general de todos los proyectos'),
    Ui::kpiStat((string) $kpi['total_projects'], 'Proyectos', '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect>')
    . Ui::kpiStat((string) $kpi['active_projects'], 'Activos', '<path d="M3 17l6-6 4 4 8-8"></path><path d="M15 7h6v6"></path>', 'primary')
    . Ui::kpiStat((string) $kpi['finished_projects'], 'Terminados', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>', 'success')
    . Ui::kpiStat((string) $kpi['delayed_projects'], 'Atrasados', '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>', 'danger')
    . Ui::kpiStat((string) $kpi['total_backlogs'], 'Backlogs', '<path d="M4 6h16M4 12h16M4 18h7"></path>', 'purple')
    . Ui::kpiStat((string) $kpi['total_activities'], 'Actividades', '<rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 12l3 3 5-6"></path>', 'teal')
    . Ui::kpiStat((string) $kpi['pending_activities'], 'Pendientes', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>', 'warning')
    . Ui::kpiStat((string) $kpi['in_progress_activities'], 'En progreso', '<path d="M3 17l6-6 4 4 8-8"></path><path d="M15 7h6v6"></path>', 'indigo')
    . Ui::kpiStat((string) $kpi['completed_activities'], 'Terminadas', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>', 'success')
) ?>

<div class="two-col">
    <div class="card">
        <p class="card-title">Avance por proyecto</p>
        <?= Charts::horizontalBar(array_combine(
            array_map(fn ($p) => $p['name'], $summary['projects']),
            array_map(fn ($p) => round($p['progress_percent'], 1), $summary['projects'])
        ), '% de avance') ?>
    </div>
    <div class="card">
        <p class="card-title">Distribución de proyectos por estado</p>
        <?= Charts::donut(array_combine(
            array_map(fn ($k) => Labels::get('project_status', $k), array_keys($summary['status_distribution'])),
            array_values($summary['status_distribution'])
        )) ?>
    </div>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Distribución de proyectos por prioridad</p>
        <?= Charts::donut(array_combine(
            array_map(fn ($k) => Labels::get('priority', $k), array_keys($summary['priority_distribution'])),
            array_values($summary['priority_distribution'])
        )) ?>
    </div>
    <div class="card">
        <p class="card-title">Avance por desarrollador</p>
        <?= Charts::horizontalBar($summary['progress_by_developer'], '% de avance') ?>
    </div>
</div>

<div class="three-col">
    <div class="card">
        <p class="card-title">Mejor y peor proyecto</p>
        <?php if ($summary['top_project']): ?>
            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">Mayor avance</p>
            <p style="margin:0 0 12px;font-weight:600;"><?= htmlspecialchars($summary['top_project']['name']) ?> — <?= round($summary['top_project']['progress_percent'], 1) ?>%</p>
            <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">Menor avance</p>
            <p style="margin:0;font-weight:600;"><?= htmlspecialchars($summary['bottom_project']['name']) ?> — <?= round($summary['bottom_project']['progress_percent'], 1) ?>%</p>
        <?php else: ?>
            <p class="empty-state">Sin proyectos</p>
        <?php endif; ?>
    </div>
    <div class="card">
        <p class="card-title">Proyectos de prioridad alta/crítica (<?= count($summary['high_priority_projects']) ?>)</p>
        <?php foreach (array_slice($summary['high_priority_projects'], 0, 6) as $p): ?>
            <p style="margin:0 0 8px;"><?= Ui::priorityBadge($p['priority_code']) ?> <?= htmlspecialchars($p['name']) ?></p>
        <?php endforeach; ?>
        <?php if ($summary['high_priority_projects'] === []): ?><p class="empty-state">Sin proyectos de alta prioridad</p><?php endif; ?>
    </div>
    <div class="card">
        <p class="card-title">Actividades vencidas (<?= count($summary['overdue_activities']) ?>) / próximas a vencer (<?= count($summary['due_soon_activities']) ?>)</p>
        <?php foreach (array_slice($summary['overdue_activities'], 0, 5) as $a): ?>
            <p style="margin:0 0 6px;"><span class="badge badge-red">Vencida</span> <?= htmlspecialchars($a['name']) ?></p>
        <?php endforeach; ?>
        <?php foreach (array_slice($summary['due_soon_activities'], 0, 3) as $a): ?>
            <p style="margin:0 0 6px;"><span class="badge badge-yellow">Próxima</span> <?= htmlspecialchars($a['name']) ?></p>
        <?php endforeach; ?>
        <?php if ($summary['overdue_activities'] === [] && $summary['due_soon_activities'] === []): ?><p class="empty-state">Sin alertas de fecha</p><?php endif; ?>
    </div>
</div>

<div class="card">
    <p class="card-title">Avance de cada proyecto</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th><th>Avance</th><th>Semáforo</th></tr></thead>
        <tbody>
        <?php foreach ($summary['projects'] as $p): ?>
            <tr>
                <td><a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
                <td><?= htmlspecialchars($p['developer_name']) ?></td>
                <td><?= Ui::priorityBadge($p['priority_code']) ?></td>
                <td><?= Ui::statusBadge('project_status', $p['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($p['progress_percent']) ?></td>
                <td><?= Ui::trafficLight($p['traffic_light']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
