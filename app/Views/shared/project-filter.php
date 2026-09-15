<?php
/**
 * Two-level project filter (see App\Helpers\ProjectScope): top-level project
 * first, then — only for a platform — one of its sub-projects. Rendered as
 * .filter-field cells of a .filters-grid.
 *
 * @var array $topProjects @var array $childProjects @var int $projectFilter @var int $childFilter
 */
?>
<label class="filter-field"><span>Proyecto</span>
    <select name="project_id" onchange="if (this.form.elements.child_id) this.form.elements.child_id.value = ''; this.form.submit()">
        <option value="">Todos</option>
        <?php foreach ($topProjects as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $projectFilter ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?><?= (int) ($p['is_platform'] ?? 0) === 1 ? ' (plataforma)' : '' ?></option>
        <?php endforeach; ?>
    </select>
</label>
<?php if ($childProjects !== []): ?>
    <label class="filter-field"><span>Subproyecto</span>
        <select name="child_id" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($childProjects as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int) $c['id'] === $childFilter ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
<?php endif; ?>
