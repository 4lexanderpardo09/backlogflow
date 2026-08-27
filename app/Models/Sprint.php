<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\SprintProgress;

/**
 * A sprint is a manually managed review cycle: a name, a start date, a
 * duration in weeks, a set of child projects (sprint_project) and a set of
 * backlog items pulled from any of them (sprint_backlog). Its completion %
 * is the activity-weighted progress of those backlog items.
 */
class Sprint extends Model
{
    protected string $table = 'sprints';

    /** All sprints with a comma-joined project list, newest first. */
    public function allWithMeta(): array
    {
        return $this->fetchAll(
            "SELECT s.*,
                    (SELECT GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ')
                     FROM sprint_project sp JOIN projects p ON p.id = sp.project_id
                     WHERE sp.sprint_id = s.id) AS project_names
             FROM sprints s
             ORDER BY s.start_date DESC, s.id DESC"
        );
    }

    /** Distinct sprints that contain at least one backlog item from the given projects. */
    public function forProjectIds(array $projectIds): array
    {
        $projectIds = array_values(array_filter(array_map('intval', $projectIds), fn (int $i) => $i > 0));
        if ($projectIds === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($projectIds), '?'));

        return $this->fetchAll(
            "SELECT DISTINCT s.*
             FROM sprints s
             JOIN sprint_backlog sb ON sb.sprint_id = s.id
             JOIN backlog_items b ON b.id = sb.backlog_item_id
             WHERE b.project_id IN ($in)
             ORDER BY s.start_date ASC, s.id ASC",
            $projectIds
        );
    }

    public function projects(int $sprintId): array
    {
        return $this->fetchAll(
            'SELECT p.id, p.name FROM sprint_project sp
             JOIN projects p ON p.id = sp.project_id
             WHERE sp.sprint_id = :id ORDER BY p.name ASC',
            ['id' => $sprintId]
        );
    }

    /** @return int[] */
    public function projectIds(int $sprintId): array
    {
        return array_map('intval', array_column($this->projects($sprintId), 'id'));
    }

    /** Backlog items in the sprint, with computed progress + activity count. */
    public function backlogs(int $sprintId): array
    {
        return $this->fetchAll(
            'SELECT b.*, p.name AS project_name, bs.code AS status_code,
                    pr.code AS priority_code, d.name AS developer_name,
                    COALESCE(vb.progress_percent, 0) AS progress_percent,
                    COALESCE(vb.activity_count, 0) AS activity_count
             FROM sprint_backlog sb
             JOIN backlog_items b ON b.id = sb.backlog_item_id
             JOIN projects p ON p.id = b.project_id
             JOIN cat_backlog_statuses bs ON bs.id = b.status_id
             JOIN cat_priorities pr ON pr.id = b.priority_id
             JOIN developers d ON d.id = b.developer_id
             LEFT JOIN vw_backlog_progress vb ON vb.backlog_item_id = b.id
             WHERE sb.sprint_id = :id
             ORDER BY p.name ASC, b.description ASC',
            ['id' => $sprintId]
        );
    }

    /** Sprints a given backlog item belongs to. */
    public function forBacklog(int $backlogItemId): array
    {
        return $this->fetchAll(
            'SELECT s.* FROM sprint_backlog sb JOIN sprints s ON s.id = sb.sprint_id
             WHERE sb.backlog_item_id = :id ORDER BY s.start_date DESC',
            ['id' => $backlogItemId]
        );
    }

    /** @return int[] */
    public function backlogIds(int $sprintId): array
    {
        $rows = $this->fetchAll('SELECT backlog_item_id FROM sprint_backlog WHERE sprint_id = :id', ['id' => $sprintId]);

        return array_map('intval', array_column($rows, 'backlog_item_id'));
    }

    /** @param int[] $projectIds */
    public function syncProjects(int $sprintId, array $projectIds): void
    {
        $this->db->prepare('DELETE FROM sprint_project WHERE sprint_id = :id')->execute(['id' => $sprintId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO sprint_project (sprint_id, project_id) VALUES (:sprint_id, :project_id)');
        foreach (array_unique(array_map('intval', $projectIds)) as $projectId) {
            if ($projectId > 0) {
                $stmt->execute(['sprint_id' => $sprintId, 'project_id' => $projectId]);
            }
        }
    }

    /** @param int[] $backlogItemIds */
    public function syncBacklogs(int $sprintId, array $backlogItemIds): void
    {
        $this->db->prepare('DELETE FROM sprint_backlog WHERE sprint_id = :id')->execute(['id' => $sprintId]);

        $stmt = $this->db->prepare('INSERT IGNORE INTO sprint_backlog (sprint_id, backlog_item_id) VALUES (:sprint_id, :backlog_item_id)');
        foreach (array_unique(array_map('intval', $backlogItemIds)) as $backlogItemId) {
            if ($backlogItemId > 0) {
                $stmt->execute(['sprint_id' => $sprintId, 'backlog_item_id' => $backlogItemId]);
            }
        }
    }

    /** Live activity-weighted completion of a sprint's backlog items. */
    public function completionFor(int $sprintId): float
    {
        return SprintProgress::completionPercent($this->backlogs($sprintId));
    }
}
