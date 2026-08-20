<?php

use App\Core\View;
use App\Helpers\Labels;
use App\Helpers\Ui;

?>
<div class="toolbar">
    <div></div>
    <button type="button" class="btn" data-open-modal="modal-create-developer">+ Nuevo desarrollador</button>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Nombre</th><th>Cargo</th><th>Estado</th><th>Fecha de inicio</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($developers as $d): ?>
            <tr>
                <td><a href="/index.php?r=projects/developers/view/<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></a></td>
                <td><?= htmlspecialchars($d['position'] ?? Labels::NOT_DEFINED) ?></td>
                <td><span class="badge <?= $d['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= htmlspecialchars(Labels::get('developer_status', $d['status'])) ?></span></td>
                <td><?= Ui::formatDate($d['start_date']) ?></td>
                <td>
                    <button type="button" class="link-button" data-open-modal="modal-edit-developer-<?= $d['id'] ?>">Editar</button>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/developers/delete/<?= $d['id'] ?>" data-confirm-message="¿Eliminar este desarrollador?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($developers === []): ?><tr><td colspan="5" class="empty-state">Sin desarrolladores registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>

<dialog id="modal-create-developer" class="modal">
    <div class="modal-header">
        <strong>Nuevo desarrollador</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <?php View::renderPartial('projects/developers/form', ['developer' => null]) ?>
    </div>
</dialog>

<?php foreach ($developers as $d): ?>
    <dialog id="modal-edit-developer-<?= $d['id'] ?>" class="modal">
        <div class="modal-header">
            <strong>Editar desarrollador</strong>
            <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="modal-body">
            <?php View::renderPartial('projects/developers/form', ['developer' => $d]) ?>
        </div>
    </dialog>
<?php endforeach; ?>
