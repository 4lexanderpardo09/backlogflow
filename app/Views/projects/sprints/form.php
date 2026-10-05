<?php

/** @var array|null $sprint @var array $projects @var array<int,array{total:int,open:int}> $backlogCounts @var array $allProjects
 *  @var int[] $selectedProjectIds @var array<int,int> $selectedBacklogs backlog_id => project_id */
$s = $sprint ?? [];
$action = empty($s['id']) ? '/index.php?r=projects/sprints/create' : '/index.php?r=projects/sprints/edit/' . $s['id'];
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/sprints/index">&larr; Sprints</a>
    <div></div>
</div>

<div class="card">
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
                    Haz clic en un proyecto para abrirlo y ver sus backlogs; marca los que entren al sprint o usa
                    «Seleccionar todos». Los backlogs se cargan al abrir cada proyecto.
                </p>
                <div class="picker-toolbar">
                    <input type="search" placeholder="Buscar proyecto o plataforma…" data-picker-search>
                    <label class="picker-filter">Backlogs:
                        <select data-picker-filter>
                            <option value="open" selected>Sin terminar</option>
                            <option value="done">Terminados / cancelados</option>
                            <option value="all">Todos</option>
                        </select>
                    </label>
                    <button type="button" class="link-button" data-picker-collapse-all>Contraer todo</button>
                    <button type="button" class="link-button" data-picker-expand-groups>Expandir plataformas</button>
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
                    $lastGroup = false;
                    foreach ($projects as $p):
                        $group = $p['parent_id'] !== null ? ($projectNames[$p['parent_id']] ?? null) : null;
                        if ($group !== $lastGroup):
                            if ($lastGroup !== false): ?></div><?php endif;
                            $lastGroup = $group; ?>
                            <div data-picker-group>
                            <button type="button" class="picker-group-title" data-picker-group-toggle aria-expanded="true"><span class="picker-chevron">▾</span> <?= htmlspecialchars($group ?? 'Proyectos independientes') ?></button>
                        <?php endif; ?>
                        <div class="picker-project" data-picker-project-row data-project-id="<?= (int) $p['id'] ?>" data-text="<?= htmlspecialchars(mb_strtolower($p['name'] . ' ' . ($group ?? ''))) ?>">
                            <div class="picker-project-head">
                                <input type="checkbox" name="project_ids[]" value="<?= $p['id'] ?>" data-picker-project aria-label="Incluir <?= htmlspecialchars($p['name']) ?> en el sprint" <?= in_array((int) $p['id'], $selectedProjectIds, true) ? 'checked' : '' ?>>
                                <button type="button" class="picker-name" data-picker-toggle aria-expanded="false">
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <span class="text-muted"><?php $c = $backlogCounts[(int) $p['id']] ?? ['total' => 0, 'open' => 0]; ?><?= $c['open'] ?> sin terminar<?= $c['total'] > $c['open'] ? ' · ' . $c['total'] . ' en total' : '' ?> <span class="picker-chevron">▾</span></span>
                                </button>
                            </div>
                            <div class="picker-backlogs" hidden></div>
                        </div>
                    <?php endforeach;
                    if ($lastGroup !== false): ?></div><?php endif; ?>
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
        var filter = root.querySelector('[data-picker-filter]');
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
                        : '<div class="picker-bulk"><button type="button" class="link-button" data-picker-all="1">Seleccionar todos</button>'
                            + ' &middot; <button type="button" class="link-button" data-picker-all="0">Quitar todos</button></div>'
                        + '<span class="picker-none text-muted" style="font-size:12.5px;" hidden>Ningún backlog con este filtro.</span>'
                        + items.map(function (b) {
                            return '<label class="picker-check picker-backlog" data-done="' + (b.done ? '1' : '0') + '" data-text="' + esc(b.description.toLowerCase()) + '">'
                                + '<input type="checkbox" data-picker-backlog value="' + b.id + '"' + (hiddenFor(b.id) ? ' checked' : '') + '>'
                                + '<span>' + esc(b.description) + '</span>'
                                + '<span class="text-muted" style="font-size:12px;">' + esc(b.status) + ' · ' + b.progress + '%</span></label>';
                        }).join('');
                    applyFilter(row);
                    return items;
                })
                .catch(function () {
                    row._loading = null;
                    box(row).innerHTML = '<span class="text-muted" style="font-size:12.5px;">No se pudieron cargar los backlogs. Intenta de nuevo.</span>';
                    return [];
                });
            return row._loading;
        }
        // Shows the backlogs that match the chosen filter; one already in the sprint stays visible so it can't get lost.
        function applyFilter(row) {
            var mode = filter.value;
            var shown = 0;
            row.querySelectorAll('.picker-backlog').forEach(function (label) {
                var done = label.getAttribute('data-done') === '1';
                var keep = label.querySelector('input').checked
                    || mode === 'all' || (mode === 'open' && !done) || (mode === 'done' && done);
                label.hidden = !keep;
                if (keep) shown++;
            });
            var none = box(row).querySelector('.picker-none');
            if (none) none.hidden = shown > 0 || !(row._items && row._items.length);
        }
        function setOpen(row, open) {
            box(row).hidden = !open;
            var toggle = row.querySelector('[data-picker-toggle]');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('is-open', open);
            if (open) load(row);
        }

        root.addEventListener('change', function (e) {
            var t = e.target;
            var row = t.closest('[data-picker-project-row]');
            if (!row) return;
            var pid = row.getAttribute('data-project-id');
            if (t.matches('[data-picker-project]')) {
                // Unchecking a project takes its backlogs out of the sprint; checking it only includes the project.
                if (!t.checked) {
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
        function setGroupOpen(group, open) {
            group.classList.toggle('is-collapsed', !open);
            group.querySelector('[data-picker-group-toggle]').setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        root.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-picker-toggle]');
            if (toggle) {
                var row = toggle.closest('[data-picker-project-row]');
                setOpen(row, box(row).hidden);
                return;
            }
            var groupToggle = e.target.closest('[data-picker-group-toggle]');
            if (groupToggle) {
                var group = groupToggle.closest('[data-picker-group]');
                setGroupOpen(group, group.classList.contains('is-collapsed'));
                return;
            }
            var bulk = e.target.closest('[data-picker-all]');
            if (bulk) {
                var brow = bulk.closest('[data-picker-project-row]');
                var bpid = brow.getAttribute('data-project-id');
                var on = bulk.getAttribute('data-picker-all') === '1';
                // Only the backlogs currently shown (per the filter) are affected.
                box(brow).querySelectorAll('.picker-backlog:not([hidden]) [data-picker-backlog]').forEach(function (cb) {
                    cb.checked = on;
                    on ? selectBacklog(cb.value, bpid) : deselectBacklog(cb.value);
                });
                if (on) projectCheckbox(brow).checked = true;
                updateCount();
                return;
            }
            if (e.target.closest('[data-picker-collapse-all]')) {
                root.querySelectorAll('[data-picker-group]').forEach(function (g) { setGroupOpen(g, false); });
                rows.forEach(function (row) { setOpen(row, false); });
            } else if (e.target.closest('[data-picker-expand-groups]')) {
                root.querySelectorAll('[data-picker-group]').forEach(function (g) { setGroupOpen(g, true); });
            }
        });
        filter.addEventListener('change', function () {
            rows.forEach(function (row) { if (row._items) applyFilter(row); });
        });
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var any = false;
            rows.forEach(function (row) {
                var visible = q === '' || row.getAttribute('data-text').indexOf(q) !== -1;
                row.hidden = !visible;
                if (visible) any = true;
            });
            // A platform title with no module left under it would just be a dangling heading.
            root.querySelectorAll('[data-picker-group]').forEach(function (g) {
                g.hidden = !g.querySelector('[data-picker-project-row]:not([hidden])');
                if (q !== '' && !g.hidden) setGroupOpen(g, true);
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
