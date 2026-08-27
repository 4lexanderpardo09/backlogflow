<?php

use App\Helpers\Ui;

/** @var array $sprints */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Un sprint es un ciclo de revisión que puedes crear y editar a mano: nombre, fecha de inicio,
    duración en semanas, uno o varios proyectos hijos y los backlogs (de cualquiera de esos proyectos)
    que entran en el ciclo. El % de cumplimiento sale del avance de las actividades de esos backlogs.
</p>

<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=projects/sprints/create">+ Nuevo sprint</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Sprint</th><th>Proyectos</th><th>Periodo</th><th>Dueño de proceso</th><th>Estado</th><th>% cumplido</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sprints as $s): ?>
            <tr>
                <td><a href="/index.php?r=projects/sprints/view/<?= $s['id'] ?>"><?= htmlspecialchars($s['name'] ?: ('Sprint #' . $s['id'])) ?></a></td>
                <td><?= htmlspecialchars($s['project_names'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= Ui::formatDate($s['start_date']) ?> — <?= Ui::formatDate($s['end_date']) ?> <span class="text-muted">(<?= (int) $s['duration_weeks'] ?> sem)</span></td>
                <td><?= htmlspecialchars($s['process_owner'] ?? '—') ?></td>
                <td><span class="badge <?= $s['status'] === 'open' ? 'badge-blue' : 'badge-gray' ?>"><?= $s['status'] === 'open' ? 'Abierto' : 'Cerrado' ?></span></td>
                <td style="min-width:120px;"><?= Ui::progressBar($s['status'] === 'open' ? (float) $s['live_percent'] : (float) ($s['completion_percent'] ?? 0)) ?></td>
                <td>
                    <a class="link-button" href="/index.php?r=projects/sprints/view/<?= $s['id'] ?>">Ver</a> &middot;
                    <a class="link-button" href="/index.php?r=projects/sprints/edit/<?= $s['id'] ?>">Editar</a> &middot;
                    <?php if ($s['status'] === 'open'): ?>
                        <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/close/<?= $s['id'] ?>" data-confirm-message="¿Cerrar este sprint? Se congelará el % de cumplimiento.">Cerrar</button> &middot;
                    <?php endif; ?>
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/delete/<?= $s['id'] ?>" data-confirm-message="¿Eliminar este sprint? No se borran los backlogs, sólo su relación con el sprint.">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($sprints === []): ?><tr><td colspan="7" class="empty-state">Sin sprints registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
