<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pure data access for sprint_closures: the snapshot saved each time a
 * sprint is closed. Rows are never changed afterwards, except to record a reopening.
 */
class SprintClosure extends Model
{
    protected string $table = 'sprint_closures';

    /** Closures of a sprint, newest first, with who closed it and where the moved activities went. */
    public function forSprint(int $sprintId): array
    {
        return $this->fetchAll(
            "SELECT c.*, d.name AS closed_by_name, COALESCE(n.name, CONCAT('Sprint #', n.id)) AS next_sprint_name
             FROM sprint_closures c
             LEFT JOIN developers d ON d.id = c.closed_by
             LEFT JOIN sprints n ON n.id = c.next_sprint_id
             WHERE c.sprint_id = :id ORDER BY c.closed_at DESC, c.id DESC",
            ['id' => $sprintId]
        );
    }

    /** @return array<int, array> sprint_id => its latest closure */
    public function latestBySprint(): array
    {
        $rows = $this->fetchAll(
            'SELECT c.* FROM sprint_closures c
             JOIN (SELECT sprint_id, MAX(id) AS id FROM sprint_closures GROUP BY sprint_id) last ON last.id = c.id'
        );

        return array_column($rows, null, 'sprint_id');
    }

    public function markReopened(int $closureId, string $reason): void
    {
        $this->db->prepare('UPDATE sprint_closures SET reopened_at = NOW(), reopened_reason = :r WHERE id = :id')
            ->execute(['r' => $reason, 'id' => $closureId]);
    }
}
