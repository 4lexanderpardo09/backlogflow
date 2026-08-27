<?php
/** @var array $note @var array $projects */
?>
<div class="toolbar">
    <a class="btn btn-secondary" href="/index.php?r=projects/ideas/index">&larr; Volver al tablero</a>
    <div></div>
</div>

<div class="card" style="max-width:640px;">
    <form method="post" action="/index.php?r=projects/ideas/edit/<?= (int) $note['id'] ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label>Proyecto</label>
                <select name="project_id" required>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === (int) $note['project_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Nota</label>
                <textarea name="text" required data-expandable><?= htmlspecialchars($note['text']) ?></textarea>
            </div>
            <div class="form-group full">
                <label>Registrada por</label>
                <input type="text" name="created_by" value="<?= htmlspecialchars($note['created_by'] ?? '') ?>" placeholder="Opcional">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="/index.php?r=projects/ideas/index">Cancelar</a>
        </div>
    </form>
</div>
