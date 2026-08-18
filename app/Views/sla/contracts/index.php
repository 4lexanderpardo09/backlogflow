<?php use App\Helpers\Labels; use App\Helpers\Ui; ?>
<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=sla/contracts/create">+ Nuevo vencimiento</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Aplicación</th><th>Tipo</th><th>Detalle</th><th>Vencimiento</th><th>Alerta</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($contracts as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['application_name']) ?></td>
                <td><?= htmlspecialchars(Labels::get('contract_type', $c['type'])) ?></td>
                <td><?= htmlspecialchars($c['label'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= Ui::formatDate($c['expiration_date']) ?></td>
                <td><?= Ui::contractAlertBadge($c['bucket']) ?></td>
                <td><button type="button" class="link-button" data-confirm-delete="/index.php?r=sla/contracts/delete/<?= $c['id'] ?>" data-confirm-message="¿Eliminar este registro?">Eliminar</button></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($contracts === []): ?><tr><td colspan="6" class="empty-state">Sin vencimientos registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
