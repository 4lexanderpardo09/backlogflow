<?php

use App\Helpers\Labels;
use App\Helpers\Progress;
use App\Helpers\Ui;

/** @var array $review @var array $finished @var array $pending @var array $cancelledBefore @var array $openSprints
 *  @var array{start_date:string,end_date:string,name:string} $suggested @var array $developers @var string[] $errors @var array|null $posted */
$sprint = $review['sprint'];
$scope = $review['scope'];
$fmtHours = fn (float $h): string => rtrim(rtrim(number_format($h, 2, ',', '.'), '0'), ',');
$hoursOf = fn (array $rows): float => array_sum(array_map([Progress::class, 'hoursOf'], $rows));
$sprintName = fn (array $s): string => $s['name'] ?: 'Sprint #' . $s['id'];
$p = $posted ?? [];
$targetMode = $p['target_mode'] ?? 'new';
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/view/<?= (int) $sprint['id'] ?>">&larr; Volver al sprint</a>
    <div></div>
</div>

<?php if ($errors !== []): ?>
    <div class="flash-banner flash-error" role="alert" style="display:block;">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="/index.php?r=projects/sprints/close/<?= (int) $sprint['id'] ?>" data-close-wizard data-start-step="<?= $errors !== [] ? 2 : 1 ?>">
    <div class="card">
        <ol class="wizard-steps">
            <li data-step-tab="1">1. Resumen</li>
            <li data-step-tab="2">2. Sin terminar</li>
            <li data-step-tab="3">3. Confirmar</li>
        </ol>

        <!-- Step 1: summary -->
        <section data-step="1">
            <div class="kpi-strip">
                <?= Ui::kpiStat(count($finished) . ' · ' . $fmtHours($hoursOf($finished)) . ' h', 'Terminadas', '<path d="M9 11l3 3L22 4"></path>', 'success') ?>
                <?= Ui::kpiStat(count($pending) . ' · ' . $fmtHours($hoursOf($pending)) . ' h', 'Sin terminar', '<circle cx="12" cy="12" r="9"></circle><path d="M12 8v4M12 16h.01"></path>', count($pending) > 0 ? 'warning' : '') ?>
                <?= Ui::kpiStat($scope['committed_count'] . ' · ' . $fmtHours($scope['committed_hours']) . ' h', 'Comprometidas al inicio' . ($scope['committed_from_legacy'] ? ' (heredadas)' : ''), '<rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path>') ?>
                <?= Ui::kpiStat($scope['added_count'] . ' · ' . $fmtHours($scope['added_hours']) . ' h', 'Alcance agregado', '<path d="M12 5v14M5 12h14"></path>') ?>
                <?= Ui::kpiStat($scope['compliance_percent'] . '%', 'Cumplimiento al cierre', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="M22 4 12 14.01l-3-3"></path>', 'success') ?>
            </div>
            <p class="text-muted" style="font-size:12.5px;margin:var(--space-md) 0;">
                Cumplimiento = horas terminadas de lo comprometido al inicio ÷ horas comprometidas
                (<?= $fmtHours($scope['committed_completed_hours']) ?> h de <?= $fmtHours($scope['committed_hours']) ?> h).
                Solo cuenta «Terminada»: «En revisión (PR)» y «En pruebas (ST)» siguen sin terminar.
            </p>
            <div class="two-col">
                <div>
                    <p class="card-title">Terminadas (<?= count($finished) ?>)</p>
                    <ul style="margin:0;padding-left:18px;font-size:13px;">
                        <?php foreach ($finished as $a): ?>
                            <li><?= htmlspecialchars($a['name']) ?> <span class="text-muted">· <?= htmlspecialchars($a['project_name']) ?> · <?= htmlspecialchars($a['developer_name']) ?></span></li>
                        <?php endforeach; ?>
                        <?php if ($finished === []): ?><li class="text-muted">Ninguna.</li><?php endif; ?>
                    </ul>
                </div>
                <div>
                    <p class="card-title">Sin terminar (<?= count($pending) ?>)</p>
                    <ul style="margin:0;padding-left:18px;font-size:13px;">
                        <?php foreach ($pending as $a): ?>
                            <li><?= htmlspecialchars($a['name']) ?> <?= Ui::statusBadge('activity_status', $a['status_code']) ?> <span class="text-muted">· <?= (int) $a['progress_percent'] ?>% · <?= htmlspecialchars($a['developer_name']) ?></span></li>
                        <?php endforeach; ?>
                        <?php if ($pending === []): ?><li class="text-muted">Ninguna: todo quedó terminado.</li><?php endif; ?>
                    </ul>
                    <?php if ($cancelledBefore !== []): ?>
                        <p class="text-muted" style="font-size:12.5px;">Además, <?= count($cancelledBefore) ?> actividad(es) ya estaban canceladas durante el sprint.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions"><button type="button" class="btn" data-goto="2">Siguiente &rarr;</button></div>
        </section>

        <!-- Step 2: decision per pending activity -->
        <section data-step="2" hidden>
            <?php if ($pending === []): ?>
                <p class="empty-state">No hay actividades sin terminar. Continúa para confirmar el cierre.</p>
            <?php else: ?>
                <div class="toolbar" style="margin-bottom:var(--space-md);">
                    <p class="text-muted" style="margin:0;font-size:12.5px;">Elige qué pasa con cada actividad. Si la mueves conserva su ID, historial, responsable y %.</p>
                    <button type="button" class="btn btn-secondary" data-move-all>Mover todas al siguiente sprint</button>
                </div>
                <div style="overflow-x:auto;"><table>
                    <thead><tr><th>Actividad</th><th>Estado</th><th>Decisión</th></tr></thead>
                    <tbody>
                    <?php foreach ($pending as $a):
                        $aid = (int) $a['id'];
                        $chosen = $p['decision'][$aid] ?? 'move'; ?>
                        <tr data-decision-row>
                            <td><strong><?= htmlspecialchars($a['name']) ?></strong>
                                <div class="text-muted" style="font-size:12px;"><?= htmlspecialchars($a['project_name']) ?> · <?= htmlspecialchars($a['backlog_description']) ?> · <?= htmlspecialchars($a['developer_name']) ?></div></td>
                            <td><?= Ui::statusBadge('activity_status', $a['status_code']) ?> <span class="text-muted"><?= (int) $a['progress_percent'] ?>%</span></td>
                            <td style="min-width:260px;">
                                <label class="radio-inline"><input type="radio" name="decision[<?= $aid ?>]" value="move" <?= $chosen === 'move' ? 'checked' : '' ?>> Mover al siguiente sprint</label><br>
                                <label class="radio-inline"><input type="radio" name="decision[<?= $aid ?>]" value="return" <?= $chosen === 'return' ? 'checked' : '' ?>> Devolver al backlog (sin sprint)</label><br>
                                <label class="radio-inline"><input type="radio" name="decision[<?= $aid ?>]" value="cancel" <?= $chosen === 'cancel' ? 'checked' : '' ?>> Cancelar</label>
                                <textarea name="reason[<?= $aid ?>]" placeholder="Motivo de la cancelación (obligatorio)" data-reason <?= $chosen === 'cancel' ? '' : 'hidden' ?> style="display:block;width:100%;margin-top:6px;"><?= htmlspecialchars($p['reason'][$aid] ?? '') ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>

                <fieldset data-target-box style="border:1px solid var(--color-border);border-radius:8px;padding:var(--space-md);margin-top:var(--space-lg);">
                    <legend style="padding:0 6px;font-weight:600;">Siguiente sprint</legend>
                    <label class="radio-inline"><input type="radio" name="target_mode" value="new" <?= $targetMode === 'new' ? 'checked' : '' ?>> Crear el siguiente sprint</label>
                    <?php if ($openSprints !== []): ?>
                        &nbsp; <label class="radio-inline"><input type="radio" name="target_mode" value="existing" <?= $targetMode === 'existing' ? 'checked' : '' ?>> Usar un sprint abierto</label>
                    <?php endif; ?>
                    <div class="form-grid" data-target-new style="margin-top:var(--space-md);">
                        <div class="form-group full">
                            <label>Nombre</label>
                            <input type="text" name="next_name" value="<?= htmlspecialchars($p['next_name'] ?? $suggested['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Fecha de inicio</label>
                            <input type="date" name="next_start_date" value="<?= htmlspecialchars($p['next_start_date'] ?? $suggested['start_date']) ?>">
                            <span class="text-muted" style="font-size:11.5px;">Sugerida: el siguiente día hábil después del cierre.</span>
                        </div>
                        <div class="form-group">
                            <label>Fecha de fin</label>
                            <input type="date" name="next_end_date" value="<?= htmlspecialchars($p['next_end_date'] ?? $suggested['end_date']) ?>">
                            <span class="text-muted" style="font-size:11.5px;">Sugerida: los mismos días calendario que este sprint.</span>
                        </div>
                        <div class="form-group full">
                            <label>Objetivo</label>
                            <textarea name="next_goal" data-expandable><?= htmlspecialchars($p['next_goal'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <?php if ($openSprints !== []): ?>
                        <div class="form-group" data-target-existing style="margin-top:var(--space-md);" hidden>
                            <label>Sprint abierto</label>
                            <select name="target_id">
                                <?php foreach ($openSprints as $os): ?>
                                    <option value="<?= (int) $os['id'] ?>" <?= (int) ($p['target_id'] ?? 0) === (int) $os['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sprintName($os)) ?> · <?= Ui::formatDate($os['start_date']) ?> — <?= Ui::formatDate($os['end_date']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </fieldset>
            <?php endif; ?>
            <p class="text-danger" data-step2-error hidden style="margin-top:var(--space-md);"></p>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" data-goto="1">&larr; Atrás</button>
                <button type="button" class="btn" data-goto="3">Siguiente &rarr;</button>
            </div>
        </section>

        <!-- Step 3: confirm -->
        <section data-step="3" hidden>
            <p>Al confirmar se guarda una foto del cierre que no cambia después, y el sprint queda <strong>Cerrado</strong> y de solo lectura.</p>
            <div class="table-scroll"><table>
                <tr><td>Comprometidas al inicio</td><td><?= $scope['committed_count'] ?> actividades · <?= $fmtHours($scope['committed_hours']) ?> h<?= $scope['committed_from_legacy'] ? ' <span class="text-muted">(heredadas: no se registró el compromiso al inicio)</span>' : '' ?></td></tr>
                <tr><td>Alcance agregado</td><td><?= $scope['added_count'] ?> actividades · <?= $fmtHours($scope['added_hours']) ?> h (<?= $scope['added_completed_count'] ?> terminadas)</td></tr>
                <tr><td>Terminadas</td><td><?= count($finished) ?></td></tr>
                <tr><td>Movidas al siguiente sprint</td><td data-count="move">0</td></tr>
                <tr><td>Devueltas al backlog</td><td data-count="return">0</td></tr>
                <tr><td>Canceladas</td><td data-count="cancel">0</td></tr>
                <tr><td><strong>Cumplimiento</strong></td><td><strong><?= $scope['compliance_percent'] ?>%</strong></td></tr>
            </table></div>
            <div class="form-group" style="max-width:360px;margin-top:var(--space-lg);">
                <label>¿Quién cierra el sprint?</label>
                <select name="closed_by" data-closed-by>
                    <option value="">— Elegir —</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (int) ($p['closed_by'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="text-danger" data-step3-error hidden></p>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" data-goto="2">&larr; Atrás</button>
                <button type="submit" class="btn">Cerrar sprint</button>
            </div>
        </section>
    </div>
</form>

<script>
(function () {
    var form = document.querySelector('[data-close-wizard]');
    if (!form) return;
    var targetBox = form.querySelector('[data-target-box]');

    function decisions() {
        var out = { move: 0, return: 0, cancel: 0 };
        form.querySelectorAll('[data-decision-row]').forEach(function (row) {
            var c = row.querySelector('input[type=radio]:checked');
            if (c) out[c.value]++;
        });
        return out;
    }
    function syncRows() {
        form.querySelectorAll('[data-decision-row]').forEach(function (row) {
            var c = row.querySelector('input[type=radio]:checked');
            row.querySelector('[data-reason]').hidden = !(c && c.value === 'cancel');
        });
        if (targetBox) targetBox.hidden = decisions().move === 0;
        var mode = form.querySelector('input[name=target_mode]:checked');
        var useExisting = !!(mode && mode.value === 'existing');
        var fresh = form.querySelector('[data-target-new]');
        var existing = form.querySelector('[data-target-existing]');
        if (fresh) fresh.hidden = useExisting;
        if (existing) existing.hidden = !useExisting;
    }
    // Same rules the server applies (App\Helpers\SprintClose::validate), checked before moving on.
    function step2Error() {
        var missingReason = 0;
        form.querySelectorAll('[data-decision-row]').forEach(function (row) {
            var c = row.querySelector('input[type=radio]:checked');
            if (c && c.value === 'cancel' && row.querySelector('[data-reason]').value.trim() === '') missingReason++;
        });
        if (missingReason > 0) return 'Escribe el motivo de cancelación de ' + missingReason + ' actividad(es).';
        var mode = form.querySelector('input[name=target_mode]:checked');
        if (decisions().move > 0 && mode && mode.value === 'new') {
            var s = form.querySelector('[name=next_start_date]').value, e = form.querySelector('[name=next_end_date]').value;
            if (!s || !e || e < s) return 'Revisa las fechas del siguiente sprint: el fin no puede ser antes del inicio.';
        }
        return '';
    }
    function show(step) {
        form.querySelectorAll('[data-step]').forEach(function (s) { s.hidden = s.getAttribute('data-step') !== String(step); });
        form.querySelectorAll('[data-step-tab]').forEach(function (t) { t.classList.toggle('is-current', t.getAttribute('data-step-tab') === String(step)); });
        if (step === 3) {
            var d = decisions();
            ['move', 'return', 'cancel'].forEach(function (k) { form.querySelector('[data-count=' + k + ']').textContent = d[k]; });
        }
        window.scrollTo(0, 0);
    }

    form.addEventListener('click', function (e) {
        var go = e.target.closest('[data-goto]');
        if (go) {
            var to = Number(go.getAttribute('data-goto'));
            var err = form.querySelector('[data-step2-error]');
            if (to === 3) {
                var msg = step2Error();
                err.textContent = msg;
                err.hidden = msg === '';
                if (msg) return;
            }
            show(to);
            return;
        }
        if (e.target.closest('[data-move-all]')) {
            form.querySelectorAll('[data-decision-row] input[value=move]').forEach(function (r) { r.checked = true; });
            syncRows();
        }
    });
    form.addEventListener('change', syncRows);
    form.addEventListener('submit', function (e) {
        var err = form.querySelector('[data-step3-error]');
        if (!form.querySelector('[data-closed-by]').value) {
            err.textContent = 'Indica quién cierra el sprint.';
            err.hidden = false;
            e.preventDefault();
        }
    });

    syncRows();
    show(Number(form.getAttribute('data-start-step')) || 1);
})();
</script>
