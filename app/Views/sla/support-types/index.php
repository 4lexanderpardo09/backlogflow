<?php

use App\Helpers\Labels;
use App\Helpers\Ui;

/** @var array $supportTypes */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Registro de qué tipos de soporte se prestan y a qué nivel pertenece cada uno.
    Nivel 1: creación de usuarios, permisos, etc. Nivel 2: los demás soportes propios de la aplicación.
    Esto es solo una clasificación — el registro de tickets/incidentes se maneja aparte.
</p>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Tipo de soporte</th><th>Nivel actual</th><th>Cambiar nivel</th></tr></thead>
        <tbody>
        <?php foreach ($supportTypes as $st): ?>
            <tr>
                <td><?= htmlspecialchars(Labels::get('support_type', $st['code'])) ?></td>
                <td><?= Ui::supportTypeLevelBadge((int) $st['level']) ?></td>
                <td>
                    <form method="post" action="/index.php?r=sla/support-types/update/<?= $st['id'] ?>" style="display:flex;gap:8px;align-items:center;">
                        <select name="level" onchange="this.form.submit()" aria-label="Nivel de <?= htmlspecialchars(Labels::get('support_type', $st['code'])) ?>">
                            <option value="1" <?= (int) $st['level'] === 1 ? 'selected' : '' ?>>Nivel 1</option>
                            <option value="2" <?= (int) $st['level'] === 2 ? 'selected' : '' ?>>Nivel 2</option>
                        </select>
                        <noscript><button class="btn btn-secondary" type="submit">Guardar</button></noscript>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($supportTypes === []): ?><tr><td colspan="3" class="empty-state">Sin tipos de soporte registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
