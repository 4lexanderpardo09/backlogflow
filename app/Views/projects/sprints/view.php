<?php

use App\Core\View;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $review @var array $developers */
$sprint = $review['sprint'];
$isOpen = $review['is_open'];
$scope = $review['scope'];
$counts = $review['counts'];
$closeIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>';
$defaultWeek = max(1, (int) $review['current_week']);
$checkinNote = 'Se guarda una foto del sprint de hoy: ' . $review['progress'] . '% de avance, '
    . $counts['completed'] . '/' . $review['activity_total'] . ' actividades terminadas y ' . $counts['overdue'] . ' vencidas.';
$fmtHours = fn (float $h): string => rtrim(rtrim(number_format($h, 2, ',', '.'), '0'), ',');
$weekLabel = $review['current_week'] === 0
    ? 'Aún no inicia'
    : 'Semana ' . $review['current_week'] . ' de ' . (int) $sprint['duration_weeks'];
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">&larr; Sprints</a>
    <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;">
        <?php if ($isOpen): ?>
            <button type="button" class="btn" data-open-modal="modal-checkin">+ Registrar seguimiento</button>
            <a class="btn btn-secondary" href="/index.php?r=projects/sprints/edit/<?= (int) $sprint['id'] ?>">Editar sprint</a>
            <a class="btn btn-secondary" href="/index.php?r=projects/sprints/close/<?= (int) $sprint['id'] ?>">Cerrar sprint</a>
        <?php else: ?>
            <span class="badge badge-gray" style="align-self:center;">Cerrado · solo lectura</span>
            <button type="button" class="btn btn-secondary" data-open-modal="modal-reopen">Reabrir sprint</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($review['overdue_open']): ?>
    <div class="flash-banner flash-error" role="alert" style="display:block;">
        <strong>Sprint vencido sin cerrar.</strong> Terminó el <?= Ui::formatDate($sprint['end_date']) ?>.
        <a href="/index.php?r=projects/sprints/close/<?= (int) $sprint['id'] ?>">Ciérralo con el asistente</a> para decidir qué pasa con lo pendiente.
    </div>
<?php endif; ?>

<div class="card kpi-strip-card">
    <?= Ui::kpiHero($review['progress'] . '%', $isOpen ? 'Avance del sprint (ponderado por horas)' : 'Avance final del sprint') ?>
    <?= Ui::delayTag($review['delay']['overdue_count'], $review['delay']['oldest_due']) ?>
    <?= Ui::unestimatedNote($review['unestimated']) ?>
    <div class="kpi-strip">
        <?= Ui::kpiStat($review['compliance'] . '%', 'Cumplimiento: ' . $fmtHours($scope['committed_completed_hours']) . ' h terminadas de '
            . $fmtHours($scope['committed_hours']) . ' h comprometidas al inicio (' . $scope['committed_count'] . ' actividades)'
            . ($scope['committed_from_legacy'] ? ' · Comprometidas = heredadas (no se registró el compromiso al inicio)' : ''),
            '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="M22 4 12 14.01l-3-3"></path>', 'success') ?>
        <?= Ui::kpiStat((string) $scope['added_count'], 'Alcance agregado: ' . $fmtHours($scope['added_hours']) . ' h · ' . $scope['added_completed_count'] . ' terminadas',
            '<path d="M12 5v14M5 12h14"></path>', $scope['added_count'] > 0 ? 'warning' : '') ?>
        <?= Ui::kpiStat($review['elapsed_percent'] . '%', 'Tiempo transcurrido', '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path>') ?>
        <?= Ui::kpiStat($weekLabel, $isOpen ? ($review['days_left'] . ' días para el cierre') : 'Sprint cerrado', '<rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path>') ?>
        <?= Ui::kpiStat($counts['completed'] . ' / ' . $review['activity_total'], 'Actividades terminadas', '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>', 'success') ?>
        <?= Ui::kpiStat((string) $counts['overdue'], 'Vencidas', '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>', $counts['overdue'] > 0 ? 'danger' : '') ?>
        <?= Ui::kpiStat((string) $counts['blocked'], 'Bloqueadas', '<rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path>', $counts['blocked'] > 0 ? 'warning' : '') ?>
    </div>
    <p style="margin:var(--space-md) 0 0;font-size:13px;">
        <?= Ui::trafficLight($review['health']) ?>
        <span class="text-muted">· El avance real se compara con el tiempo que ya pasó del sprint.</span>
    </p>
</div>

<div class="two-col">
    <div class="card">
        <p class="card-title">Detalle del sprint</p>
        <div class="table-scroll"><table>
            <tr><td>Nombre</td><td><?= htmlspecialchars($sprint['name'] ?? '—') ?></td></tr>
            <tr><td>Objetivo</td><td><?= !empty($sprint['goal']) ? nl2br(htmlspecialchars($sprint['goal'])) : '<span class="text-muted">—</span>' ?></td></tr>
            <tr><td>Periodo</td><td><?= Ui::formatDate($sprint['start_date']) ?> — <?= Ui::formatDate($sprint['end_date']) ?> (<?= (int) $sprint['duration_weeks'] ?> semanas · <?= (int) $review['business_days'] ?> días hábiles)</td></tr>
            <tr><td>Dueño de proceso</td><td><?= htmlspecialchars($sprint['process_owner'] ?? '—') ?></td></tr>
            <tr><td>Estado</td><td><span class="badge <?= $isOpen ? 'badge-blue' : 'badge-gray' ?>"><?= $isOpen ? 'Abierto' : 'Cerrado' ?></span></td></tr>
            <?php if (!empty($sprint['notes'])): ?><tr><td>Observaciones</td><td><?= nl2br(htmlspecialchars($sprint['notes'])) ?></td></tr><?php endif; ?>
        </table></div>
    </div>
    <div class="card">
        <p class="card-title">Requiere atención (<?= count($review['alerts']) ?>)</p>
        <?php if ($review['alerts'] === []): ?>
            <p class="text-muted" style="margin:0;">Ninguna actividad vencida, bloqueada o próxima a vencer.</p>
        <?php else: ?>
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:var(--space-sm);">
                <?php foreach (array_slice($review['alerts'], 0, 8) as $a): ?>
                    <li style="font-size:13px;">
                        <?= $a['system_status'] === 'overdue' || $a['system_status'] === 'blocked'
                            ? Ui::statusBadge('activity_status', $a['system_status'])
                            : '<span class="badge badge-yellow">Vence pronto</span>' ?>
                        <strong><?= htmlspecialchars($a['name']) ?></strong>
                        <span class="text-muted">· <?= htmlspecialchars($a['project_name']) ?> · <?= htmlspecialchars($a['developer_name']) ?> · <?= Ui::formatDate($a['due_date']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($review['alerts']) > 8): ?><p class="text-muted" style="margin:var(--space-sm) 0 0;font-size:12.5px;">y <?= count($review['alerts']) - 8 ?> más, abajo en el detalle.</p><?php endif; ?>
        <?php endif; ?>
        <?php if ($isOpen && $review['due_after_end'] !== []): ?>
            <p style="margin:var(--space-md) 0 var(--space-sm);font-size:13px;"><strong>Vencen después del fin del sprint (<?= Ui::formatDate($sprint['end_date']) ?>)</strong></p>
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:var(--space-sm);">
                <?php foreach ($review['due_after_end'] as $a): ?>
                    <li style="font-size:13px;"><span class="badge badge-yellow">Vence <?= Ui::formatDate($a['due_date']) ?></span>
                        <strong><?= htmlspecialchars($a['name']) ?></strong> <span class="text-muted">· <?= htmlspecialchars($a['developer_name']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($review['closures'] !== []): ?>
<div class="card">
    <p class="card-title" style="margin:0 0 var(--space-md);">Cierres del sprint</p>
    <?php foreach ($review['closures'] as $c): ?>
        <div style="border:1px solid var(--color-border);border-radius:8px;padding:var(--space-md);margin-bottom:var(--space-md);font-size:13.5px;">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:var(--space-sm);">
                <strong>Cerrado el <?= Ui::formatDate(substr($c['closed_at'], 0, 10)) ?> · <?= htmlspecialchars($c['closed_by_name'] ?? Labels::NOT_DEFINED) ?></strong>
                <?php if ($c['reopened_at'] !== null): ?><span class="badge badge-yellow">Reabierto el <?= Ui::formatDate(substr($c['reopened_at'], 0, 10)) ?></span><?php endif; ?>
            </div>
            <div class="table-scroll"><table style="margin-top:var(--space-sm);">
                <tr><th>Comprometidas al inicio</th><th>Alcance agregado</th><th>Terminadas</th><th>Movidas</th><th>Devueltas</th><th>Canceladas</th><th>Cumplimiento</th></tr>
                <tr>
                    <td><?= $c['committed_count'] !== null ? (int) $c['committed_count'] . ' · ' . $fmtHours((float) $c['committed_hours']) . ' h' : '—' ?><?= $c['committed_from_legacy'] ? '<div class="text-muted" style="font-size:11px;">heredadas</div>' : '' ?></td>
                    <td><?= (int) $c['added_count'] ?> · <?= $fmtHours((float) $c['added_hours']) ?> h <span class="text-muted">(<?= (int) $c['added_completed_count'] ?> terminadas)</span></td>
                    <td><?= (int) $c['completed_count'] ?> · <?= $fmtHours((float) $c['completed_hours']) ?> h</td>
                    <td><?= (int) $c['moved_count'] ?><?= $c['next_sprint_id'] !== null ? ' → <a href="/index.php?r=projects/sprints/view/' . (int) $c['next_sprint_id'] . '">' . htmlspecialchars($c['next_sprint_name']) . '</a>' : '' ?></td>
                    <td><?= (int) $c['returned_count'] ?></td>
                    <td><?= (int) $c['cancelled_count'] ?></td>
                    <td><strong><?= $c['compliance_percent'] !== null ? (float) $c['compliance_percent'] . '%' : '—' ?></strong></td>
                </tr>
            </table></div>
            <?php if ($c['reopened_reason'] !== null): ?><p style="margin:var(--space-sm) 0 0;"><strong>Motivo de reapertura:</strong> <?= nl2br(htmlspecialchars($c['reopened_reason'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <p class="card-title" style="margin:0 0 var(--space-md);">Seguimiento semanal</p>
    <div style="display:grid;gap:var(--space-md);">
        <?php foreach ($review['weeks'] as $w): ?>
            <div style="border:1px solid var(--color-border);border-radius:8px;padding:var(--space-md);<?= $w['is_current'] ? 'border-color:var(--color-primary);' : '' ?>">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:var(--space-sm);">
                    <strong>Semana <?= $w['number'] ?> <?php if ($w['is_current']): ?><span class="badge badge-blue">Esta semana</span><?php endif; ?></strong>
                    <span class="text-muted" style="font-size:12.5px;"><?= Ui::formatDate($w['start']) ?> — <?= Ui::formatDate($w['end']) ?></span>
                </div>
                <?php if ($w['checkins'] === []): ?>
                    <p class="text-muted" style="margin:var(--space-sm) 0 0;font-size:13px;">
                        <?= $w['started'] ? 'Sin seguimiento registrado.' : 'Aún no empieza.' ?>
                    </p>
                <?php endif; ?>
                <?php foreach ($w['checkins'] as $c): ?>
                    <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid var(--color-border);font-size:13.5px;">
                        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:var(--space-sm);">
                            <span><strong><?= Ui::formatDate($c['checkin_date']) ?></strong>
                                <span class="text-muted">· <?= htmlspecialchars($c['registered_by_name'] ?? Labels::NOT_DEFINED) ?></span></span>
                            <span>
                                <span class="badge badge-blue"><?= (float) $c['progress_percent'] ?>% avance</span>
                                <span class="text-muted" style="font-size:12.5px;"><?= (int) $c['activities_done'] ?>/<?= (int) $c['activities_total'] ?> terminadas · <?= (int) $c['activities_overdue'] ?> vencidas</span>
                                <?php if ($isOpen): ?>
                                <button type="button" class="link-button" data-open-modal="modal-edit-checkin-<?= $c['id'] ?>">Editar</button>
                                &middot;
                                <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/deleteCheckin/<?= $c['id'] ?>" data-confirm-message="¿Eliminar este seguimiento?">Eliminar</button>
                                <?php endif; ?>
                            </span>
                        </div>
                        <p style="margin:var(--space-sm) 0 0;"><?= nl2br(htmlspecialchars($c['summary'])) ?></p>
                        <?php if (!empty($c['blockers'])): ?><p style="margin:var(--space-sm) 0 0;"><strong>Bloqueos:</strong> <?= nl2br(htmlspecialchars($c['blockers'])) ?></p><?php endif; ?>
                        <?php if (!empty($c['next_steps'])): ?><p style="margin:var(--space-sm) 0 0;"><strong>Próximos pasos:</strong> <?= nl2br(htmlspecialchars($c['next_steps'])) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <p class="card-title" style="margin:0 0 var(--space-md);">Avance por proyecto / módulo (<?= count($review['projects']) ?>)</p>
    <?php if ($review['projects'] === []): ?>
        <p class="empty-state">Sin proyectos ni backlogs. Usa «Editar sprint» para agregarlos.</p>
    <?php endif; ?>
    <?php foreach ($review['projects'] as $p): ?>
        <div style="margin-bottom:var(--space-lg);">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:var(--space-sm);align-items:flex-end;">
                <strong><a href="/index.php?r=projects/projects/view/<?= $p['project_id'] ?>"><?= htmlspecialchars($p['project_name']) ?></a></strong>
                <span class="text-muted" style="font-size:12.5px;"><?= $p['backlog_count'] ?> backlogs · <?= $p['activity_count'] ?> actividades</span>
            </div>
            <?= Ui::progressBar($p['progress_percent']) ?>

            <?php foreach ($p['items'] ?? [] as $b): ?>
                <details style="margin-top:var(--space-sm);">
                    <summary style="cursor:pointer;font-size:13.5px;">
                        <?= htmlspecialchars($b['description']) ?>
                        <?= Ui::statusBadge('backlog_status', $b['status_code']) ?>
                        <span class="text-muted">· <?= round((float) $b['progress_percent']) ?>% · <?= htmlspecialchars($b['developer_name']) ?></span>
                        <?php if ($b['counts']['overdue'] > 0): ?><span class="badge badge-red"><?= $b['counts']['overdue'] ?> vencidas</span><?php endif; ?>
                        <?php if ($b['unestimated'] > 0): ?><span class="badge badge-yellow"><?= $b['unestimated'] ?> sin horas estimadas</span><?php endif; ?>
                    </summary>
                    <?php if ($b['activities'] === []): ?>
                        <p class="text-muted" style="margin:var(--space-sm) 0;font-size:13px;">Este backlog aún no tiene actividades.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;"><table>
                            <tr><th>Actividad</th><th>Responsable</th><th>Estado</th><th>Avance</th><th>Horas</th><th>Fecha límite</th></tr>
                            <?php foreach ($b['activities'] as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['name']) ?>
                                        <?php if ($a['carried_from_sprint_id'] !== null): ?><span class="badge badge-purple">Arrastrada desde <?= htmlspecialchars($a['carried_from_name'] ?: 'Sprint #' . $a['carried_from_sprint_id']) ?></span><?php endif; ?>
                                        <?php if ($a['origin'] === 'added'): ?><span class="badge badge-yellow" title="Agregada el <?= Ui::formatDate(substr($a['added_at'], 0, 10)) ?> por <?= htmlspecialchars($a['added_by_name'] ?? Labels::NOT_DEFINED) ?>">Alcance agregado · <?= Ui::formatDate(substr($a['added_at'], 0, 10)) ?></span><?php endif; ?>
                                        <?php if ($isOpen && $a['due_after_end']): ?><span class="badge badge-yellow">Vence después del fin del sprint</span><?php endif; ?>
                                        <?php if (!$isOpen && $a['outcome'] !== null): ?><span class="badge <?= $a['outcome'] === 'completed' ? 'badge-green' : 'badge-gray' ?>"><?= htmlspecialchars(Labels::get('sprint_outcome', $a['outcome'])) ?></span><?php endif; ?>
                                        <?php if ($a['outcome'] === 'cancelled' && !empty($a['outcome_reason'])): ?><div class="text-muted" style="font-size:12px;">Motivo: <?= htmlspecialchars($a['outcome_reason']) ?></div><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['developer_name']) ?></td>
                                    <td><?= in_array($a['status_code'], ['in_review', 'in_testing', 'cancelled'], true) ? Ui::statusBadge('activity_status', $a['status_code']) : Ui::statusBadge('activity_status', $a['system_status']) ?></td>
                                    <td style="min-width:110px;"><?= Ui::progressBar((float) $a['progress_percent']) ?></td>
                                    <td><?= Ui::estimatedHours($a['estimated_hours'] ?? null) ?></td>
                                    <td><?= Ui::formatDate($a['due_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table></div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($review['removed'] !== []): ?>
<div class="card">
    <p class="card-title" style="margin:0 0 var(--space-md);">Quitadas después del inicio (<?= count($review['removed']) ?>)</p>
    <p class="text-muted" style="margin:0 0 var(--space-sm);font-size:12.5px;">Siguen contando como comprometidas: salieron del sprint cuando ya había empezado.</p>
    <ul style="margin:0;padding-left:18px;font-size:13px;">
        <?php foreach ($review['removed'] as $a): ?>
            <li><?= htmlspecialchars($a['name']) ?> <span class="text-muted">· <?= htmlspecialchars($a['project_name']) ?> · quitada el <?= Ui::formatDate(substr($a['removed_at'], 0, 10)) ?></span></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!$isOpen): ?>
<dialog id="modal-reopen" class="modal">
    <div class="modal-header">
        <strong>Reabrir sprint</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
    </div>
    <div class="modal-body">
        <form method="post" action="/index.php?r=projects/sprints/reopen/<?= (int) $sprint['id'] ?>">
            <div class="form-group">
                <label>Motivo (obligatorio)</label>
                <textarea name="reason" required data-expandable placeholder="Ej. se cerró antes de registrar unas actividades"></textarea>
            </div>
            <p class="text-muted" style="font-size:12.5px;">La foto del cierre se conserva y queda marcada como reabierta; al volver a cerrar se guarda una nueva.
                Lo que se movió, devolvió o canceló en el cierre no se deshace.</p>
            <div class="form-actions"><button class="btn" type="submit">Reabrir</button></div>
        </form>
    </div>
</dialog>
<?php endif; ?>

<dialog id="modal-checkin" class="modal">
    <div class="modal-header">
        <strong>Registrar seguimiento</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/sprints/checkin-form', [
            'action' => '/index.php?r=projects/sprints/checkin/' . (int) $sprint['id'],
            'checkin' => null,
            'weeks' => $review['weeks'],
            'defaultWeek' => $defaultWeek,
            'developers' => $developers,
            'note' => $checkinNote,
        ]) ?>
    </div>
</dialog>

<?php foreach ($review['weeks'] as $w): foreach ($w['checkins'] as $c): ?>
    <dialog id="modal-edit-checkin-<?= $c['id'] ?>" class="modal">
        <div class="modal-header">
            <strong>Editar seguimiento</strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
        </div>
        <div class="modal-body">
            <?php View::renderPartial('projects/sprints/checkin-form', [
                'action' => '/index.php?r=projects/sprints/editCheckin/' . (int) $c['id'],
                'checkin' => $c,
                'weeks' => $review['weeks'],
                'defaultWeek' => $defaultWeek,
                'developers' => $developers,
                'note' => 'El avance guardado (' . (float) $c['progress_percent'] . '%) no cambia: es la foto del sprint ese día.',
            ]) ?>
        </div>
    </dialog>
<?php endforeach; endforeach; ?>

<script>
(function () {
    // Picking a date moves the week selector to the sprint week that contains it.
    document.querySelectorAll('[data-checkin-form]').forEach(function (form) {
        var date = form.querySelector('[data-checkin-date]');
        var week = form.querySelector('[data-checkin-week]');
        date.addEventListener('change', function () {
            Array.prototype.some.call(week.options, function (opt) {
                if (date.value >= opt.getAttribute('data-start') && date.value <= opt.getAttribute('data-end')) {
                    week.value = opt.value;
                    return true;
                }
                return false;
            });
        });
    });
})();
</script>
