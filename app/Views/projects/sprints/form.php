<?php

/** @var array|null $sprint @var array $projects @var array $backlogItems
 *  @var int[] $selectedProjectIds @var int[] $selectedBacklogIds */
$s = $sprint ?? [];
$action = empty($s['id']) ? '/index.php?r=projects/sprints/create' : '/index.php?r=projects/sprints/edit/' . $s['id'];
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">&larr; Sprints</a>
    <div></div>
</div>

<div class="card" style="max-width:820px;">
    <form method="post" action="<?= $action ?>" data-sprint-form>
        <div class="form-grid">
            <div class="form-group full">
                <label>Nombre del sprint</label>
                <input type="text" name="name" value="<?= htmlspecialchars($s['name'] ?? '') ?>" placeholder="Ej. Sprint comercial — septiembre">
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="start_date" required value="<?= htmlspecialchars($s['start_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Duración (semanas)</label>
                <input type="number" name="duration_weeks" min="1" max="52" required value="<?= htmlspecialchars((string) ($s['duration_weeks'] ?? 2)) ?>">
            </div>
            <div class="form-group">
                <label>Dueño de proceso</label>
                <input type="text" name="process_owner" value="<?= htmlspecialchars($s['process_owner'] ?? '') ?>" placeholder="Persona o área">
            </div>
            <div class="form-group">
                <label>Proyectos hijos del sprint</label>
                <select name="project_ids[]" multiple size="6" data-sprint-projects>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= in_array((int) $p['id'], $selectedProjectIds, true) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Backlogs del sprint <span class="text-muted" style="font-weight:400;">(de los proyectos elegidos)</span></label>
                <select name="backlog_ids[]" multiple size="6" data-sprint-backlogs>
                    <?php foreach ($backlogItems as $b): ?>
                        <option value="<?= $b['id'] ?>" data-project-id="<?= (int) $b['project_id'] ?>" <?= in_array((int) $b['id'], $selectedBacklogIds, true) ? 'selected' : '' ?>><?= htmlspecialchars($b['project_name']) ?> — <?= htmlspecialchars($b['description']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Notas</label>
                <textarea name="notes" data-expandable><?= htmlspecialchars($s['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <p style="color:var(--color-muted-foreground);font-size:12.5px;">La fecha de fin se calcula sola: inicio + (semanas × 7 días). El % de cumplimiento se recalcula desde las actividades de los backlogs elegidos.</p>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">Cancelar</a>
        </div>
    </form>
</div>
<script>
(function () {
    if (window.__bfSprintFormBound) return;
    window.__bfSprintFormBound = true;

    function selectedProjectIds(form) {
        var sel = form.querySelector('[data-sprint-projects]');
        if (!sel) return [];
        return Array.prototype.filter.call(sel.options, function (o) { return o.selected; })
            .map(function (o) { return o.value; });
    }

    function filterBacklogs(form) {
        var backlogs = form.querySelector('[data-sprint-backlogs]');
        if (!backlogs) return;
        var picked = selectedProjectIds(form);
        Array.prototype.forEach.call(backlogs.options, function (opt) {
            var pid = opt.getAttribute('data-project-id');
            var visible = picked.length === 0 || picked.indexOf(pid) !== -1 || opt.selected;
            opt.hidden = !visible;
            if (!visible) opt.selected = false;
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches && e.target.matches('[data-sprint-projects]')) {
            var form = e.target.closest('form[data-sprint-form]');
            if (form) filterBacklogs(form);
        }
    });

    function initAll() { document.querySelectorAll('form[data-sprint-form]').forEach(filterBacklogs); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
</script>
