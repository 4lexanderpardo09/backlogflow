<?php use App\Helpers\Labels; ?>
<p style="color:#6b7280;margin-top:0;">Nivel 1 = Mesa de ayuda / Sistemas · Nivel 2 = Especialista interno · Nivel 3 = Proveedor · Nivel 4 = Fabricante.</p>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Aplicación</th><th>Nivel 1</th><th>Nivel 2</th><th>Nivel 3</th><th>Proveedor</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                $byLevel = [];
                foreach ($row['levels'] as $l) {
                    $byLevel[(int) $l['level']] = $l;
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['application']['name']) ?></td>
                <?php foreach ([1, 2, 3] as $lvl): ?>
                    <td>
                        <?php if (isset($byLevel[$lvl])): ?>
                            <?= htmlspecialchars($byLevel[$lvl]['responsible'] ?? Labels::NOT_DEFINED) ?>
                        <?php else: ?>
                            <span class="empty-state" style="padding:0;">Sin definir</span>
                        <?php endif; ?>
                        <br>
                        <a href="/index.php?r=sla/support-matrix/edit/<?= $row['application']['id'] ?>&level=<?= $lvl ?>" style="font-size:11.5px;">Editar</a>
                    </td>
                <?php endforeach; ?>
                <td><?= htmlspecialchars($row['application']['provider_name'] ?? Labels::IN_HOUSE_PROVIDER) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?><tr><td colspan="5" class="empty-state">Sin aplicaciones registradas</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
