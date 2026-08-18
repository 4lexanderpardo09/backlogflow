<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $backlogItem @var array $activities */
?>
<div class="two-col">
    <div class="card">
        <p class="card-title">Detalle del backlog</p>
        <div class="table-scroll"><table>
            <tr><td>Proyecto</td><td><a href="/index.php?r=projects/projects/view/<?= $backlogItem['project_id'] ?>"><?= htmlspecialchars($backlogItem['project_name']) ?></a></td></tr>
            <tr><td>Desarrollador</td><td><?= htmlspecialchars($backlogItem['developer_name']) ?></td></tr>
            <tr><td>Tipo</td><td><?= htmlspecialchars(Labels::get('backlog_type', $backlogItem['type_code'])) ?></td></tr>
            <tr><td>Prioridad</td><td><?= Ui::priorityBadge($backlogItem['priority_code']) ?></td></tr>
            <tr><td>Estado</td><td><?= Ui::statusBadge('backlog_status', $backlogItem['status_code']) ?></td></tr>
            <tr><td>Fecha de creación</td><td><?= Ui::formatDate($backlogItem['created_date']) ?></td></tr>
            <tr><td>Fecha objetivo</td><td><?= Ui::formatDate($backlogItem['target_date']) ?></td></tr>
            <tr><td>Observaciones</td><td><?= htmlspecialchars($backlogItem['notes'] ?? Labels::NOT_DEFINED) ?></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Avance (calculado)</p>
        <?= Ui::progressBar($backlogItem['progress_percent']) ?>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <p class="card-title" style="margin:0;">Actividades</p>
        <a class="btn btn-secondary" href="/index.php?r=projects/activities/create">+ Actividad</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>Actividad</th><th>Prioridad</th><th>Estado</th><th>Avance</th><th>Fecha límite</th></tr></thead>
        <tbody>
        <?php foreach ($activities as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= Ui::priorityBadge($a['priority_code']) ?></td>
                <td><?= Ui::statusBadge('activity_status', $a['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($a['progress_percent']) ?></td>
                <td><?= Ui::formatDate($a['due_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($activities === []): ?><tr><td colspan="5" class="empty-state">Sin actividades registradas</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
