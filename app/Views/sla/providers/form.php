<?php
/** @var array|null $provider */
$p = $provider ?? [];
$action = $provider === null ? '/index.php?r=sla/providers/create' : '/index.php?r=sla/providers/edit/' . $provider['id'];
?>
<div class="card" style="max-width:760px;">
    <form method="post" action="<?= $action ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label>Nombre del proveedor</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($p['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>NIT</label>
                <input type="text" name="tax_id" value="<?= htmlspecialchars($p['tax_id'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto comercial</label>
                <input type="text" name="commercial_contact" value="<?= htmlspecialchars($p['commercial_contact'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto técnico</label>
                <input type="text" name="technical_contact" value="<?= htmlspecialchars($p['technical_contact'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" value="<?= htmlspecialchars($p['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($p['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Portal de soporte</label>
                <input type="text" name="support_portal" value="<?= htmlspecialchars($p['support_portal'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Canal de atención</label>
                <input type="text" name="support_channel" value="<?= htmlspecialchars($p['support_channel'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Horario de atención</label>
                <input type="text" name="support_hours" value="<?= htmlspecialchars($p['support_hours'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($p['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/providers/index">Cancelar</a>
        </div>
    </form>
</div>
