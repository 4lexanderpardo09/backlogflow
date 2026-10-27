<?php
/**
 * Two-step project choice for an idea: first a platform or a standalone
 * project (or none), then — only for a platform — one of its sub-projects.
 * Listing every project at once in one flat select hid which platform each
 * module belongs to. The chosen id is submitted as project_id.
 *
 * @var array $projects every project (id, name, parent_id, is_platform)
 * @var int|null $selectedId project currently chosen (null = loose idea)
 */
use App\Helpers\ProjectScope;

$byId = array_column($projects, null, 'id');
$selected = $selectedId !== null ? ($byId[$selectedId] ?? null) : null;
// A sub-project preselects its platform in the first step and itself in the second.
$topSelected = $selected !== null ? (int) ($selected['parent_id'] ?: $selected['id']) : 0;
$childSelected = $selected !== null && $selected['parent_id'] ? (int) $selected['id'] : 0;
$top = ProjectScope::topLevel($projects);
$platforms = array_values(array_filter($top, fn (array $p) => (int) $p['is_platform'] === 1));
$standalone = array_values(array_filter($top, fn (array $p) => (int) $p['is_platform'] !== 1));
?>
<div class="form-group full" data-idea-project-picker>
    <input type="hidden" name="project_id" value="<?= $selected !== null ? (int) $selected['id'] : '' ?>" data-picker-value>
    <label>Proyecto</label>
    <select data-picker-top aria-label="Plataforma o proyecto">
        <option value="">Sin proyecto (idea suelta)</option>
        <?php if ($platforms !== []): ?>
            <optgroup label="Plataformas">
                <?php foreach ($platforms as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $topSelected ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endif; ?>
        <?php if ($standalone !== []): ?>
            <optgroup label="Proyectos independientes">
                <?php foreach ($standalone as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int) $p['id'] === $topSelected ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endif; ?>
    </select>
    <?php foreach ($platforms as $platform):
        $children = ProjectScope::childrenOf($projects, (int) $platform['id']); ?>
        <select data-picker-children="<?= $platform['id'] ?>" aria-label="Subproyecto de <?= htmlspecialchars($platform['name']) ?>"
                style="margin-top:6px;" <?= (int) $platform['id'] === $topSelected ? '' : 'hidden' ?>>
            <option value="">Toda la plataforma (sin subproyecto)</option>
            <?php foreach ($children as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int) $c['id'] === $childSelected ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endforeach; ?>
</div>
<script>
(function () {
    if (window.__bfIdeaPickerBound) return;
    window.__bfIdeaPickerBound = true;

    function sync(picker) {
        var top = picker.querySelector('[data-picker-top]');
        var value = top.value;
        picker.querySelectorAll('[data-picker-children]').forEach(function (sel) {
            var active = sel.getAttribute('data-picker-children') === top.value;
            sel.hidden = !active;
            if (active && sel.value) value = sel.value;
        });
        picker.querySelector('[data-picker-value]').value = value;
    }

    document.addEventListener('change', function (e) {
        var picker = e.target.closest && e.target.closest('[data-idea-project-picker]');
        if (!picker) return;
        if (e.target.matches('[data-picker-top]')) {
            // A new platform starts from "toda la plataforma".
            picker.querySelectorAll('[data-picker-children]').forEach(function (sel) { sel.value = ''; });
        }
        sync(picker);
    });
})();
</script>
