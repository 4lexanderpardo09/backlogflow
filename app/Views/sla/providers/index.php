<?php use App\Helpers\Labels; ?>
<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=sla/providers/create">+ Nuevo proveedor</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Proveedor</th><th>Contacto técnico</th><th>Correo</th><th>Aplicaciones</th><th>Portal de soporte</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($providers as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['technical_contact'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($p['email'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars(implode(', ', array_column($p['applications'], 'name')) ?: Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($p['support_portal'] ?? Labels::NOT_DEFINED) ?></td>
                <td>
                    <a href="/index.php?r=sla/providers/edit/<?= $p['id'] ?>">Editar</a>
                    &middot;
                    <button type="button" class="link-button" data-confirm-delete="/index.php?r=sla/providers/delete/<?= $p['id'] ?>" data-confirm-message="¿Eliminar este proveedor?">Eliminar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($providers === []): ?><tr><td colspan="6" class="empty-state">Sin proveedores registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
