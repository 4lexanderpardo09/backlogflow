<?php

use App\Core\View;
use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $backlogItems @var array $projects @var array $developers @var array $types @var array $priorities @var array $statuses
 *  @var array $filters @var array $topProjects @var array $childProjects */
$formOptions = [
    'projects' => $projects,
    'developers' => $developers,
    'types' => $types,
    'priorities' => $priorities,
    'statuses' => $statuses,
];
?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-backlog">+ Nuevo backlog</button>
</div>

<form method="get" action="/index.php" class="filters">
    <input type="hidden" name="r" value="projects/backlog/index">
    <select name="developer_id" onchange="this.form.submit()" aria-label="Filtrar por desarrollador">
        <option value="">Todos los desarrolladores</option>
        <?php foreach ($developers as $d): ?>
            <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === $filters['developer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php View::renderPartial('shared/project-filter', [
        'topProjects' => $topProjects,
        'childProjects' => $childProjects,
        'projectFilter' => $filters['project_id'],
        'childFilter' => $filters['child_id'],
    ]) ?>
    <select name="priority" onchange="this.form.submit()" aria-label="Filtrar por prioridad">
        <option value="">Toda prioridad</option>
        <?php foreach ($priorities as $pr): ?>
            <option value="<?= htmlspecialchars($pr['code']) ?>" <?= $pr['code'] === $filters['priority'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('priority', $pr['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()" aria-label="Filtrar por estado">
        <option value="">Todo estado</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= htmlspecialchars($s['code']) ?>" <?= $s['code'] === $filters['status'] ? 'selected' : '' ?>><?= htmlspecialchars(Labels::get('backlog_status', $s['code'])) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="filter-date">Desde <input type="date" name="desde" id="backlog-desde" value="<?= htmlspecialchars($filters['desde'] ?? '') ?>"></label>
    <label class="filter-date">Hasta <input type="date" name="hasta" id="backlog-hasta" value="<?= htmlspecialchars($filters['hasta'] ?? '') ?>"></label>
    <button class="btn btn-secondary" type="submit">Aplicar fechas</button>
    <?php if (array_filter($filters) !== []): ?>
        <a class="btn btn-secondary" href="/index.php?r=projects/backlog/index">Limpiar filtros</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr><th>Descripción</th><th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th><th>Avance</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($backlogItems as $b): ?>
            <tr>
                <td><a href="/index.php?r=projects/backlog/view/<?= $b['id'] ?>"><?= htmlspecialchars($b['description']) ?></a> <?= Ui::noteIcon($b['notes'] ?? null) ?></td>
                <td><?= htmlspecialchars($b['project_name']) ?></td>
                <td><?= htmlspecialchars($b['developer_name']) ?><?php if (!empty($b['collaborator_names'])): ?><br><span class="text-muted" style="font-size:11.5px;">+ <?= htmlspecialchars($b['collaborator_names']) ?></span><?php endif; ?></td>
                <td><?= Ui::priorityBadge($b['priority_code']) ?></td>
                <td><?= Ui::statusBadge('backlog_status', $b['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($b['progress_percent']) ?></td>
                <td>
                    <button type="button" class="link-button" data-open-modal="modal-edit-backlog-<?= $b['id'] ?>">Editar</button>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/backlog/delete/<?= $b['id'] ?>" data-confirm-message="¿Eliminar este backlog?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($backlogItems === []): ?><tr><td colspan="7" class="empty-state">Sin backlogs registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-create-backlog" class="modal">
    <div class="modal-header">
        <strong>Nuevo elemento de backlog</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/backlog/form', ['backlogItem' => null, 'collaborators' => [], ...$formOptions]) ?>
    </div>
</dialog>

<?php foreach ($backlogItems as $b): ?>
    <dialog id="modal-edit-backlog-<?= $b['id'] ?>" class="modal">
        <div class="modal-header">
            <strong>Editar elemento de backlog</strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="modal-body">
            <?php
            $rowCollaborators = array_map(fn ($cid) => ['id' => (int) $cid], array_filter(explode(',', (string) ($b['collaborator_ids'] ?? ''))));
            View::renderPartial('projects/backlog/form', ['backlogItem' => $b, 'collaborators' => $rowCollaborators, ...$formOptions]);
            ?>
        </div>
    </dialog>
<?php endforeach; ?>
