<?php

use App\Core\View;
use App\Helpers\Ui;

/** @var array $projects @var array $developers @var array $priorities @var array $statuses @var array $platforms */
$formOptions = ['developers' => $developers, 'priorities' => $priorities, 'statuses' => $statuses, 'platforms' => $platforms];

// Order rows as: each platform immediately followed by its children, then
// every standalone project (no parent, not a platform). Each row carries the
// id of the group it belongs to, so the table tools paginate, sort and filter
// by whole platforms instead of splitting a parent from its children.
$byParent = [];
foreach ($projects as $row) {
    $byParent[(int) ($row['parent_id'] ?? 0)][] = $row;
}
$ordered = [];
foreach ($projects as $row) {
    if ((int) $row['is_platform'] === 1) {
        $row['_group'] = (int) $row['id'];
        $row['_role'] = 'parent';
        $ordered[] = $row;
        $children = $byParent[(int) $row['id']] ?? [];
        $lastIndex = count($children) - 1;
        foreach ($children as $i => $child) {
            $child['_group'] = (int) $row['id'];
            $child['_role'] = 'child';
            $child['_last'] = $i === $lastIndex;
            $ordered[] = $child;
        }
    }
}
foreach ($projects as $row) {
    if ((int) $row['is_platform'] !== 1 && (int) ($row['parent_id'] ?? 0) === 0) {
        $row['_group'] = (int) $row['id'];
        $row['_role'] = 'standalone';
        $ordered[] = $row;
    }
}
?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-project">+ Nuevo proyecto</button>
</div>

<div class="card">
    <div class="table-scroll"><table data-page-size="25">
        <thead>
        <tr>
            <th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th>
            <th>Avance</th><th>Días restantes</th><th>Semáforo</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ordered as $p): ?>
            <tr data-group="<?= (int) $p['_group'] ?>" data-role="<?= $p['_role'] ?>" class="row-<?= $p['_role'] ?><?= !empty($p['_last']) ? ' row-child-last' : '' ?>">
                <td>
                    <div class="project-cell">
                        <?php if ($p['_role'] === 'child'): ?><span class="project-branch" aria-hidden="true"></span><?php endif; ?>
                        <a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a>
                        <?php if ((int) $p['is_platform'] === 1): ?>
                            <span class="badge badge-blue">Plataforma · <?= (int) ($p['child_count'] ?? 0) ?> subproyectos</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($p['developer_name']) ?><?php if (!empty($p['collaborator_names'])): ?><br><span class="text-muted" style="font-size:11.5px;">+ <?= htmlspecialchars($p['collaborator_names']) ?></span><?php endif; ?></td>
                <td><?= Ui::priorityBadge($p['priority_code']) ?></td>
                <td><?= Ui::statusBadge('project_status', $p['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($p['progress_percent']) ?></td>
                <td><?= Ui::daysRemainingLabel($p['days_remaining'], $p['status_code'] === 'completed', $p['estimated_end_date'] ?? null) ?></td>
                <td><?= Ui::trafficLight($p['traffic_light']) ?></td>
                <td>
                    <button type="button" class="link-button" data-open-modal="modal-edit-project-<?= $p['id'] ?>">Editar</button>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/projects/delete/<?= $p['id'] ?>" data-confirm-message="¿Eliminar este proyecto?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($projects === []): ?><tr><td colspan="8" class="empty-state">Sin proyectos registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-create-project" class="modal">
    <div class="modal-header">
        <strong>Nuevo proyecto</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/projects/form', ['project' => null, 'collaborators' => [], ...$formOptions]) ?>
    </div>
</dialog>

<?php foreach ($projects as $p): ?>
    <dialog id="modal-edit-project-<?= $p['id'] ?>" class="modal">
        <div class="modal-header">
            <strong>Editar proyecto</strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="modal-body">
            <?php
            $rowCollaborators = array_map(fn ($cid) => ['id' => (int) $cid], array_filter(explode(',', (string) ($p['collaborator_ids'] ?? ''))));
            View::renderPartial('projects/projects/form', ['project' => $p, 'collaborators' => $rowCollaborators, ...$formOptions]);
            ?>
        </div>
    </dialog>
<?php endforeach; ?>
