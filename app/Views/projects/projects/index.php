<?php

use App\Core\View;
use App\Helpers\Ui;

/** @var array $projects @var array $developers @var array $priorities @var array $statuses */
$formOptions = ['developers' => $developers, 'priorities' => $priorities, 'statuses' => $statuses];
?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-project">+ Nuevo proyecto</button>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr>
            <th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th>
            <th>Avance</th><th>Días restantes</th><th>Semáforo</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><a href="/index.php?r=projects/projects/view/<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></td>
                <td><?= htmlspecialchars($p['developer_name']) ?><?php if (!empty($p['collaborator_names'])): ?><br><span class="text-muted" style="font-size:11.5px;">+ <?= htmlspecialchars($p['collaborator_names']) ?></span><?php endif; ?></td>
                <td><?= Ui::priorityBadge($p['priority_code']) ?></td>
                <td><?= Ui::statusBadge('project_status', $p['status_code']) ?></td>
                <td style="min-width:120px;"><?= Ui::progressBar($p['progress_percent']) ?></td>
                <td><?= Ui::daysRemainingLabel($p['days_remaining'], $p['status_code'] === 'completed') ?></td>
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
