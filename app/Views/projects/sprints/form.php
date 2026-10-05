<?php

/** @var array|null $sprint @var array $projects @var array $backlogCounts @var array $allProjects
 *  @var int[] $selectedProjectIds @var array<int,int> $selectedBacklogs backlog_id => project_id */
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
                    Marca un proyecto para meter todos sus backlogs, o ábrelo con «backlogs ▾» y marca solo los que quieras.
                    Los backlogs se cargan al abrir cada proyecto.
                </p>
                <div class="picker-toolbar">
                    <input type="search" placeholder="Buscar proyecto…" data-picker-search>
                    <span class="text-muted" data-picker-count></span>
                </div>
                <div data-picker-selected>
                    <?php foreach ($selectedBacklogs as $backlogId => $projectId): ?>
                        <input type="hidden" name="backlog_ids[]" value="<?= (int) $backlogId ?>" data-project-id="<?= (int) $projectId ?>">
                    <?php endforeach; ?>
                </div>
                <div class="picker-list">
                    <?php
                    $projectNames = array_column($allProjects, 'name', 'id');
                    $lastGroup = null;
                    foreach ($projects as $p):
                        $group = $p['parent_id'] !== null ? ($projectNames[$p['parent_id']] ?? null) : null;
                        if ($group !== $lastGroup): $lastGroup = $group; ?>
                            <div class="picker-group-title"><?= htmlspecialchars($group ?? 'Proyectos independientes') ?></div>
                        <?php endif; ?>
                        <div class="picker-project" data-picker-project-row data-project-id="<?= (int) $p['id'] ?>" data-text="<?= htmlspecialchars(mb_strtolower($p['name'])) ?>">
                            <div class="picker-project-head">
                                <label class="picker-check">
                                    <input type="checkbox" name="project_ids[]" value="<?= $p['id'] ?>" data-picker-project <?= in_array((int) $p['id'], $selectedProjectIds, true) ? 'checked' : '' ?>>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                </label>
                                <button type="button" class="link-button" data-picker-toggle aria-expanded="false"><?= (int) ($backlogCounts[(int) $p['id']] ?? 0) ?> backlogs ▾</button>
                            </div>
                            <div class="picker-backlogs" hidden></div>
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

    function esc(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function init(root) {
        var search = root.querySelector('[data-picker-search]');
        var count = root.querySelector('[data-picker-count]');
        var empty = root.querySelector('[data-picker-empty]');
        var store = root.querySelector('[data-picker-selected]');
        var rows = Array.prototype.slice.call(root.querySelectorAll('[data-picker-project-row]'));

        function hiddenFor(id) { return store.querySelector('input[value="' + id + '"]'); }
        function selectBacklog(id, projectId) {
            if (hiddenFor(id)) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'backlog_ids[]';
            input.value = id;
            input.setAttribute('data-project-id', projectId);
            store.appendChild(input);
        }
        function deselectBacklog(id) {
            var input = hiddenFor(id);
            if (input) input.remove();
        }
        function updateCount() {
            var p = root.querySelectorAll('[data-picker-project]:checked').length;
            var b = store.querySelectorAll('input').length;
            count.textContent = p + ' proyectos · ' + b + ' backlogs seleccionados';
        }
        function projectCheckbox(row) { return row.querySelector('[data-picker-project]'); }
        function box(row) { return row.querySelector('.picker-backlogs'); }

        // Fetches the project's backlogs the first time it is opened; resolves with the rows.
        function load(row) {
            if (row._loading) return row._loading;
            var pid = row.getAttribute('data-project-id');
            box(row).innerHTML = '<span class="text-muted" style="font-size:12.5px;">Cargando…</span>';
            row._loading = fetch('/index.php?r=projects/sprints/backlogs/' + pid, { headers: { Accept: 'application/json' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (items) {
                    row._items = items;
                    box(row).innerHTML = items.length === 0
                        ? '<span class="text-muted" style="font-size:12.5px;">Este proyecto no tiene backlogs.</span>'
                        : items.map(function (b) {
                            return '<label class="picker-check picker-backlog" data-text="' + esc(b.description.toLowerCase()) + '">'
                                + '<input type="checkbox" data-picker-backlog value="' + b.id + '"' + (hiddenFor(b.id) ? ' checked' : '') + '>'
                                + '<span>' + esc(b.description) + '</span>'
                                + '<span class="text-muted" style="font-size:12px;">' + esc(b.status) + ' · ' + b.progress + '%</span></label>';
                        }).join('');
                    return items;
                })
                .catch(function () {
                    row._loading = null;
                    box(row).innerHTML = '<span class="text-muted" style="font-size:12.5px;">No se pudieron cargar los backlogs. Intenta de nuevo.</span>';
                    return [];
                });
            return row._loading;
        }
        function setOpen(row, open) {
            box(row).hidden = !open;
            row.querySelector('[data-picker-toggle]').setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) load(row);
        }

        root.addEventListener('change', function (e) {
            var t = e.target;
            var row = t.closest('[data-picker-project-row]');
            if (!row) return;
            var pid = row.getAttribute('data-project-id');
            if (t.matches('[data-picker-project]')) {
                if (t.checked) {
                    setOpen(row, true);
                    load(row).then(function (items) {
                        items.forEach(function (b) { selectBacklog(b.id, pid); });
                        box(row).querySelectorAll('[data-picker-backlog]').forEach(function (cb) { cb.checked = true; });
                        updateCount();
                    });
                } else {
                    store.querySelectorAll('input[data-project-id="' + pid + '"]').forEach(function (i) { i.remove(); });
                    box(row).querySelectorAll('[data-picker-backlog]').forEach(function (cb) { cb.checked = false; });
                }
            } else if (t.matches('[data-picker-backlog]')) {
                if (t.checked) {
                    selectBacklog(t.value, pid);
                    projectCheckbox(row).checked = true;
                } else {
                    deselectBacklog(t.value);
                }
            }
            updateCount();
        });
        root.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-picker-toggle]');
            if (toggle) {
                var row = toggle.closest('[data-picker-project-row]');
                setOpen(row, box(row).hidden);
            }
        });
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var any = false;
            rows.forEach(function (row) {
                var visible = q === '' || row.getAttribute('data-text').indexOf(q) !== -1;
                row.hidden = !visible;
                if (visible) any = true;
            });
            empty.hidden = any;
        });
        search.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

        // Projects that already have backlogs in the sprint open on their own.
        rows.forEach(function (row) {
            if (store.querySelector('input[data-project-id="' + row.getAttribute('data-project-id') + '"]')) setOpen(row, true);
        });
        updateCount();
    }

    function initAll() { document.querySelectorAll('[data-sprint-picker]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
</script>
