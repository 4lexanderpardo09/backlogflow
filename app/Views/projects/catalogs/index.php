<?php use App\Helpers\Labels; ?>
<p style="color:#6b7280;margin-top:0;">Estas listas alimentan todos los desplegables del módulo de proyectos. Un valor no se puede eliminar si ya está en uso.</p>

<div class="three-col">
    <?php foreach ($catalogs as $key => $catalog): ?>
        <div class="card">
            <p class="card-title"><?= htmlspecialchars($catalog['title']) ?></p>
            <div class="table-scroll"><table>
                <tbody>
                <?php foreach ($catalog['rows'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars(Labels::get($catalog['label_group'], $row['code'])) ?></td>
                        <td style="text-align:right;">
                            <button type="button" class="link-button" data-confirm-delete="/index.php?r=projects/catalogs/delete&id=<?= $row['id'] ?>&catalog=<?= $key ?>" data-confirm-message="¿Eliminar este valor?">Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($catalog['rows'] === []): ?><tr><td class="empty-state">Sin valores</td></tr><?php endif; ?>
                </tbody>
            </table></div>
            <form method="post" action="/index.php?r=projects/catalogs/create&catalog=<?= $key ?>" style="margin-top:12px;display:flex;gap:8px;">
                <input type="text" name="code" placeholder="Ej: Revisión de seguridad" required style="flex:1;">
                <button class="btn btn-secondary" type="submit">Agregar</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
