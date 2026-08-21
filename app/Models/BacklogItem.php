<?php

namespace App\Models;

use App\Core\Model;

class BacklogItem extends Model
{
    protected string $table = 'backlog_items';

    private const COLLABORATORS_SUBSELECT = "(SELECT GROUP_CONCAT(cd.name ORDER BY cd.name SEPARATOR ', ')
                     FROM backlog_item_developer bid JOIN developers cd ON cd.id = bid.developer_id
                     WHERE bid.backlog_item_id = b.id) AS collaborator_names,
                    (SELECT GROUP_CONCAT(bid.developer_id ORDER BY bid.developer_id)
                     FROM backlog_item_developer bid WHERE bid.backlog_item_id = b.id) AS collaborator_ids";

    public function allWithDetails(): array
    {
        return $this->fetchAll(
            'SELECT b.*, p.name AS project_name, d.name AS developer_name,
                    pr.code AS priority_code, bs.code AS status_code, bt.code AS type_code,
                    COALESCE(vb.progress_percent, 0) AS progress_percent,
                    ' . self::COLLABORATORS_SUBSELECT . '
             FROM backlog_items b
             JOIN projects p ON p.id = b.project_id
             JOIN developers d ON d.id = b.developer_id
             JOIN cat_priorities pr ON pr.id = b.priority_id
             JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             LEFT JOIN cat_backlog_types bt ON bt.id = b.type_id
             LEFT JOIN vw_backlog_progress vb ON vb.backlog_item_id = b.id
             ORDER BY p.name ASC, b.description ASC'
        );
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT b.*, p.name AS project_name, d.name AS developer_name,
                    pr.code AS priority_code, bs.code AS status_code, bt.code AS type_code,
                    COALESCE(vb.progress_percent, 0) AS progress_percent,
                    ' . self::COLLABORATORS_SUBSELECT . '
             FROM backlog_items b
             JOIN projects p ON p.id = b.project_id
             JOIN developers d ON d.id = b.developer_id
             JOIN cat_priorities pr ON pr.id = b.priority_id
             JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             LEFT JOIN cat_backlog_types bt ON bt.id = b.type_id
             LEFT JOIN vw_backlog_progress vb ON vb.backlog_item_id = b.id
             WHERE b.id = :id',
            ['id' => $id]
        );
    }

    public function bySprint(int $sprintId): array
    {
        return $this->fetchAll(
            'SELECT b.*, bs.code AS status_code, pr.code AS priority_code, d.name AS developer_name, COALESCE(vb.progress_percent, 0) AS progress_percent
             FROM backlog_items b
             JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             JOIN cat_priorities pr ON pr.id = b.priority_id
             JOIN developers d ON d.id = b.developer_id
             LEFT JOIN vw_backlog_progress vb ON vb.backlog_item_id = b.id
             WHERE b.sprint_id = :sprint_id
             ORDER BY b.description ASC',
            ['sprint_id' => $sprintId]
        );
    }

    /** Moves every backlog item still in $fromSprintId that isn't completed into $toSprintId — used when closing a sprint. */
    public function rollUnfinishedToSprint(int $fromSprintId, int $toSprintId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE backlog_items b JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             SET b.sprint_id = :to_sprint_id
             WHERE b.sprint_id = :from_sprint_id AND bs.code != 'completed'"
        );
        $stmt->execute(['to_sprint_id' => $toSprintId, 'from_sprint_id' => $fromSprintId]);

        return $stmt->rowCount();
    }

    public function additionalDevelopers(int $backlogItemId): array
    {
        return $this->fetchAll(
            'SELECT d.id, d.name FROM backlog_item_developer bid
             JOIN developers d ON d.id = bid.developer_id
             WHERE bid.backlog_item_id = :id ORDER BY d.name ASC',
            ['id' => $backlogItemId]
        );
    }

    /** @param int[] $developerIds */
    public function syncDevelopers(int $backlogItemId, array $developerIds): void
    {
        $this->db->prepare('DELETE FROM backlog_item_developer WHERE backlog_item_id = :id')->execute(['id' => $backlogItemId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO backlog_item_developer (backlog_item_id, developer_id) VALUES (:backlog_item_id, :developer_id)');
        foreach (array_unique(array_map('intval', $developerIds)) as $developerId) {
            if ($developerId > 0) {
                $stmt->execute(['backlog_item_id' => $backlogItemId, 'developer_id' => $developerId]);
            }
        }
    }

    public function byProject(int $projectId): array
    {
        return $this->fetchAll(
            'SELECT b.*, bs.code AS status_code, pr.code AS priority_code, COALESCE(vb.progress_percent, 0) AS progress_percent
             FROM backlog_items b
             JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             JOIN cat_priorities pr ON pr.id = b.priority_id
             LEFT JOIN vw_backlog_progress vb ON vb.backlog_item_id = b.id
             WHERE b.project_id = :project_id
             ORDER BY b.description ASC',
            ['project_id' => $projectId]
        );
    }
}
