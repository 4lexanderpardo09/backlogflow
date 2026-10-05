<?php

/** @var array|null $sprint @var array $projects @var array $backlogItems @var array $allProjects
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
            <div class="form-group full" data-sprint-picker>
                <label>Proyectos y backlogs del sprint</label>
                <p class="text-muted" style="margin:0 0 var(--space-sm);font-size:12.5px;">
                    Marca un proyecto para meter todos sus backlogs, o abre el proyecto y marca solo los que quieras.
                </p>
                <div class="picker-toolbar">
                    <input type="search" placeholder="Buscar proyecto o backlog…" data-picker-search>
                    <span class="text-muted" data-picker-count></span>
                </div>
                <div class="picker-list">
                    <?php
                    $projectNames = array_column($allProjects, 'name', 'id');
                    $backlogsByProject = [];
                    foreach ($backlogItems as $b) {
                        $backlogsByProject[(int) $b['project_id']][] = $b;
                    }
                    $lastGroup = null;
                    foreach ($projects as $p):
                        $group = $p['parent_id'] !== null ? ($projectNames[$p['parent_id']] ?? null) : null;
                        if ($group !== $lastGroup): $lastGroup = $group; ?>
                            <div class="picker-group-title"><?= htmlspecialchars($group ?? 'Proyectos independientes') ?></div>
                        <?php endif;
                        $projectBacklogs = $backlogsByProject[(int) $p['id']] ?? []; ?>
                        <div class="picker-project" data-picker-project-row data-text="<?= htmlspecialchars(mb_strtolower($p['name'])) ?>">
                            <div class="picker-project-head">
                                <label class="picker-check">
                                    <input type="checkbox" name="project_ids[]" value="<?= $p['id'] ?>" data-picker-project <?= in_array((int) $p['id'], $selectedProjectIds, true) ? 'checked' : '' ?>>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                </label>
                                <button type="button" class="link-button" data-picker-toggle aria-expanded="false"><?= count($projectBacklogs) ?> backlogs ▾</button>
                            </div>
                            <div class="picker-backlogs" hidden>
                                <?php foreach ($projectBacklogs as $b): ?>
                                    <label class="picker-check picker-backlog" data-text="<?= htmlspecialchars(mb_strtolower($b['description'])) ?>">
                                        <input type="checkbox" name="backlog_ids[]" value="<?= $b['id'] ?>" data-picker-backlog <?= in_array((int) $b['id'], $selectedBacklogIds, true) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($b['description']) ?></span>
                                        <span class="text-muted" style="font-size:12px;"><?= \App\Helpers\Labels::get('backlog_status', $b['status_code']) ?> · <?= round((float) $b['progress_percent']) ?>%</span>
                                    </label>
                                <?php endforeach; ?>
                                <?php if ($projectBacklogs === []): ?><span class="text-muted" style="font-size:12.5px;">Este proyecto no tiene backlogs.</span><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="empty-state" data-picker-empty hidden>Nada coincide con la búsqueda.</p>
                </div>
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

    function init(root) {
        var search = root.querySelector('[data-picker-search]');
        var count = root.querySelector('[data-picker-count]');
        var empty = root.querySelector('[data-picker-empty]');
        var rows = Array.prototype.slice.call(root.querySelectorAll('[data-picker-project-row]'));

        function backlogsOf(row) { return row.querySelectorAll('[data-picker-backlog]'); }
        function setOpen(row, open) {
            row.querySelector('.picker-backlogs').hidden = !open;
            row.querySelector('[data-picker-toggle]').setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        function updateCount() {
            var p = root.querySelectorAll('[data-picker-project]:checked').length;
            var b = root.querySelectorAll('[data-picker-backlog]:checked').length;
            count.textContent = p + ' proyectos · ' + b + ' backlogs seleccionados';
        }
        function applySearch() {
            var q = search.value.trim().toLowerCase();
            var any = false;
            rows.forEach(function (row) {
                var projectMatch = q === '' || row.getAttribute('data-text').indexOf(q) !== -1;
                var backlogMatch = false;
                row.querySelectorAll('.picker-backlog').forEach(function (bl) {
                    var m = q === '' || projectMatch || bl.getAttribute('data-text').indexOf(q) !== -1;
                    bl.hidden = !m;
                    if (m && q !== '' && !projectMatch) backlogMatch = true;
                });
                var visible = projectMatch || backlogMatch;
                row.hidden = !visible;
                if (visible) any = true;
                if (q !== '') setOpen(row, true);
            });
            empty.hidden = any;
        }

        root.addEventListener('change', function (e) {
            var t = e.target;
            var row = t.closest('[data-picker-project-row]');
            if (t.matches('[data-picker-project]')) {
                backlogsOf(row).forEach(function (cb) { cb.checked = t.checked; });
                if (t.checked) setOpen(row, true);
            } else if (t.matches('[data-picker-backlog]') && t.checked) {
                row.querySelector('[data-picker-project]').checked = true;
            }
            updateCount();
        });
        root.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-picker-toggle]');
            if (toggle) {
                var row = toggle.closest('[data-picker-project-row]');
                setOpen(row, row.querySelector('.picker-backlogs').hidden);
            }
        });
        search.addEventListener('input', applySearch);
        search.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

        rows.forEach(function (row) {
            if (row.querySelector('[data-picker-backlog]:checked')) setOpen(row, true);
        });
        updateCount();
    }

    function initAll() { document.querySelectorAll('[data-sprint-picker]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
</script>
