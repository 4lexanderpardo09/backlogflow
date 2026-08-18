<?php use App\Helpers\Labels; ?>
<div class="card" style="max-width:700px;">
    <form method="post" action="/index.php?r=sla/contracts/create">
        <div class="form-grid">
            <div class="form-group full">
                <label>Aplicación</label>
                <select name="application_id" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($applications as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="type" required>
                    <?php foreach (Labels::options('contract_type') as $code => $label): ?>
                        <option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de vencimiento</label>
                <input type="date" name="expiration_date" required>
            </div>
            <div class="form-group full">
                <label>Detalle</label>
                <input type="text" name="label" placeholder="Ej: Contrato de soporte anual">
            </div>
            <div class="form-group full">
                <label>Observaciones</label>
                <textarea name="notes"></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=sla/contracts/index">Cancelar</a>
        </div>
    </form>
</div>
