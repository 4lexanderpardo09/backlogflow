<?php
use App\Helpers\Labels;

/** @var array|null $project @var array $developers @var array $priorities @var array $statuses @var array $collaborators */
$p = $project ?? [];
$collaboratorIds = array_column($collaborators ?? [], 'id');
$action = $project === null ? '/index.php?r=projects/projects/create' : '/index.php?r=projects/projects/edit/' . $project['id'];
?>
<div class="card" style="max-width:820px;">
    <form method="post" action="<?= $action ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label>Nombre del proyecto</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($p['name'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Desarrollador responsable</label>
                <select name="developer_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int) ($p['developer_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Colaboradores adicionales</label>
                <select name="collaborator_ids[]" multiple size="4">
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= in_array((int) $d['id'], $collaboratorIds, true) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad</label>
                <select name="priority_id" required>
                    <?php foreach ($priorities as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= (int) ($p['priority_id'] ?? 0) === (int) $pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status_id" required>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) ($p['status_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('project_status', $s['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Duración del sprint (días)</label>
                <input type="number" name="sprint_duration_days" min="1" value="<?= htmlspecialchars((string) ($p['sprint_duration_days'] ?? 8)) ?>">
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($p['start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha estimada de finalización</label>
                <input type="date" name="estimated_end_date" value="<?= htmlspecialchars($p['estimated_end_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha real de finalización</label>
                <input type="date" name="actual_end_date" value="<?= htmlspecialchars($p['actual_end_date'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($p['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:#6b7280;font-size:12.5px;">El % de avance, los días restantes y el semáforo se calculan automáticamente a partir de las actividades del proyecto — no se ingresan aquí.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/projects/index">Cancelar</button>
        </div>
    </form>
</div>
