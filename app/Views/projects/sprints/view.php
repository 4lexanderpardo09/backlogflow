<?php

use App\Helpers\Ui;

/** @var array $sprint @var array $projects @var array $items @var float $livePercent */
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">&larr; Sprints</a>
    <a class="btn" href="/index.php?r=projects/sprints/edit/<?= (int) $sprint['id'] ?>">Editar sprint</a>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Detalle del sprint</p>
        <div class="table-scroll"><table>
            <tr><td>Nombre</td><td><?= htmlspecialchars($sprint['name'] ?? '—') ?></td></tr>
            <tr><td>Proyectos</td><td>
                <?php if ($projects === []): ?>—<?php else: ?>
                    <?php foreach ($projects as $i => $p): ?><?= $i ? ', ' : '' ?><a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a><?php endforeach; ?>
                <?php endif; ?>
            </td></tr>
            <tr><td>Periodo</td><td><?= Ui::formatDate($sprint['start_date']) ?> — <?= Ui::formatDate($sprint['end_date']) ?> (<?= (int) $sprint['duration_weeks'] ?> semanas)</td></tr>
            <tr><td>Dueño de proceso</td><td><?= htmlspecialchars($sprint['process_owner'] ?? '—') ?></td></tr>
            <tr><td>Estado</td><td><span class="badge <?= $sprint['status'] === 'open' ? 'badge-blue' : 'badge-gray' ?>"><?= $sprint['status'] === 'open' ? 'Abierto' : 'Cerrado' ?></span></td></tr>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title"><?= $sprint['status'] === 'open' ? '% cumplido hasta ahora' : '% cumplido final' ?></p>
        <?= Ui::progressBar($sprint['status'] === 'open' ? $livePercent : (float) ($sprint['completion_percent'] ?? 0)) ?>
        <p style="color:var(--color-muted-foreground);font-size:12.5px;margin-top:10px;">
            Promedio de avance de las actividades de los backlogs del sprint, ponderado por número de actividades.
        </p>
        <?php if ($sprint['status'] === 'open'): ?>
            <form method="post" action="/index.php?r=projects/sprints/close/<?= $sprint['id'] ?>" style="margin-top:8px;" onsubmit="return confirm('¿Cerrar este sprint? Se congelará el % de cumplimiento.');">
                <button class="btn" type="submit">Cerrar sprint</button>
            </form>
        <?php else: ?>
            <form method="post" action="/index.php?r=projects/sprints/reopen/<?= $sprint['id'] ?>" style="margin-top:8px;">
                <button class="btn btn-secondary" type="submit">Reabrir sprint</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <p class="card-title" style="margin:0 0 var(--space-md);">Backlogs de este sprint (<?= count($items) ?>)</p>
    <div class="table-scroll"><table>
        <thead><tr><th>Descripción</th><th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th><th>Avance</th></tr></thead>
        <tbody>
        <?php foreach ($items as $b): ?>
            <tr>
                <td><a href="/index.php?r=projects/backlog/view/<?= $b['id'] ?>"><?= htmlspecialchars($b['description']) ?></a></td>
                <td><?= htmlspecialchars($b['project_name']) ?></td>
                <td><?= htmlspecialchars($b['developer_name']) ?></td>
                <td><?= Ui::priorityBadge($b['priority_code']) ?></td>
                <td><?= Ui::statusBadge('backlog_status', $b['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($b['progress_percent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?><tr><td colspan="6" class="empty-state">Sin backlog asignado. Usa «Editar sprint» para agregar backlogs.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
