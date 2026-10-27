<?php

use App\Helpers\Ui;

/** @var array $sprints */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Un sprint es un ciclo de revisión que puedes crear y editar a mano: nombre, objetivo, fechas,
    uno o varios proyectos hijos y las actividades (de cualquiera de sus backlogs) que entran en el ciclo.
    El <strong>avance</strong> pondera el % de cada actividad por sus horas estimadas; el
    <strong>cumplimiento</strong> son las horas terminadas de lo comprometido al inicio sobre las horas comprometidas
    (el alcance agregado después del inicio se muestra aparte).
</p>

<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=projects/sprints/create">+ Nuevo sprint</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Sprint</th><th>Proyectos</th><th>Periodo</th><th>Dueño de proceso</th><th>Estado</th><th>Avance</th><th>Cumplimiento</th><th>Cumplimiento al cierre</th><th>Arrastradas</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sprints as $s): ?>
            <tr>
                <td><a href="/index.php?r=projects/sprints/view/<?= $s['id'] ?>"><?= htmlspecialchars($s['name'] ?: ('Sprint #' . $s['id'])) ?></a></td>
                <td><?= htmlspecialchars($s['project_names'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= Ui::formatDate($s['start_date']) ?> — <?= Ui::formatDate($s['end_date']) ?> <span class="text-muted">(<?= (int) $s['duration_weeks'] ?> sem)</span></td>
                <td><?= htmlspecialchars($s['process_owner'] ?? '—') ?></td>
                <td><span class="badge <?= $s['status'] === 'open' ? 'badge-blue' : 'badge-gray' ?>"><?= $s['status'] === 'open' ? 'Abierto' : 'Cerrado' ?></span>
                    <?php if ($s['overdue_open']): ?><span class="badge badge-red">Vencido sin cerrar</span><?php endif; ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($s['progress']) ?><?= Ui::delayTag($s['overdue_count'], $s['oldest_open_due']) ?></td>
                <td style="min-width:120px;" title="Horas terminadas de lo comprometido al inicio sobre las horas comprometidas"><?= Ui::progressBar($s['compliance']) ?><?php if ($s['compliance_on_legacy']): ?><div class="text-muted" style="font-size:11px;">Comprometidas = heredadas</div><?php endif; ?></td>
                <td><?= $s['closure'] !== null && $s['closure']['compliance_percent'] !== null ? (float) $s['closure']['compliance_percent'] . '%' : '<span class="text-muted">—</span>' ?></td>
                <td><?= $s['carried_count'] > 0 ? (int) $s['carried_count'] : '<span class="text-muted">—</span>' ?></td>
                <td>
                    <a class="link-button" href="/index.php?r=projects/sprints/view/<?= $s['id'] ?>">Ver</a>
                    <?php if ($s['status'] === 'open'): ?>
                        &middot; <a class="link-button" href="/index.php?r=projects/sprints/edit/<?= $s['id'] ?>">Editar</a>
                        &middot; <a class="link-button" href="/index.php?r=projects/sprints/close/<?= $s['id'] ?>">Cerrar</a>
                        &middot; <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/delete/<?= $s['id'] ?>" data-confirm-message="¿Eliminar este sprint? No se borran las actividades, sólo su relación con el sprint.">Eliminar</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($sprints === []): ?><tr><td colspan="10" class="empty-state">Sin sprints registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
