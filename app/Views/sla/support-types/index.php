<?php

/** @var array $supportTypes */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Catálogo de tipos de soporte: crea, renombra o elimina los que necesite el equipo, y clasifica cada uno
    por nivel. Nivel 1: creación de usuarios, permisos, etc. Nivel 2: los demás soportes propios de la aplicación.
    Esto es solo una clasificación — el registro de tickets/incidentes se maneja aparte.
    Luego, en cada aplicación (<a href="/index.php?r=sla/applications/index">Aplicaciones</a>), marcas cuáles de
    estos tipos presta y a qué nivel corresponde para esa aplicación en particular.
</p>

<div class="card" style="max-width:520px;margin-bottom:18px;">
    <p class="card-title">Nuevo tipo de soporte</p>
    <form method="post" action="/index.php?r=sla/support-types/create" class="form-grid">
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="name" required placeholder="Ej. Soporte de reportes">
        </div>
        <div class="form-group">
            <label>Nivel</label>
            <select name="level">
                <option value="1">Nivel 1</option>
                <option value="2" selected>Nivel 2</option>
            </select>
        </div>
        <div class="form-actions" style="grid-column:1/-1;">
            <button class="btn" type="submit">Agregar</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-scroll"><table>
        <thead><tr><th>Nombre y nivel</th><th style="width:120px;"></th></tr></thead>
        <tbody>
        <?php foreach ($supportTypes as $st): ?>
            <tr>
                <td>
                    <form method="post" action="/index.php?r=sla/support-types/update/<?= $st['id'] ?>" style="display:flex;gap:8px;align-items:center;">
                        <input type="text" name="name" value="<?= htmlspecialchars($st['name']) ?>" style="max-width:260px;">
                        <select name="level" aria-label="Nivel de <?= htmlspecialchars($st['name']) ?>">
                            <option value="1" <?= (int) $st['level'] === 1 ? 'selected' : '' ?>>Nivel 1</option>
                            <option value="2" <?= (int) $st['level'] === 2 ? 'selected' : '' ?>>Nivel 2</option>
                        </select>
                        <button class="btn btn-secondary" type="submit" style="padding:4px 10px;font-size:12.5px;">Guardar</button>
                    </form>
                </td>
                <td><button type="button" class="link-button" data-confirm-delete="/index.php?r=sla/support-types/delete/<?= $st['id'] ?>" data-confirm-message="¿Eliminar el tipo de soporte «<?= htmlspecialchars($st['name']) ?>»?">Eliminar</button></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($supportTypes === []): ?><tr><td colspan="2" class="empty-state">Sin tipos de soporte registrados</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
