<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pure data access for the developers table. No business logic lives here.
 */
class Developer extends Model
{
    protected string $table = 'developers';

    public function allWithProjectName(): array
    {
        return $this->fetchAll(
            'SELECT d.*, p.name AS project_name
             FROM developers d
             LEFT JOIN projects p ON p.developer_id = d.id
             ORDER BY d.name ASC'
        );
    }

    public function isReferenced(int $id): bool
    {
        // Native (non-emulated) prepared statements don't allow reusing the
        // same named placeholder twice, so each subquery gets its own.
        $row = $this->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM projects WHERE developer_id = :id1)
                + (SELECT COUNT(*) FROM backlog_items WHERE developer_id = :id2)
                + (SELECT COUNT(*) FROM activities WHERE developer_id = :id3) AS total',
            ['id1' => $id, 'id2' => $id, 'id3' => $id]
        );

        return (int) ($row['total'] ?? 0) > 0;
    }
}
