<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pure data access for the developers table. No business logic lives here.
 */
class Developer extends Model
{
    protected string $table = 'developers';

    /**
     * One row per developer for the list screen. (Previously LEFT JOINed
     * projects, which fanned a developer out to one row per project they
     * lead — and the joined project_name was never even used.)
     */
    public function allForList(): array
    {
        return $this->fetchAll('SELECT d.* FROM developers d ORDER BY d.name ASC');
    }

    public function isReferenced(int $id): bool
    {
        // Native (non-emulated) prepared statements don't allow reusing the
        // same named placeholder twice, so each subquery gets its own.
        $row = $this->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM projects WHERE developer_id = :id1)
                + (SELECT COUNT(*) FROM backlog_items WHERE developer_id = :id2)
                + (SELECT COUNT(*) FROM activities WHERE developer_id = :id3)
                + (SELECT COUNT(*) FROM project_developer WHERE developer_id = :id4)
                + (SELECT COUNT(*) FROM backlog_item_developer WHERE developer_id = :id5)
                + (SELECT COUNT(*) FROM activity_developer WHERE developer_id = :id6) AS total',
            ['id1' => $id, 'id2' => $id, 'id3' => $id, 'id4' => $id, 'id5' => $id, 'id6' => $id]
        );

        return (int) ($row['total'] ?? 0) > 0;
    }
}
