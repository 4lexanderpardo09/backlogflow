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
            <?php \App\Core\View::renderPartial('projects/ideas/project-picker', [
                'projects' => $projects,
                'selectedId' => $note['project_id'] !== null ? (int) $note['project_id'] : null,
            ]) ?>
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
