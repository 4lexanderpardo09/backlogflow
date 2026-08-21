<?php
use App\Helpers\Labels;

/** @var array|null $backlogItem @var array $projects @var array $sprints @var array $developers @var array $types @var array $priorities @var array $statuses @var array $collaborators */
$b = $backlogItem ?? [];
$collaboratorIds = array_column($collaborators ?? [], 'id');
$isNew = empty($b['id']);
$action = $isNew ? '/index.php?r=projects/backlog/create' : '/index.php?r=projects/backlog/edit/' . $b['id'];
$fromNoteId = $fromNoteId ?? 0;
?>
<div class="card" style="max-width:820px;">
    <form method="post" action="<?= $action ?>">
        <?php if ($fromNoteId > 0): ?><input type="hidden" name="from_note" value="<?= $fromNoteId ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group full">
                <label>Descripción del backlog</label>
                <input type="text" name="description" required value="<?= htmlspecialchars($b['description'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Proyecto</label>
                <select name="project_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int) ($b['project_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Desarrollador</label>
                <select name="developer_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int) ($b['developer_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sprint</label>
                <select name="sprint_id">
                    <option value="">Sin asignar</option>
                    <?php foreach ($sprints as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) ($b['sprint_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['project_name']) ?> — Sprint #<?= (int) $s['sequence_number'] ?> (<?= htmlspecialchars($s['start_date']) ?> a <?= htmlspecialchars($s['end_date']) ?>)</option>
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
                <label>Tipo</label>
                <select name="type_id">
                    <option value="">Sin definir</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (int) ($b['type_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('backlog_type', $t['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioridad</label>
                <select name="priority_id" required>
                    <?php foreach ($priorities as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= (int) ($b['priority_id'] ?? 0) === (int) $pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status_id" required>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) ($b['status_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('backlog_status', $s['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de creación</label>
                <input type="date" name="created_date" value="<?= htmlspecialchars($b['created_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Fecha objetivo</label>
                <input type="date" name="target_date" value="<?= htmlspecialchars($b['target_date'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($b['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:#6b7280;font-size:12.5px;">El % de avance se calcula automáticamente a partir de las actividades de este backlog.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/backlog/index">Cancelar</button>
        </div>
    </form>
</div>
