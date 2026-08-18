<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $project @var array $backlogs */
?>
<div class="two-col">
    <div class="card">
        <p class="card-title">Detalle del proyecto</p>
        <p><?= htmlspecialchars($project['description'] ?? Labels::NOT_DEFINED) ?></p>
        <div class="table-scroll"><table>
            <tr><td>Desarrollador</td><td><?= htmlspecialchars($project['developer_name']) ?></td></tr>
            <tr><td>Prioridad</td><td><?= Ui::priorityBadge($project['priority_code']) ?></td></tr>
            <tr><td>Estado</td><td><?= Ui::statusBadge('project_status', $project['status_code']) ?></td></tr>
            <tr><td>Fecha de inicio</td><td><?= Ui::formatDate($project['start_date']) ?></td></tr>
            <tr><td>Fecha estimada de fin</td><td><?= Ui::formatDate($project['estimated_end_date']) ?></td></tr>
            <tr><td>Fecha real de fin</td><td><?= Ui::formatDate($project['actual_end_date']) ?></td></tr>
            <tr><td>Días transcurridos</td><td><?= $project['days_elapsed'] ?? Labels::NOT_DEFINED ?></td></tr>
            <tr><td>Días restantes</td><td><?= Ui::daysRemainingLabel($project['days_remaining'], $project['status_code'] === 'completed') ?></td></tr>
            <tr><td>Observaciones</td><td><?= htmlspecialchars($project['notes'] ?? Labels::NOT_DEFINED) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Avance y semáforo (calculados)</p>
        <?= Ui::progressBar($project['progress_percent']) ?>
        <p style="margin-top:14px;"><?= Ui::trafficLight($project['traffic_light']) ?></p>
        <p style="color:#6b7280;font-size:12.5px;">
            Actividades vencidas: <?= $project['overdue_activities'] ?> ·
            Actividades críticas/altas abiertas: <?= $project['critical_open_activities'] ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <p class="card-title" style="margin:0;">Backlogs de este proyecto</p>
        <a class="btn btn-secondary" href="/index.php?r=projects/backlog/create">+ Backlog</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>Descripción</th><th>Prioridad</th><th>Estado</th><th>Avance</th></tr></thead>
        <tbody>
        <?php foreach ($backlogs as $b): ?>
            <tr>
                <td><a href="/index.php?r=projects/backlog/view/<?= $b['id'] ?>"><?= htmlspecialchars($b['description']) ?></a></td>
                <td><?= Ui::priorityBadge($b['priority_code'] ?? null) ?></td>
                <td><?= Ui::statusBadge('backlog_status', $b['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($b['progress_percent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($backlogs === []): ?><tr><td colspan="4" class="empty-state">Sin backlogs registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
