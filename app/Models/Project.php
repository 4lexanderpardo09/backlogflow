<?php

namespace App\Models;

use App\Core\Model;

class Project extends Model
{
    protected string $table = 'projects';

    /**
     * All projects joined with lookup labels and computed progress from
     * vw_project_progress — the single source of truth for progress.
     */
    private const COLLABORATORS_SUBSELECT = "(SELECT GROUP_CONCAT(cd.name ORDER BY cd.name SEPARATOR ', ')
                     FROM project_developer pd JOIN developers cd ON cd.id = pd.developer_id
                     WHERE pd.project_id = p.id) AS collaborator_names,
                    (SELECT GROUP_CONCAT(pd.developer_id ORDER BY pd.developer_id)
                     FROM project_developer pd WHERE pd.project_id = p.id) AS collaborator_ids";

    public function allWithDetails(): array
    {
        return $this->fetchAll(
            'SELECT p.*, d.name AS developer_name,
                    pr.code AS priority_code, ps.code AS status_code,
                    COALESCE(vp.progress_percent, 0) AS progress_percent,
                    COALESCE(vp.activity_count, 0) AS activity_count,
                    ' . self::COLLABORATORS_SUBSELECT . '
             FROM projects p
             JOIN developers d ON d.id = p.developer_id
             JOIN cat_priorities pr ON pr.id = p.priority_id
             JOIN cat_project_statuses ps ON ps.id = p.status_id
             LEFT JOIN vw_project_progress vp ON vp.project_id = p.id
             ORDER BY p.name ASC'
        );
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, d.name AS developer_name,
                    pr.code AS priority_code, ps.code AS status_code,
                    COALESCE(vp.progress_percent, 0) AS progress_percent,
                    COALESCE(vp.activity_count, 0) AS activity_count,
                    ' . self::COLLABORATORS_SUBSELECT . '
             FROM projects p
             JOIN developers d ON d.id = p.developer_id
             JOIN cat_priorities pr ON pr.id = p.priority_id
             JOIN cat_project_statuses ps ON ps.id = p.status_id
             LEFT JOIN vw_project_progress vp ON vp.project_id = p.id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /** Additional developers collaborating on a project, beyond its primary developer_id. */
    public function additionalDevelopers(int $projectId): array
    {
        return $this->fetchAll(
            'SELECT d.id, d.name FROM project_developer pd
             JOIN developers d ON d.id = pd.developer_id
             WHERE pd.project_id = :id ORDER BY d.name ASC',
            ['id' => $projectId]
        );
    }

    /**
     * Replaces the full set of additional collaborators for a project.
     *
     * @param int[] $developerIds
     */
    public function syncDevelopers(int $projectId, array $developerIds): void
    {
        $this->db->prepare('DELETE FROM project_developer WHERE project_id = :id')->execute(['id' => $projectId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO project_developer (project_id, developer_id) VALUES (:project_id, :developer_id)');
        foreach (array_unique(array_map('intval', $developerIds)) as $developerId) {
            if ($developerId > 0) {
                $stmt->execute(['project_id' => $projectId, 'developer_id' => $developerId]);
            }
        }
    }

    /** Projects where this developer is the primary responsible OR an additional collaborator. */
    public function byDeveloper(int $developerId): array
    {
        return $this->fetchAll(
            'SELECT p.*, ps.code AS status_code, COALESCE(vp.progress_percent, 0) AS progress_percent
             FROM projects p
             JOIN cat_project_statuses ps ON ps.id = p.status_id
             LEFT JOIN vw_project_progress vp ON vp.project_id = p.id
             WHERE p.developer_id = :developer_id
                OR p.id IN (SELECT project_id FROM project_developer WHERE developer_id = :developer_id2)
             ORDER BY p.name ASC',
            ['developer_id' => $developerId, 'developer_id2' => $developerId]
        );
    }

    /** Count of overdue + open critical/high priority activities per project, for the traffic light. */
    public function riskCounters(int $projectId, string $today): array
    {
        $overdue = $this->fetchOne(
            "SELECT COUNT(*) AS n
             FROM activities a
             JOIN backlog_items b ON b.id = a.backlog_item_id
             WHERE b.project_id = :project_id
               AND a.progress_percent < 100
               AND a.due_date IS NOT NULL
               AND a.due_date < :today",
            ['project_id' => $projectId, 'today' => $today]
        );

        $critical = $this->fetchOne(
            "SELECT COUNT(*) AS n
             FROM activities a
             JOIN backlog_items b ON b.id = a.backlog_item_id
             JOIN cat_priorities pr ON pr.id = a.priority_id
             WHERE b.project_id = :project_id
               AND a.progress_percent < 100
               AND pr.code IN ('critical', 'high')",
            ['project_id' => $projectId]
        );

        return [
            'overdue_activities' => (int) ($overdue['n'] ?? 0),
            'critical_open_activities' => (int) ($critical['n'] ?? 0),
        ];
    }
}
