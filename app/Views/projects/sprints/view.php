<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $review @var array $developers */
$sprint = $review['sprint'];
$isOpen = $sprint['status'] === 'open';
$counts = $review['counts'];
$closeIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>';
$weekLabel = $review['current_week'] === 0
    ? 'Aún no inicia'
    : 'Semana ' . $review['current_week'] . ' de ' . (int) $sprint['duration_weeks'];
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">&larr; Sprints</a>
    <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;">
        <button type="button" class="btn" data-open-modal="modal-checkin">+ Registrar seguimiento</button>
        <a class="btn btn-secondary" href="/index.php?r=projects/sprints/edit/<?= (int) $sprint['id'] ?>">Editar sprint</a>
    </div>
</div>

<div class="card kpi-strip-card">
    <?= Ui::kpiHero($review['progress'] . '%', $isOpen ? 'Avance real del sprint' : 'Cumplimiento final del sprint') ?>
    <div class="kpi-strip">
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
            <tr><td>Periodo</td><td><?= Ui::formatDate($sprint['start_date']) ?> — <?= Ui::formatDate($sprint['end_date']) ?> (<?= (int) $sprint['duration_weeks'] ?> semanas)</td></tr>
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
        <div style="margin-top:var(--space-md);">
            <?php if ($isOpen): ?>
                <form method="post" action="/index.php?r=projects/sprints/close/<?= $sprint['id'] ?>" onsubmit="return confirm('¿Cerrar este sprint? Se congelará el % de cumplimiento.');">
                    <button class="btn btn-secondary" type="submit">Cerrar sprint</button>
                </form>
            <?php else: ?>
                <form method="post" action="/index.php?r=projects/sprints/reopen/<?= $sprint['id'] ?>">
                    <button class="btn btn-secondary" type="submit">Reabrir sprint</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

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
                                <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/sprints/deleteCheckin/<?= $c['id'] ?>" data-confirm-message="¿Eliminar este seguimiento?">Eliminar</button>
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
                    </summary>
                    <?php if ($b['activities'] === []): ?>
                        <p class="text-muted" style="margin:var(--space-sm) 0;font-size:13px;">Este backlog aún no tiene actividades.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;"><table>
                            <tr><th>Actividad</th><th>Responsable</th><th>Estado</th><th>Avance</th><th>Fecha límite</th></tr>
                            <?php foreach ($b['activities'] as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['name']) ?></td>
                                    <td><?= htmlspecialchars($a['developer_name']) ?></td>
                                    <td><?= Ui::statusBadge('activity_status', $a['system_status']) ?></td>
                                    <td style="min-width:110px;"><?= Ui::progressBar((float) $a['progress_percent']) ?></td>
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

<dialog id="modal-checkin" class="modal">
    <div class="modal-header">
        <strong>Registrar seguimiento</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar"><?= $closeIcon ?></button>
    </div>
    <div class="modal-body">
        <div class="card" style="max-width:700px;">
            <form method="post" action="/index.php?r=projects/sprints/checkin/<?= (int) $sprint['id'] ?>">
                <p class="text-muted" style="margin-top:0;font-size:13px;">
                    Se guarda una foto del sprint de hoy: <?= $review['progress'] ?>% de avance,
                    <?= $counts['completed'] ?>/<?= $review['activity_total'] ?> actividades terminadas y <?= $counts['overdue'] ?> vencidas.
                </p>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha del seguimiento</label>
                        <input type="date" name="checkin_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Registrado por</label>
                        <select name="registered_by">
                            <option value="">Selecciona…</option>
                            <?php foreach ($developers as $d): ?>
                                <?php if ($d['status'] !== 'active') continue; ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>¿Cómo va el sprint?</label>
                        <textarea name="summary" required placeholder="Qué se avanzó, qué falta, cómo se ve el cierre"></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Bloqueos o riesgos</label>
                        <textarea name="blockers"></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Próximos pasos</label>
                        <textarea name="next_steps"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit">Guardar seguimiento</button>
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</dialog>
