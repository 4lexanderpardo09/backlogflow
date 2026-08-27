<?php
use App\Helpers\Labels;

/** @var array|null $project @var array $developers @var array $priorities @var array $statuses
 *  @var array $collaborators @var array $platforms */
$p = $project ?? [];
$collaboratorIds = array_column($collaborators ?? [], 'id');
$action = empty($p['id']) ? '/index.php?r=projects/projects/create' : '/index.php?r=projects/projects/edit/' . $p['id'];
$isPlatform = (int) ($p['is_platform'] ?? 0) === 1;
// A platform can't be listed as its own parent option.
$parentOptions = array_values(array_filter($platforms ?? [], fn ($pl) => (int) $pl['id'] !== (int) ($p['id'] ?? 0)));
?>
<div class="card" style="max-width:820px;">
    <form method="post" action="<?= $action ?>" data-project-form>
        <div class="form-grid">
            <div class="form-group full">
                <label>Nombre del proyecto</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($p['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tipo de proyecto</label>
                <select name="is_platform" data-project-kind>
                    <option value="0" <?= $isPlatform ? '' : 'selected' ?>>Proyecto / subproyecto</option>
                    <option value="1" <?= $isPlatform ? 'selected' : '' ?>>Plataforma (proyecto padre)</option>
                </select>
            </div>
            <div class="form-group" data-parent-field>
                <label>Pertenece a la plataforma</label>
                <select name="parent_id">
                    <option value="">Ninguna (proyecto independiente)</option>
                    <?php foreach ($parentOptions as $pl): ?>
                        <option value="<?= $pl['id'] ?>" <?= (int) ($p['parent_id'] ?? 0) === (int) $pl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description" data-expandable><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
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
            <div class="form-group" data-enddate-field>
                <label>Fecha estimada de finalización</label>
                <input type="date" name="estimated_end_date" value="<?= htmlspecialchars($p['estimated_end_date'] ?? '') ?>">
            </div>
            <div class="form-group" data-enddate-field>
                <label>Fecha real de finalización</label>
                <input type="date" name="actual_end_date" value="<?= htmlspecialchars($p['actual_end_date'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes" data-expandable><?= htmlspecialchars($p['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:#6b7280;font-size:12.5px;">Una <strong>plataforma</strong> (proyecto padre) no tiene fecha de fin: su avance y semáforo se agregan desde sus subproyectos. El % de avance, los días restantes y el semáforo se calculan siempre a partir de las actividades — no se ingresan aquí.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/projects/index">Cancelar</button>
        </div>
    </form>
</div>
<script>
(function () {
    if (window.__bfProjectFormBound) return;
    window.__bfProjectFormBound = true;

    function sync(form) {
        var kind = form.querySelector('[data-project-kind]');
        if (!kind) return;
        var isPlatform = kind.value === '1';
        form.querySelectorAll('[data-parent-field], [data-enddate-field]').forEach(function (el) {
            el.style.display = isPlatform ? 'none' : '';
            var field = el.querySelector('input, select');
            if (field && isPlatform) field.value = '';
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches && e.target.matches('[data-project-kind]')) {
            var form = e.target.closest('form[data-project-form]');
            if (form) sync(form);
        }
    });

    function initAll() { document.querySelectorAll('form[data-project-form]').forEach(sync); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
</script>
