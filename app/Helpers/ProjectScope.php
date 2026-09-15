<?php

namespace App\Helpers;

/**
 * Resolves the two-level project filter used by the list pages: first a
 * top-level project (a platform or a project without parent), then — only
 * for a platform — one of its sub-projects. A platform has no backlog of its
 * own, so picking it alone must mean "all of its sub-projects", not nothing.
 */
class ProjectScope
{
    /** Platforms and standalone projects: the options of the first select. */
    public static function topLevel(array $projects): array
    {
        return array_values(array_filter($projects, fn (array $p) => (int) ($p['parent_id'] ?? 0) === 0));
    }

    public static function childrenOf(array $projects, int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        return array_values(array_filter($projects, fn (array $p) => (int) ($p['parent_id'] ?? 0) === $parentId));
    }

    /**
     * Project ids a list must be limited to, or null when no project is picked.
     * A sub-project that doesn't belong to the picked parent (stale after the
     * parent changed) is ignored, falling back to the whole parent.
     *
     * @return int[]|null
     */
    public static function ids(array $projects, int $projectId, int $childId): ?array
    {
        if ($projectId <= 0) {
            return null;
        }

        $childIds = array_map(fn (array $c) => (int) $c['id'], self::childrenOf($projects, $projectId));

        if ($childId > 0 && in_array($childId, $childIds, true)) {
            return [$childId];
        }

        return [$projectId, ...$childIds];
    }
}
