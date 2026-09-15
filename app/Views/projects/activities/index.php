<?php

use App\Core\View;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $activities @var array $backlogItems @var array $developers @var array $projects @var array $types
 *  @var array $priorities @var array $statuses @var array $dependencyOptions
 *  @var array $filters @var array $topProjects @var array $childProjects */
$baseFormOptions = [
    'backlogItems' => $backlogItems,
    'developers' => $developers,
    'projects' => $projects,
    'types' => $types,
    'priorities' => $priorities,
    'statuses' => $statuses,
];
?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-activity">+ Nueva actividad</button>
</div>

<form method="get" action="/index.php" class="filters filters-card">
    <input type="hidden" name="r" value="projects/activities/index">
    <div class="filters-head">
        <p class="filters-title">Filtros</p>
        <div class="filters-actions">
            <button class="btn btn-secondary" type="submit">Aplicar fechas</button>
            <?php if (array_filter($filters) !== []): ?>
                <a class="btn btn-secondary" href="/index.php?r=projects/activities/index">Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="filters-grid">
        <label class="filter-field"><span>Desarrollador</span>
            <select name="developer_id" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($developers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === $filters['developer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php View::renderPartial('shared/project-filter', [
            'topProjects' => $topProjects,
            'childProjects' => $childProjects,
            'projectFilter' => $filters['project_id'],
            'childFilter' => $filters['child_id'],
        ]) ?>
        <label class="filter-field"><span>Prioridad</span>
            <select name="priority" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($priorities as $pr): ?>
                    <option value="<?= htmlspecialchars($pr['code']) ?>" <?= $pr['code'] === $filters['priority'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field"><span>Estado manual</span>
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s['code']) ?>" <?= $s['code'] === $filters['status'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('activity_status', $s['code'])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field"><span>Estado del sistema</span>
            <select name="system_status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s['code']) ?>" <?= $s['code'] === $filters['system_status'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('activity_status', $s['code'])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field"><span>Desde</span>
            <input type="date" name="desde" id="actividades-desde" value="<?= htmlspecialchars($filters['desde'] ?? '') ?>">
        </label>
        <label class="filter-field"><span>Hasta</span>
            <input type="date" name="hasta" id="actividades-hasta" value="<?= htmlspecialchars($filters['hasta'] ?? '') ?>">
        </label>
    </div>
</form>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr>
            <th>Actividad</th><th>Backlog</th><th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th>
            <th>Estado (manual)</th><th>Estado del sistema</th><th>Avance</th><th>Fecha límite</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($activities as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['name']) ?> <?= Ui::noteIcon($a['notes'] ?? null) ?></td>
                <td><?= htmlspecialchars($a['backlog_description']) ?></td>
                <td><?= htmlspecialchars($a['project_name']) ?></td>
                <td><?= htmlspecialchars($a['developer_name']) ?><?php if (!empty($a['collaborator_names'])): ?><br><span class="text-muted" style="font-size:11.5px;">+ <?= htmlspecialchars($a['collaborator_names']) ?></span><?php endif; ?></td>
                <td><?= Ui::priorityBadge($a['priority_code']) ?></td>
                <td><?= Ui::statusBadge('activity_status', $a['status_code']) ?></td>
                <td>
                    <?= Ui::statusBadge('activity_status', $a['system_status']) ?>
                    <?php if ($a['system_status'] !== $a['status_code']): ?>
                        <?= Ui::warningIcon('El estado manual no coincide con el estado calculado por el sistema') ?>
                    <?php endif; ?>
                </td>
                <td style="min-width:120px;"><?= Ui::progressBar($a['progress_percent']) ?></td>
                <td><?= Ui::daysRemainingLabel($a['days_remaining'], $a['system_status'] === 'completed') ?></td>
                <td>
                    <button type="button" class="link-button" data-edit-activity="<?= $a['id'] ?>">Editar</button>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/activities/delete/<?= $a['id'] ?>" data-confirm-message="¿Eliminar esta actividad?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($activities === []): ?><tr><td colspan="10" class="empty-state">Sin actividades registradas</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-create-activity" class="modal">
    <div class="modal-header">
        <strong>Nueva actividad</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/activities/form', ['activity' => null, 'dependencyOptions' => $dependencyOptions, 'collaborators' => [], ...$baseFormOptions]) ?>
    </div>
</dialog>

<?php /* One shared edit dialog, filled on demand: rendering a full form per row
   (each with every backlog and every activity as <option>s) exhausted the
   host's memory once the table reached hundreds of activities. */ ?>
<dialog id="modal-edit-activity" class="modal">
    <div class="modal-header">
        <strong>Editar actividad</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body" data-edit-activity-body></div>
</dialog>
<script>
(function () {
    var dialog = document.getElementById('modal-edit-activity');
    var body = dialog.querySelector('[data-edit-activity-body]');

    function spanishValidation(root) {
        root.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            el.addEventListener('invalid', function () {
                el.setCustomValidity(el.validity.valueMissing ? 'Este campo es obligatorio.' : 'Ingresa un valor válido.');
            });
            ['input', 'change'].forEach(function (evt) {
                el.addEventListener(evt, function () { el.setCustomValidity(''); });
            });
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-edit-activity]');
        if (!btn) return;

        body.innerHTML = '<p class="text-muted">Cargando actividad…</p>';
        dialog.showModal();

        fetch('/index.php?r=projects/activities/editForm/' + encodeURIComponent(btn.getAttribute('data-edit-activity')), { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                body.innerHTML = html;
                var form = body.querySelector('form[data-activity-filter]');
                if (form && window.bfInitActivityForm) window.bfInitActivityForm(form);
                if (window.bfDecorateTextareas) window.bfDecorateTextareas(body);
                spanishValidation(body);
            })
            .catch(function () {
                body.innerHTML = '<p>No se pudo cargar la actividad. Recarga la página e inténtalo de nuevo.</p>';
            });
    });
})();
</script>
