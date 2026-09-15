<?php
/**
 * Two-level project filter (see App\Helpers\ProjectScope): top-level project
 * first, then — only for a platform — one of its sub-projects.
 *
 * @var array $topProjects @var array $childProjects @var int $projectFilter @var int $childFilter
 */
?>
<select name="project_id" onchange="if (this.form.elements.child_id) this.form.elements.child_id.value = ''; this.form.submit()" aria-label="Filtrar por proyecto">
    <option value="">Todos los proyectos</option>
    <?php foreach ($topProjects as $p): ?>
        <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $projectFilter ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?><?= (int) ($p['is_platform'] ?? 0) === 1 ? ' (plataforma)' : '' ?></option>
    <?php endforeach; ?>
</select>
<?php if ($childProjects !== []): ?>
    <select name="child_id" onchange="this.form.submit()" aria-label="Filtrar por subproyecto">
        <option value="">Todos los subproyectos</option>
        <?php foreach ($childProjects as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (int) $c['id'] === $childFilter ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>
