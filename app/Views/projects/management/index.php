<?php use App\Helpers\Ui; ?>
<p style="color:#6b7280;margin-top:0;">Vista consolidada para gerencia. Se actualiza automáticamente con cada cambio de avance en actividades.</p>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr><th>Proyecto</th><th>Desarrollador</th><th>Avance</th><th>Prioridad</th><th>Estado</th><th>Fecha límite</th><th>Semáforo</th></tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
                <td><?= htmlspecialchars($p['developer_name']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($p['progress_percent']) ?></td>
                <td><?= Ui::priorityBadge($p['priority_code']) ?></td>
                <td><?= Ui::statusBadge('project_status', $p['status_code']) ?></td>
                <td><?= Ui::formatDate($p['estimated_end_date']) ?></td>
                <td><?= Ui::trafficLight($p['traffic_light']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($projects === []): ?><tr><td colspan="7" class="empty-state">Sin proyectos registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
