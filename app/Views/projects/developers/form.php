<?php
/** @var array|null $developer */
$d = $developer ?? [];
$action = $developer === null ? '/index.php?r=projects/developers/create' : '/index.php?r=projects/developers/edit/' . $developer['id'];
?>
<div class="card" style="max-width:700px;">
    <form method="post" action="<?= $action ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label>Nombre</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($d['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Cargo</label>
                <input type="text" name="position" value="<?= htmlspecialchars($d['position'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($d['start_date'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"><?= htmlspecialchars($d['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/developers/index">Cancelar</button>
        </div>
    </form>
</div>
