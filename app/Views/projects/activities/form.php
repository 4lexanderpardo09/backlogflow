<?php
use App\Helpers\Labels;

/** @var array|null $activity @var array $backlogItems @var array $developers @var array $types
 *  @var array $priorities @var array $statuses @var array $dependencyOptions */
$a = $activity ?? [];
$action = $activity === null ? '/index.php?r=projects/activities/create' : '/index.php?r=projects/activities/edit/' . $activity['id'];
?>
<div class="card" style="max-width:860px;">
    <form method="post" action="<?= $action ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label>Actividad</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($a['name'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description"><?= htmlspecialchars($a['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Backlog</label>
                <select name="backlog_item_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($backlogItems as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= (int) ($a['backlog_item_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Desarrollador</label>
                <select name="developer_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int) ($a['developer_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="type_id">
                    <option value="">Sin definir</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int) ($a['type_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('activity_type', $t['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad</label>
                <select name="priority_id" required>
                    <?php foreach ($priorities as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= (int) ($a['priority_id'] ?? 0) === (int) $pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado (manual)</label>
                <select name="status_id" required>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) ($a['status_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('activity_status', $s['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>% de avance (0-100)</label>
                <input type="number" name="progress_percent" min="0" max="100" required value="<?= htmlspecialchars((string) ($a['progress_percent'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label>Depende de</label>
                <select name="depends_on_activity_id">
                    <option value="">Ninguna</option>
                    <?php foreach ($dependencyOptions as $dep): ?>
                        <option value="<?= $dep['id'] ?>" <?= (int) ($a['depends_on_activity_id'] ?? 0) === (int) $dep['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($a['start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha límite</label>
                <input type="date" name="due_date" value="<?= htmlspecialchars($a['due_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de finalización</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($a['end_date'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($a['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:#6b7280;font-size:12.5px;">Esta es la única pantalla donde el % de avance se ingresa manualmente; todo lo demás (backlog, proyecto, dashboard) se recalcula solo.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/activities/index">Cancelar</button>
        </div>
    </form>
</div>
