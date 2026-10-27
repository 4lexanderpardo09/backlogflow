<?php

namespace App\Models;

use App\Core\Model;

class IdeaNote extends Model
{
    protected string $table = 'idea_notes';

    // LEFT JOIN: an idea may not belong to any project yet (project_id NULL).
    private const DETAIL_SELECT = 'SELECT n.*, p.name AS project_name
             FROM idea_notes n LEFT JOIN projects p ON p.id = n.project_id';

    public function allWithDetails(): array
    {
        return $this->fetchAll(self::DETAIL_SELECT . ' ORDER BY n.created_at DESC');
    }

    public function byProject(int $projectId): array
    {
        return $this->fetchAll(
            self::DETAIL_SELECT . ' WHERE n.project_id = :project_id ORDER BY n.created_at DESC',
            ['project_id' => $projectId]
        );
    }

    /** Ideas not tied to any project yet. */
    public function withoutProject(): array
    {
        return $this->fetchAll(self::DETAIL_SELECT . ' WHERE n.project_id IS NULL ORDER BY n.created_at DESC');
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->fetchOne(self::DETAIL_SELECT . ' WHERE n.id = :id', ['id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->update($id, ['status' => $status]);
    }

    public function markConverted(int $id, int $backlogItemId): void
    {
        $this->update($id, ['status' => 'converted', 'backlog_item_id' => $backlogItemId]);
    }
}
