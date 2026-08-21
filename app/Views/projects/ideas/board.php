<?php

use App\Helpers\Ui;

/** @var array $columns @var array $projects @var int $projectFilter */
$columnLabels = [
    'new' => 'Nueva',
    'clarifying' => 'En aclaración',
    'ready' => 'Lista para backlog',
    'converted' => 'Convertida',
];
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Captura rápida de tareas/ideas que surgen en las reuniones de sprint. Arrastra una tarjeta entre columnas para aclararla,
    y conviértela a backlog cuando esté lista.
</p>

<div class="toolbar">
    <form method="get" action="/index.php" class="filters" style="margin:0;">
        <input type="hidden" name="r" value="projects/ideas/index">
        <select name="project_id" onchange="this.form.submit()" aria-label="Filtrar por proyecto">
            <option value="">Todos los proyectos</option>
            <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (string) $p['id'] === (string) $projectFilter ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <button type="button" class="btn" data-open-modal="modal-create-idea">+ Nueva nota</button>
</div>

<div class="idea-board" data-idea-board>
    <?php foreach ($columnLabels as $status => $label): ?>
        <div class="idea-column" data-idea-column data-status="<?= $status ?>">
            <p class="idea-column-title"><?= htmlspecialchars($label) ?> (<?= count($columns[$status]) ?>)</p>
            <div class="idea-column-body" data-idea-dropzone data-status="<?= $status ?>">
                <?php foreach ($columns[$status] as $note): ?>
                    <div class="idea-card<?= $status === 'converted' ? ' idea-card-converted' : '' ?>" draggable="<?= $status === 'converted' ? 'false' : 'true' ?>" data-idea-card data-id="<?= $note['id'] ?>">
                        <p class="idea-card-project"><?= htmlspecialchars($note['project_name']) ?></p>
                        <p class="idea-card-text"><?= nl2br(htmlspecialchars($note['text'])) ?></p>
                        <p class="idea-card-meta">
                            <?php if (!empty($note['created_by'])): ?><?= htmlspecialchars($note['created_by']) ?> · <?php endif; ?>
                            <?= Ui::formatDate(substr($note['created_at'], 0, 10)) ?>
                        </p>
                        <?php if ($status === 'ready'): ?>
                            <a class="btn btn-secondary" style="padding:4px 10px;font-size:12px;" href="/index.php?r=projects/ideas/convert/<?= $note['id'] ?>">Convertir a backlog</a>
                        <?php elseif ($status === 'converted' && !empty($note['backlog_item_id'])): ?>
                            <a style="font-size:12px;" href="/index.php?r=projects/backlog/view/<?= $note['backlog_item_id'] ?>">Ver backlog creado</a>
                        <?php endif; ?>
                        <button type="button" class="link-button" style="font-size:11.5px;margin-top:6px;" data-confirm-delete="/index.php?r=projects/ideas/delete/<?= $note['id'] ?>" data-confirm-message="¿Eliminar esta nota?">Eliminar</button>
                    </div>
                <?php endforeach; ?>
                <?php if ($columns[$status] === []): ?><p class="empty-state" style="padding:var(--space-lg);">Sin notas</p><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<dialog id="modal-create-idea" class="modal">
    <div class="modal-header">
        <strong>Nueva nota</strong>
        <button type="button" class="modal-close" data-close-modal aria-label="Cerrar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"></path></svg>
        </button>
    </div>
    <div class="modal-body">
        <div class="card" style="max-width:520px;">
            <form method="post" action="/index.php?r=projects/ideas/create">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Proyecto</label>
                        <select name="project_id" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (string) $p['id'] === (string) $projectFilter ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Nota</label>
                        <textarea name="text" required placeholder="Qué salió en la reunión..."></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Registrada por</label>
                        <input type="text" name="created_by" placeholder="Opcional">
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit">Agregar</button>
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</dialog>

<script src="/assets/js/idea-board.js"></script>
