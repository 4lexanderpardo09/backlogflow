<?php

use App\Core\View;
use App\Helpers\Ui;

/** @var array $backlogItems @var array $projects @var array $developers @var array $types @var array $priorities @var array $statuses */
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

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr><th>Descripción</th><th>Proyecto</th><th>Desarrollador</th><th>Prioridad</th><th>Estado</th><th>Avance</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($backlogItems as $b): ?>
            <tr>
                <td><a href="/index.php?r=projects/backlog/view/<?= $b['id'] ?>"><?= htmlspecialchars($b['description']) ?></a></td>
                <td><?= htmlspecialchars($b['project_name']) ?></td>
                <td><?= htmlspecialchars($b['developer_name']) ?></td>
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
        <?php View::renderPartial('projects/backlog/form', ['backlogItem' => null, ...$formOptions]) ?>
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
            <?php View::renderPartial('projects/backlog/form', ['backlogItem' => $b, ...$formOptions]) ?>
        </div>
    </dialog>
<?php endforeach; ?>
