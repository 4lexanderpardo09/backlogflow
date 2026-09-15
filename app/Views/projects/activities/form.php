<?php
use App\Helpers\Labels;

/** @var array|null $activity @var array $backlogItems @var array $developers @var array $projects @var array $types
 *  @var array $priorities @var array $statuses @var array $dependencyOptions @var array $collaborators */
$a = $activity ?? [];
$collaboratorIds = array_column($collaborators ?? [], 'id');
$action = $activity === null ? '/index.php?r=projects/activities/create' : '/index.php?r=projects/activities/edit/' . $activity['id'];
?>
<div class="card" style="max-width:860px;">
    <form method="post" action="<?= $action ?>" data-activity-filter>
        <div class="form-grid">
            <div class="form-group full">
                <label>Actividad</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($a['name'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Descripción</label>
                <textarea name="description" data-expandable><?= htmlspecialchars($a['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Desarrollador</label>
                <select name="developer_id" required data-developer-filter>
                    <option value="">Seleccione...</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (int) ($a['developer_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Proyecto <span class="text-muted" style="font-weight:400;">(para filtrar el backlog)</span></label>
                <select data-project-filter aria-label="Filtrar backlog por proyecto">
                    <option value="">Todos los proyectos</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Backlog</label>
                <select name="backlog_item_id" required data-developer-filter-target data-project-filter-target>
                    <option value="">Seleccione...</option>
                    <?php foreach ($backlogItems as $b): ?>
                        <option value="<?= $b['id'] ?>" data-developer-id="<?= (int) $b['developer_id'] ?>" data-project-id="<?= (int) $b['project_id'] ?>" <?= (int) ($a['backlog_item_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['description']) ?></option>
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
                <select name="status_id" required data-status-select>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" data-code="<?= htmlspecialchars($s['code']) ?>" <?= (int) ($a['status_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('activity_status', $s['code'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>% de avance (0-100)</label>
                <input type="number" name="progress_percent" min="0" max="100" required data-progress-input value="<?= htmlspecialchars((string) ($a['progress_percent'] ?? 0)) ?>">
                <span class="text-muted" style="font-size:11.5px;" data-progress-hint></span>
            </div>
            <div class="form-group">
                <label>Depende de</label>
                <select name="depends_on_activity_id" data-developer-filter-target>
                    <option value="">Ninguna</option>
                    <?php foreach ($dependencyOptions as $dep): ?>
                        <option value="<?= $dep['id'] ?>" data-developer-id="<?= (int) $dep['developer_id'] ?>" <?= (int) ($a['depends_on_activity_id'] ?? 0) === (int) $dep['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['name']) ?></option>
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
                <textarea name="notes" data-expandable><?= htmlspecialchars($a['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:#6b7280;font-size:12.5px;">El % de avance sólo se ingresa cuando el estado es <strong>En progreso</strong>: <strong>Terminada</strong> lo fija en 100 y los demás estados en 0. Todo lo demás (backlog, proyecto, dashboard) se recalcula solo.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/activities/index">Cancelar</button>
        </div>
    </form>
</div>
<script>
(function () {
    if (window.__bfActivityFilterBound) return;
    window.__bfActivityFilterBound = true;

    function optMatches(opt, attr, value) {
        if (!opt.hasAttribute(attr)) return true;   // dimension not applicable to this option
        return value === '' || opt.getAttribute(attr) === value;
    }

    function filterBacklog(form, isInit) {
        var devSelect = form.querySelector('[data-developer-filter]');
        var projSelect = form.querySelector('[data-project-filter]');
        var devId = devSelect ? devSelect.value : '';
        var projId = projSelect ? projSelect.value : '';

        form.querySelectorAll('[data-developer-filter-target], [data-project-filter-target]').forEach(function (targetSelect) {
            var current = targetSelect.value;
            var isBacklog = targetSelect.hasAttribute('data-project-filter-target');

            Array.prototype.slice.call(targetSelect.options).forEach(function (opt) {
                if (opt.value === '') { opt.hidden = false; return; }
                var match = optMatches(opt, 'data-developer-id', devId)
                    && (!isBacklog || optMatches(opt, 'data-project-id', projId));
                // On first load keep the already-selected option visible even
                // if it doesn't match, so editing never drops an existing value.
                var keepSelected = isInit && opt.value === current;
                opt.hidden = !(match || keepSelected);
            });

            if (!isInit && current !== '') {
                var selected = targetSelect.querySelector('option[value="' + current + '"]');
                if (selected && selected.hidden) targetSelect.value = '';
            }
        });
    }

    function syncProgressToStatus(form) {
        var statusSelect = form.querySelector('[data-status-select]');
        var input = form.querySelector('[data-progress-input]');
        var hint = form.querySelector('[data-progress-hint]');
        if (!statusSelect || !input) return;

        var opt = statusSelect.selectedOptions[0];
        var code = opt ? opt.getAttribute('data-code') : '';

        if (code === 'completed') {
            input.value = 100;
            input.readOnly = true;
            if (hint) hint.textContent = 'Fijo en 100 por estado Terminada';
        } else if (code === 'in_progress') {
            input.readOnly = false;
            if (hint) hint.textContent = '';
        } else {
            input.value = 0;
            input.readOnly = true;
            if (hint) hint.textContent = 'Bloqueado en 0 (sólo editable con estado En progreso)';
        }
    }

    function initForm(form) {
        filterBacklog(form, true);
        // On edit, reflect the current backlog's project in the filter select.
        var projSelect = form.querySelector('[data-project-filter]');
        var backlog = form.querySelector('[data-project-filter-target]');
        if (projSelect && backlog && backlog.value) {
            var opt = backlog.querySelector('option[value="' + backlog.value + '"]');
            if (opt && opt.getAttribute('data-project-id')) projSelect.value = opt.getAttribute('data-project-id');
        }
        syncProgressToStatus(form);
    }

    // Exposed so the activity list can initialise forms it loads on demand.
    window.bfInitActivityForm = initForm;

    function initAll() {
        document.querySelectorAll('form[data-activity-filter]').forEach(initForm);
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.matches) return;
        var form = t.closest('form[data-activity-filter]');
        if (!form) return;
        if (t.matches('[data-developer-filter], [data-project-filter]')) filterBacklog(form, false);
        if (t.matches('[data-status-select]')) syncProgressToStatus(form);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
