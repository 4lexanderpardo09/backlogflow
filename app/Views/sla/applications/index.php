<?php use App\Helpers\Labels; ?>
<div class="toolbar">
    <div></div>
    <a class="btn" href="/index.php?r=sla/applications/create">+ Nueva aplicación</a>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead>
        <tr><th>Aplicación</th><th>Tipo</th><th>Criticidad</th><th>Área usuaria</th><th>Proveedor</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($applications as $a): ?>
            <tr>
                <td><a href="/index.php?r=sla/applications/view/<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></a></td>
                <td><?= htmlspecialchars(Labels::get('application_type', $a['type_code'])) ?></td>
                <td><?= \App\Helpers\Ui::priorityBadge($a['criticality_code']) ?></td>
                <td><?= htmlspecialchars($a['requesting_area'] ?? Labels::NOT_DEFINED) ?></td>
                <td><?= htmlspecialchars($a['provider_name'] ?? Labels::IN_HOUSE_PROVIDER) ?></td>
                <td><span class="badge <?= $a['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= htmlspecialchars(Labels::get('application_status', $a['status'])) ?></span></td>
                <td>
                    <a href="/index.php?r=sla/applications/edit/<?= $a['id'] ?>">Editar</a>
                    &middot;
                    <a href="/index.php?r=sla/applications/datasheet/<?= $a['id'] ?>">Ficha</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($applications === []): ?><tr><td colspan="7" class="empty-state">Sin aplicaciones registradas</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
