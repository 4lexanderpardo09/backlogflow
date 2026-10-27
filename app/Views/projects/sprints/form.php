<?php

/** @var array|null $sprint @var array $projects @var array<int,array{total:int,open:int}> $backlogCounts @var array $allProjects
 *  @var int[] $selectedProjectIds @var array<int,array{project_id:int,backlog_id:int}> $selectedActivities activity_id => where it lives
 *  @var array $developers */
$s = $sprint ?? [];
$startDefault = $s['start_date'] ?? date('Y-m-d');
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
            <div class="form-group full">
                <label>Objetivo</label>
                <textarea name="goal" data-expandable placeholder="Qué se quiere lograr en este sprint"><?= htmlspecialchars($s['goal'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="start_date" required value="<?= htmlspecialchars($startDefault) ?>" data-sprint-start>
            </div>
            <div class="form-group">
                <label>Fecha de fin</label>
                <input type="date" name="end_date" required value="<?= htmlspecialchars($s['end_date'] ?? date('Y-m-d', strtotime($startDefault . ' +14 days'))) ?>" min="<?= htmlspecialchars($startDefault) ?>" data-sprint-end>
            </div>
            <div class="form-group">
                <label>Dueño de proceso</label>
                <input type="text" name="process_owner" value="<?= htmlspecialchars($s['process_owner'] ?? '') ?>" placeholder="Persona o área">
            </div>
            <div class="form-group">
                <label>¿Quién hace el cambio?</label>
                <select name="changed_by">
                    <option value="">— Elegir —</option>
                    <?php foreach ($developers as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="text-muted" style="font-size:11.5px;">Queda registrado en las actividades que agregues (alcance agregado si el sprint ya empezó).</span>
            </div>
            <div class="form-group full" data-sprint-picker>
                <label>Proyectos y actividades del sprint</label>
                <p class="text-muted" style="margin:0 0 var(--space-sm);font-size:12.5px;">
                    Haz clic en un proyecto para abrirlo y ver sus backlogs y actividades. Marcar un backlog incluye
                    todas sus actividades sin terminar; también puedes marcar o quitar actividades una por una.
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
                <div data-picker-selected data-sprint-id="<?= (int) ($s['id'] ?? 0) ?>">
                    <?php foreach ($selectedActivities as $activityId => $where): ?>
                        <input type="hidden" name="activity_ids[]" value="<?= (int) $activityId ?>" data-project-id="<?= (int) $where['project_id'] ?>" data-backlog-id="<?= (int) $where['backlog_id'] ?>">
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
        <p style="color:var(--color-muted-foreground);font-size:12.5px;">El avance se recalcula desde las actividades elegidas, ponderado por sus horas estimadas. Lo que esté en el sprint hasta el día de inicio queda comprometido con sus horas de ese momento (base del cumplimiento); lo que se agregue después queda como alcance agregado, con fecha y quién lo agregó.</p>
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

        var sprintId = store.getAttribute('data-sprint-id');
        function hiddenFor(id) { return store.querySelector('input[value="' + id + '"]'); }
        function selectActivity(id, projectId, backlogId) {
            if (hiddenFor(id)) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'activity_ids[]';
            input.value = id;
            input.setAttribute('data-project-id', projectId);
            input.setAttribute('data-backlog-id', backlogId);
            store.appendChild(input);
        }
        function deselectActivity(id) {
            var input = hiddenFor(id);
            if (input) input.remove();
        }
        function updateCount() {
            var p = root.querySelectorAll('[data-picker-project]:checked').length;
            var a = store.querySelectorAll('input').length;
            var b = {};
            store.querySelectorAll('input').forEach(function (i) { b[i.getAttribute('data-backlog-id')] = true; });
            count.textContent = p + ' proyectos · ' + Object.keys(b).length + ' backlogs · ' + a + ' actividades seleccionadas';
        }
        // A backlog's checkbox is checked when all its pickable activities are in, half-checked when some are.
        function syncBacklogBox(label) {
            var cb = label.querySelector('[data-picker-backlog]');
            var acts = label.parentNode.querySelectorAll('[data-picker-activity][data-backlog-id="' + cb.value + '"]:not(:disabled)');
            var on = 0;
            acts.forEach(function (a) { if (a.checked) on++; });
            cb.checked = acts.length > 0 && on === acts.length;
            cb.indeterminate = on > 0 && on < acts.length;
        }
        function projectCheckbox(row) { return row.querySelector('[data-picker-project]'); }
        function box(row) { return row.querySelector('.picker-backlogs'); }

        // Fetches the project's backlogs the first time it is opened; resolves with the rows.
        function load(row) {
            if (row._loading) return row._loading;
            var pid = row.getAttribute('data-project-id');
            box(row).innerHTML = '<span class="text-muted" style="font-size:12.5px;">Cargando…</span>';
            row._loading = fetch('/index.php?r=projects/sprints/backlogs/' + pid + '&sprint=' + sprintId, { headers: { Accept: 'application/json' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (items) {
                    row._items = items;
                    box(row).innerHTML = items.length === 0
                        ? '<span class="text-muted" style="font-size:12.5px;">Este proyecto no tiene backlogs.</span>'
                        : '<div class="picker-bulk"><button type="button" class="link-button" data-picker-all="1">Seleccionar todos</button>'
                            + ' &middot; <button type="button" class="link-button" data-picker-all="0">Quitar todos</button></div>'
                        + '<span class="picker-none text-muted" style="font-size:12.5px;" hidden>Ningún backlog con este filtro.</span>'
                        + items.map(function (b) {
                            return '<div class="picker-backlog" data-done="' + (b.done ? '1' : '0') + '" data-text="' + esc(b.description.toLowerCase()) + '">'
                                + '<label class="picker-check">'
                                + '<input type="checkbox" data-picker-backlog value="' + b.id + '"' + (b.activities.length === 0 ? ' disabled' : '') + '>'
                                + '<span>' + esc(b.description) + '</span>'
                                + '<span class="text-muted" style="font-size:12px;">' + esc(b.status) + ' · ' + b.progress + '%'
                                + (b.activities.length === 0 ? ' · sin actividades' : '') + '</span></label>'
                                + b.activities.map(function (a) {
                                    var selected = !!hiddenFor(a.id);
                                    return '<label class="picker-check picker-activity" data-done="' + (a.done ? '1' : '0') + '" style="margin-left:26px;font-size:12.5px;">'
                                        + '<input type="checkbox" data-picker-activity data-backlog-id="' + b.id + '" value="' + a.id + '"'
                                        + (selected ? ' checked' : '') + (a.busy && !selected ? ' disabled' : '') + '>'
                                        + '<span>' + esc(a.name) + '</span>'
                                        + '<span class="text-muted" style="font-size:11.5px;">' + esc(a.status) + ' · ' + a.progress + '% · ' + esc(a.developer)
                                        + (a.busy ? ' · en ' + esc(a.busy) : '') + '</span></label>';
                                }).join('')
                                + '</div>';
                        }).join('');
                    box(row).querySelectorAll('.picker-backlog').forEach(syncBacklogBox);
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
            row.querySelectorAll('.picker-backlog').forEach(function (group) {
                var done = group.getAttribute('data-done') === '1';
                var keep = group.querySelector('[data-picker-activity]:checked') !== null
                    || mode === 'all' || (mode === 'open' && !done) || (mode === 'done' && done);
                group.hidden = !keep;
                if (keep) shown++;
                // Finished activities stay out of sight with "Sin terminar" unless they are already in the sprint.
                group.querySelectorAll('.picker-activity').forEach(function (label) {
                    label.hidden = mode === 'open' && label.getAttribute('data-done') === '1' && !label.querySelector('input').checked;
                });
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
                // Unchecking a project takes its activities out of the sprint; checking it only includes the project.
                if (!t.checked) {
                    store.querySelectorAll('input[data-project-id="' + pid + '"]').forEach(function (i) { i.remove(); });
                    box(row).querySelectorAll('[data-picker-backlog], [data-picker-activity]').forEach(function (cb) { cb.checked = false; cb.indeterminate = false; });
                }
            } else if (t.matches('[data-picker-backlog]')) {
                // A whole backlog: its unfinished activities (finished ones only when picked one by one).
                var group = t.closest('.picker-backlog');
                group.querySelectorAll('[data-picker-activity]:not(:disabled)').forEach(function (cb) {
                    var done = cb.closest('.picker-activity').getAttribute('data-done') === '1';
                    if (t.checked && done) return;
                    cb.checked = t.checked;
                    t.checked ? selectActivity(cb.value, pid, t.value) : deselectActivity(cb.value);
                });
                syncBacklogBox(group);
                if (t.checked) projectCheckbox(row).checked = true;
            } else if (t.matches('[data-picker-activity]')) {
                if (t.checked) {
                    selectActivity(t.value, pid, t.getAttribute('data-backlog-id'));
                    projectCheckbox(row).checked = true;
                } else {
                    deselectActivity(t.value);
                }
                syncBacklogBox(t.closest('.picker-backlog'));
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
                // Only the activities currently shown (per the filter) are affected.
                box(brow).querySelectorAll('.picker-backlog:not([hidden]) .picker-activity:not([hidden]) [data-picker-activity]:not(:disabled)').forEach(function (cb) {
                    cb.checked = on;
                    on ? selectActivity(cb.value, bpid, cb.getAttribute('data-backlog-id')) : deselectActivity(cb.value);
                });
                box(brow).querySelectorAll('.picker-backlog').forEach(syncBacklogBox);
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

        // The end date can't be before the start.
        var startInput = document.querySelector('[data-sprint-start]');
        var endInput = document.querySelector('[data-sprint-end]');
        if (startInput && endInput) {
            startInput.addEventListener('change', function () {
                endInput.min = startInput.value;
                if (endInput.value < startInput.value) endInput.value = startInput.value;
            });
        }

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
