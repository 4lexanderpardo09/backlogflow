<?php

use App\Helpers\Ui;

/** @var array $sprint @var array $items @var float $livePercent */
?>
<div class="two-col">
    <div class="card">
        <p class="card-title">Detalle del sprint</p>
        <div class="table-scroll"><table>
            <tr><td>Proyecto</td><td><a href="/index.php?r=projects/projects/view/<?= $sprint['project_id'] ?>"><?= htmlspecialchars($sprint['project_name']) ?></a></td></tr>
            <tr><td>Periodo</td><td><?= Ui::formatDate($sprint['start_date']) ?> — <?= Ui::formatDate($sprint['end_date']) ?></td></tr>
            <tr><td>Dueño de proceso</td><td><?= htmlspecialchars($sprint['process_owner'] ?? '—') ?></td></tr>
            <tr><td>Estado</td><td><span class="badge <?= $sprint['status'] === 'open' ? 'badge-blue' : 'badge-gray' ?>"><?= $sprint['status'] === 'open' ? 'Abierto' : 'Cerrado' ?></span></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title"><?= $sprint['status'] === 'open' ? '% cumplido hasta ahora' : '% cumplido final' ?></p>
        <?= Ui::progressBar($sprint['status'] === 'open' ? $livePercent : (float) ($sprint['completion_percent'] ?? 0)) ?>
        <?php if ($sprint['status'] === 'open'): ?>
            <form method="post" action="/index.php?r=projects/sprints/close/<?= $sprint['id'] ?>" style="margin-top:14px;" onsubmit="return confirm('¿Cerrar este sprint? Lo pendiente pasará al siguiente sprint.');">
                <button class="btn" type="submit">Cerrar sprint</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="toolbar">
        <p class="card-title" style="margin:0;">Backlog de este sprint (<?= count($items) ?>)</p>
        <a class="btn btn-secondary" href="/index.php?r=projects/backlog/create">+ Backlog</a>
    </div>
    <div class="table-scroll"><table>
        <thead><tr><th>Descripción</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th><th>Avance</th></tr></thead>
        <tbody>
        <?php foreach ($items as $b): ?>
            <tr>
                <td><a href="/index.php?r=projects/backlog/view/<?= $b['id'] ?>"><?= htmlspecialchars($b['description']) ?></a></td>
                <td><?= htmlspecialchars($b['developer_name']) ?></td>
                <td><?= Ui::priorityBadge($b['priority_code']) ?></td>
                <td><?= Ui::statusBadge('backlog_status', $b['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($b['progress_percent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?><tr><td colspan="5" class="empty-state">Sin backlog asignado a este sprint</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
