<?php
/** @var array $application @var int $level @var array|null $entry */
$e = $entry ?? [];
?>
<div class="card" style="max-width:700px;">
    <p class="card-title"><?= htmlspecialchars($application['name']) ?> — Nivel <?= $level ?></p>
    <form method="post" action="/index.php?r=sla/support-matrix/edit/<?= $application['id'] ?>&level=<?= $level ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Responsable</label>
                <input type="text" name="responsible" value="<?= htmlspecialchars($e['responsible'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Canal</label>
                <input type="text" name="channel" value="<?= htmlspecialchars($e['channel'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Horario</label>
                <input type="text" name="hours" value="<?= htmlspecialchars($e['hours'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tiempo máximo antes de escalar</label>
                <input type="text" name="max_escalation_time" value="<?= htmlspecialchars($e['max_escalation_time'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($e['contact'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" value="<?= htmlspecialchars($e['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($e['phone'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Procedimiento</label>
                <textarea name="procedure_notes"><?= htmlspecialchars($e['procedure_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/support-matrix/index">Cancelar</a>
        </div>
    </form>
</div>
