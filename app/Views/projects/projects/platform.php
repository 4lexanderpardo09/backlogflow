<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $project @var array $children */
$totalBacklogs = array_sum(array_column($children, 'backlog_count'));
?>
<div class="two-col">
    <div class="card">
        <p class="card-title">Plataforma (proyecto padre)</p>
        <p><?= htmlspecialchars($project['description'] ?? Labels::NOT_DEFINED) ?></p>
        <div class="table-scroll"><table>
            <tr><td>Responsable</td><td><?= htmlspecialchars($project['developer_name']) ?></td></tr>
            <tr><td>Prioridad</td><td><?= Ui::priorityBadge($project['priority_code']) ?></td></tr>
            <tr><td>Estado</td><td><?= Ui::statusBadge('project_status', $project['status_code']) ?></td></tr>
            <tr><td>Fecha de inicio</td><td><?= Ui::formatDate($project['start_date']) ?></td></tr>
            <tr><td>Días transcurridos</td><td><?= $project['days_elapsed'] ?? Labels::NOT_DEFINED ?></td></tr>
            <tr><td>Subproyectos</td><td><?= count($children) ?></td></tr>
            <tr><td>Backlogs (todos)</td><td><?= (int) $totalBacklogs ?></td></tr>
            <tr><td>Actividades (todas)</td><td><?= (int) $project['activity_count'] ?></td></tr>
            <tr><td>Observaciones</td><td><?= htmlspecialchars($project['notes'] ?? Labels::NOT_DEFINED) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Avance agregado y semáforo</p>
        <?= Ui::progressBar($project['progress_percent']) ?>
        <p style="margin-top:14px;"><?= Ui::trafficLight($project['traffic_light']) ?></p>
        <p style="color:var(--color-muted-foreground);font-size:12.5px;">
            Una plataforma no tiene fecha de fin: su avance es el promedio de los subproyectos
            (ponderado por número de actividades) y su semáforo es el peor de ellos.
        </p>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <p class="card-title" style="margin:0;">Subproyectos</p>
        <a class="btn btn-secondary" href="/index.php?r=projects/projects/create&amp;parent_id=<?= (int) $project['id'] ?>">+ Nuevo subproyecto</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>Subproyecto</th><th>Responsable</th><th>Estado</th><th>Avance</th><th>Días restantes</th><th>Semáforo</th><th>Backlogs</th></tr></thead>
        <tbody>
        <?php foreach ($children as $c): ?>
            <tr>
                <td><a href="/index.php?r=projects/projects/view/<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                <td><?= htmlspecialchars($c['developer_name']) ?></td>
                <td><?= Ui::statusBadge('project_status', $c['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($c['progress_percent']) ?></td>
                <td><?= Ui::daysRemainingLabel($c['days_remaining'], $c['status_code'] === 'completed') ?></td>
                <td><?= Ui::trafficLight($c['traffic_light']) ?></td>
                <td><?= (int) $c['backlog_count'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($children === []): ?><tr><td colspan="7" class="empty-state">Esta plataforma aún no tiene subproyectos</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
