<?php
/** @var string $action @var array|null $checkin @var array $weeks @var int $defaultWeek @var array $developers @var string|null $note */
$c = $checkin ?? [];
$selectedWeek = (int) ($c['week_number'] ?? $defaultWeek);
?>
<div class="card" style="max-width:700px;">
    <form method="post" action="<?= htmlspecialchars($action) ?>" data-checkin-form>
        <?php if (!empty($note)): ?>
            <p class="text-muted" style="margin-top:0;font-size:13px;"><?= htmlspecialchars($note) ?></p>
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Semana del sprint</label>
                <select name="week_number" data-checkin-week>
                    <?php foreach ($weeks as $w): ?>
                        <option value="<?= $w['number'] ?>" data-start="<?= $w['start'] ?>" data-end="<?= $w['end'] ?>" <?= $w['number'] === $selectedWeek ? 'selected' : '' ?>>
                            Semana <?= $w['number'] ?> · <?= date('d/m', strtotime($w['start'])) ?> – <?= date('d/m', strtotime($w['end'])) ?><?= $w['is_current'] ? ' (esta semana)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha del seguimiento</label>
                <input type="date" name="checkin_date" required data-checkin-date value="<?= htmlspecialchars($c['checkin_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group full">
                <label>Registrado por</label>
                <select name="registered_by">
                    <option value="">Selecciona…</option>
                    <?php foreach ($developers as $d): ?>
                        <?php if ($d['status'] !== 'active' && (int) $d['id'] !== (int) ($c['registered_by'] ?? 0)) continue; ?>
                        <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) ($c['registered_by'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>¿Cómo va el sprint?</label>
                <textarea name="summary" required placeholder="Qué se avanzó, qué falta, cómo se ve el cierre"><?= htmlspecialchars($c['summary'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Bloqueos o riesgos</label>
                <textarea name="blockers"><?= htmlspecialchars($c['blockers'] ?? '') ?></textarea>
            </div>
            <div class="form-group full">
                <label>Próximos pasos</label>
                <textarea name="next_steps"><?= htmlspecialchars($c['next_steps'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar seguimiento</button>
            <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
        </div>
    </form>
</div>
