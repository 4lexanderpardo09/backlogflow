<?php
/** @var array $application @var string $priority @var array|null $entry */
$e = $entry ?? [];
?>
<div class="card" style="max-width:700px;">
    <p class="card-title"><?= htmlspecialchars($application['name']) ?> — Tiempos de respuesta<?= $priority !== '' ? ' ' . htmlspecialchars($priority) : '' ?></p>
    <form method="post" action="/index.php?r=sla/incident-sla/edit/<?= $application['id'] ?><?= $priority !== '' ? '&priority=' . urlencode($priority) : '' ?>">
        <?php if (isset($e['id'])): ?>
            <input type="hidden" name="row_id" value="<?= (int) $e['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Prioridad (P1-P4)</label>
                <input type="text" name="priority" maxlength="4" required value="<?= htmlspecialchars($e['priority'] ?? $priority) ?>">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <input type="text" name="description" value="<?= htmlspecialchars($e['description'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tiempo de respuesta (min)</label>
                <input type="number" min="0" name="response_time_minutes" required value="<?= htmlspecialchars((string) ($e['response_time_minutes'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label>Tiempo de solución (min)</label>
                <input type="number" min="0" name="resolution_time_minutes" required value="<?= htmlspecialchars((string) ($e['resolution_time_minutes'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/applications/view/<?= $application['id'] ?>">Cancelar</a>
        </div>
    </form>
</div>
