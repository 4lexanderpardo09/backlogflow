<?php

use App\Helpers\Gantt;

/** @var array $platforms @var array $children @var int $parentId @var int $childId @var array $gantt */
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Elige un proyecto padre (plataforma) y, si quieres, uno de sus subproyectos. El diagrama muestra
    los sprints que tocan esos proyectos, sus backlogs y las actividades planeadas, con el relleno
    proporcional al avance.
</p>

<form method="get" action="/index.php" class="filters">
    <input type="hidden" name="r" value="projects/cronograma/index">
    <select name="parent_id" onchange="this.form.submit()" aria-label="Proyecto padre">
        <option value="">Selecciona una plataforma...</option>
        <?php foreach ($platforms as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $parentId ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="child_id" onchange="this.form.submit()" aria-label="Subproyecto" <?= $children === [] ? 'disabled' : '' ?>>
        <option value="">Todos los subproyectos</option>
        <?php foreach ($children as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int) $c['id'] === $childId ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($parentId > 0): ?>
        <a class="btn btn-secondary" href="/index.php?r=projects/cronograma/index">Limpiar</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if ($parentId === 0): ?>
        <p class="empty-state">Selecciona una plataforma para ver su cronograma.</p>
    <?php else: ?>
        <p style="font-size:12.5px;color:var(--color-muted-foreground);margin:0 0 var(--space-md);">
            Ventana: <?= htmlspecialchars($gantt['window_start']) ?> — <?= htmlspecialchars($gantt['window_end']) ?>
        </p>
        <?= Gantt::render($gantt['window_start'], $gantt['window_end'], $gantt['rows']) ?>
    <?php endif; ?>
</div>
