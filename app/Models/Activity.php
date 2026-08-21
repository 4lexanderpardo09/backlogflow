<?php

namespace App\Models;

use App\Core\Model;

class Activity extends Model
{
    protected string $table = 'activities';

    private const DETAIL_SELECT = "SELECT a.*, b.description AS backlog_description, b.project_id,
                    p.name AS project_name, d.name AS developer_name,
                    pr.code AS priority_code, ast.code AS status_code,
                    dep.name AS depends_on_name, dep.progress_percent AS depends_on_progress,
                    (SELECT GROUP_CONCAT(cd.name ORDER BY cd.name SEPARATOR ', ')
                     FROM activity_developer ad JOIN developers cd ON cd.id = ad.developer_id
                     WHERE ad.activity_id = a.id) AS collaborator_names,
                    (SELECT GROUP_CONCAT(ad.developer_id ORDER BY ad.developer_id)
                     FROM activity_developer ad WHERE ad.activity_id = a.id) AS collaborator_ids
             FROM activities a
             JOIN backlog_items b ON b.id = a.backlog_item_id
             JOIN projects p ON p.id = b.project_id
             JOIN developers d ON d.id = a.developer_id
             JOIN cat_priorities pr ON pr.id = a.priority_id
             JOIN cat_activity_statuses ast ON ast.id = a.status_id
             LEFT JOIN activities dep ON dep.id = a.depends_on_activity_id";

    public function allWithDetails(): array
    {
        return $this->fetchAll(self::DETAIL_SELECT . ' ORDER BY a.due_date IS NULL, a.due_date ASC');
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->fetchOne(self::DETAIL_SELECT . ' WHERE a.id = :id', ['id' => $id]);
    }

    public function byBacklog(int $backlogItemId): array
    {
        return $this->fetchAll(
            self::DETAIL_SELECT . ' WHERE a.backlog_item_id = :backlog_item_id ORDER BY a.due_date IS NULL, a.due_date ASC',
            ['backlog_item_id' => $backlogItemId]
        );
    }

    /** Activities where this developer is the primary responsible OR an additional collaborator. */
    public function byDeveloper(int $developerId): array
    {
        return $this->fetchAll(
            self::DETAIL_SELECT . ' WHERE a.developer_id = :developer_id
                OR a.id IN (SELECT activity_id FROM activity_developer WHERE developer_id = :developer_id2)
             ORDER BY a.due_date IS NULL, a.due_date ASC',
            ['developer_id' => $developerId, 'developer_id2' => $developerId]
        );
    }

    public function additionalDevelopers(int $activityId): array
    {
        return $this->fetchAll(
            'SELECT d.id, d.name FROM activity_developer ad
             JOIN developers d ON d.id = ad.developer_id
             WHERE ad.activity_id = :id ORDER BY d.name ASC',
            ['id' => $activityId]
        );
    }

    /** @param int[] $developerIds */
    public function syncDevelopers(int $activityId, array $developerIds): void
    {
        $this->db->prepare('DELETE FROM activity_developer WHERE activity_id = :id')->execute(['id' => $activityId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO activity_developer (activity_id, developer_id) VALUES (:activity_id, :developer_id)');
        foreach (array_unique(array_map('intval', $developerIds)) as $developerId) {
            if ($developerId > 0) {
                $stmt->execute(['activity_id' => $activityId, 'developer_id' => $developerId]);
            }
        }
    }

    /** Options for the "depends on" dropdown: every activity except itself. */
    public function selectableDependencies(?int $excludeId = null): array
    {
        if ($excludeId === null) {
            return $this->fetchAll('SELECT id, name, developer_id FROM activities ORDER BY name ASC');
        }

        return $this->fetchAll(
            'SELECT id, name, developer_id FROM activities WHERE id != :id ORDER BY name ASC',
            ['id' => $excludeId]
        );
    }
}
