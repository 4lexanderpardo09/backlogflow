<?php

use App\Helpers\Gantt;
use App\Helpers\Ui;

/** @var array $platforms @var array $children @var int $parentId @var int $childId @var ?string $from @var ?string $to @var array $gantt */
$customWindow = $from !== null && $to !== null && $from <= $to;
$invertedWindow = $from !== null && $to !== null && $from > $to;
?>
<p style="color:var(--color-muted-foreground);margin-top:0;">
    Elige un proyecto padre (plataforma) y, si quieres, uno de sus subproyectos. El diagrama muestra
    los sprints que tocan esos proyectos, sus backlogs y las actividades planeadas, con el relleno
    proporcional al avance. Los backlogs que no están en ningún sprint aparecen en «Sin sprint asignado».
    Usa <strong>Desde</strong> y <strong>Hasta</strong> para cambiar la ventana de fechas.
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
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--color-muted-foreground);">
        Desde <input type="date" name="desde" id="cronograma-desde" value="<?= htmlspecialchars($from ?? '') ?>">
    </label>
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--color-muted-foreground);">
        Hasta <input type="date" name="hasta" id="cronograma-hasta" value="<?= htmlspecialchars($to ?? '') ?>">
    </label>
    <button class="btn" type="submit">Ver</button>
    <?php if ($parentId > 0 || $from !== null || $to !== null): ?>
        <a class="btn btn-secondary" href="/index.php?r=projects/cronograma/index">Limpiar</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if ($parentId === 0): ?>
        <p class="empty-state">Selecciona una plataforma para ver su cronograma.</p>
    <?php else: ?>
        <?php if ($invertedWindow): ?>
            <p style="font-size:12.5px;color:var(--color-warning);margin:0 0 var(--space-md);">
                «Desde» es posterior a «Hasta», así que se muestra la ventana automática. Corrige las fechas y pulsa Ver.
            </p>
        <?php endif; ?>
        <p style="font-size:12.5px;color:var(--color-muted-foreground);margin:0 0 var(--space-md);">
            Ventana: <?= Ui::formatDate($gantt['window_start']) ?> — <?= Ui::formatDate($gantt['window_end']) ?>
            <?= $customWindow ? '(elegida)' : '(automática: sprints o mes actual)' ?>
        </p>
        <?= Gantt::render($gantt['window_start'], $gantt['window_end'], $gantt['rows']) ?>
    <?php endif; ?>
</div>
