<?php

namespace App\Models;

use App\Core\Model;

class IdeaNote extends Model
{
    protected string $table = 'idea_notes';

    // LEFT JOIN: an idea may not belong to any project yet (project_id NULL).
    private const DETAIL_SELECT = 'SELECT n.*, p.name AS project_name, pp.name AS parent_name
             FROM idea_notes n
             LEFT JOIN projects p ON p.id = n.project_id
             LEFT JOIN projects pp ON pp.id = p.parent_id';

    public function allWithDetails(): array
    {
        return $this->fetchAll(self::DETAIL_SELECT . ' ORDER BY n.created_at DESC');
    }

    /**
     * Ideas of several projects (a platform together with its sub-projects).
     *
     * @param int[] $projectIds
     */
    public function byProjectIds(array $projectIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $projectIds), fn (int $i) => $i > 0));
        if ($ids === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));

        return $this->fetchAll(self::DETAIL_SELECT . " WHERE n.project_id IN ($in) ORDER BY n.created_at DESC", $ids);
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
