<?php

namespace App\Models;

use App\Core\Model;

class BacklogItem extends Model
{
    protected string $table = 'backlog_items';

    public function allWithDetails(): array
    {
        return $this->fetchAll(
            'SELECT b.*, p.name AS project_name, d.name AS developer_name,
                    pr.code AS priority_code, bs.code AS status_code, bt.code AS type_code,
                    COALESCE(vb.progress_percent, 0) AS progress_percent
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
                    COALESCE(vb.progress_percent, 0) AS progress_percent
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
