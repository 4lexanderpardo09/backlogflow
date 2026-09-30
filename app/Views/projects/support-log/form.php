<?php
/** @var array|null $entry @var array $developers */
use App\Helpers\Labels;

$e = $entry ?? [];
$action = $entry === null ? '/index.php?r=projects/support-log/create' : '/index.php?r=projects/support-log/edit/' . $entry['id'];
$inHelpdesk = (int) ($e['in_helpdesk'] ?? 0) === 1;
?>
<div class="card" style="max-width:700px;">
    <form method="post" action="<?= $action ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="log_date" required value="<?= htmlspecialchars($e['log_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Atendido / registrado por</label>
                <select name="developer_id" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($developers as $d): ?>
                        <?php if ($d['status'] !== 'active' && (int) $d['id'] !== (int) ($e['developer_id'] ?? 0)) continue; ?>
                        <option value="<?= $d['id'] ?>" <?= (int) $d['id'] === (int) ($e['developer_id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Solicitante</label>
                <input type="text" name="requester" placeholder="Quién pidió la ayuda" value="<?= htmlspecialchars($e['requester'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Área del solicitante</label>
                <input type="text" name="requester_area" value="<?= htmlspecialchars($e['requester_area'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tipo de soporte</label>
                <select name="category">
                    <?php foreach (Labels::options('support_category') as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($e['category'] ?? 'other') === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tiempo en solucionarlo (minutos)</label>
                <input type="number" name="time_minutes" min="0" step="1" value="<?= (int) ($e['time_minutes'] ?? 0) ?>">
            </div>
            <div class="form-group full">
                <label>¿Qué se hizo?</label>
                <textarea name="description" required placeholder="Ej.: Se reinstaló el driver de la impresora del 2.º piso"><?= htmlspecialchars($e['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>¿Registrado en la mesa de ayuda?</label>
                <select name="in_helpdesk" data-helpdesk-toggle>
                    <option value="0" <?= $inHelpdesk ? '' : 'selected' ?>>No</option>
                    <option value="1" <?= $inHelpdesk ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
            <div class="form-group">
                <label>N.º de caso en mesa de ayuda</label>
                <input type="text" name="helpdesk_ticket" value="<?= htmlspecialchars($e['helpdesk_ticket'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <button type="button" class="btn btn-secondary" data-close-modal data-fallback-href="/index.php?r=projects/support-log/index">Cancelar</button>
        </div>
    </form>
</div>
