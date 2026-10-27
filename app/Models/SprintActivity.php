<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\SprintScope;

/**
 * Pure data access for sprint_activity: which activities are in each sprint,
 * how they got there and what happened to them at the close.
 */
class SprintActivity extends Model
{
    protected string $table = 'sprint_activity';

    private const DETAIL_SELECT = "SELECT a.*, sa.id AS membership_id, sa.sprint_id, sa.origin, sa.carried_from_sprint_id,
                    sa.committed_hours, sa.added_at, sa.added_by, sa.removed_at, sa.outcome, sa.outcome_reason,
                    cf.name AS carried_from_name, ab.name AS added_by_name,
                    b.description AS backlog_description, b.project_id, p.name AS project_name,
                    d.name AS developer_name, ast.code AS status_code, pr.code AS priority_code,
                    dep.progress_percent AS depends_on_progress
             FROM sprint_activity sa
             JOIN activities a ON a.id = sa.activity_id
             JOIN backlog_items b ON b.id = a.backlog_item_id
             JOIN projects p ON p.id = b.project_id
             JOIN developers d ON d.id = a.developer_id
             JOIN cat_activity_statuses ast ON ast.id = a.status_id
             JOIN cat_priorities pr ON pr.id = a.priority_id
             LEFT JOIN sprints cf ON cf.id = sa.carried_from_sprint_id
             LEFT JOIN developers ab ON ab.id = sa.added_by
             LEFT JOIN activities dep ON dep.id = a.depends_on_activity_id";

    /** Every activity that was ever in the sprint (removed and moved ones included), with its membership fields. */
    public function forSprint(int $sprintId): array
    {
        return $this->fetchAll(
            self::DETAIL_SELECT . ' WHERE sa.sprint_id = :id ORDER BY p.name ASC, b.description ASC, a.due_date IS NULL, a.due_date ASC, a.id ASC',
            ['id' => $sprintId]
        );
    }

    /** Activities still in the sprint (see SprintScope::isActive). */
    public function activeForSprint(int $sprintId): array
    {
        return array_values(array_filter($this->forSprint($sprintId), [SprintScope::class, 'isActive']));
    }

    /** @return array<int,int> activity_id => backlog_item_id of the activities still in the sprint */
    public function activeIds(int $sprintId): array
    {
        $rows = $this->fetchAll(
            "SELECT a.id, a.backlog_item_id FROM sprint_activity sa JOIN activities a ON a.id = sa.activity_id
             WHERE sa.sprint_id = :id AND sa.removed_at IS NULL AND (sa.outcome IS NULL OR sa.outcome = 'completed')",
            ['id' => $sprintId]
        );

        return array_map('intval', array_column($rows, 'backlog_item_id', 'id'));
    }

    /** Sprints an activity went through, oldest first. */
    public function historyForActivity(int $activityId): array
    {
        return $this->fetchAll(
            'SELECT sa.*, s.name AS sprint_name, s.start_date, s.end_date, s.status AS sprint_status,
                    cf.name AS carried_from_name, ab.name AS added_by_name
             FROM sprint_activity sa
             JOIN sprints s ON s.id = sa.sprint_id
             LEFT JOIN sprints cf ON cf.id = sa.carried_from_sprint_id
             LEFT JOIN developers ab ON ab.id = sa.added_by
             WHERE sa.activity_id = :id
             ORDER BY s.start_date ASC, sa.id ASC',
            ['id' => $activityId]
        );
    }

    /**
     * Activities that are in another OPEN sprint right now (an activity is in
     * one open sprint at a time).
     *
     * @param int[] $activityIds
     * @return array<int, array{sprint_id:int, sprint_name:string}> activity_id => sprint
     */
    public function inOtherOpenSprint(array $activityIds, ?int $exceptSprintId): array
    {
        $ids = array_values(array_filter(array_map('intval', $activityIds), fn (int $i) => $i > 0));
        if ($ids === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->fetchAll(
            "SELECT sa.activity_id, s.id AS sprint_id, COALESCE(s.name, CONCAT('Sprint #', s.id)) AS sprint_name
             FROM sprint_activity sa JOIN sprints s ON s.id = sa.sprint_id
             WHERE s.status = 'open' AND sa.removed_at IS NULL AND (sa.outcome IS NULL OR sa.outcome = 'completed')
               AND sa.activity_id IN ($in) AND s.id <> ?",
            [...$ids, (int) $exceptSprintId]
        );

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['activity_id']] = ['sprint_id' => (int) $r['sprint_id'], 'sprint_name' => $r['sprint_name']];
        }

        return $map;
    }

    /** Adds an activity to a sprint; one taken out earlier comes back with its original membership. */
    public function add(int $sprintId, int $activityId, string $origin, ?int $addedBy, ?int $carriedFrom = null): void
    {
        $this->db->prepare(
            'INSERT INTO sprint_activity (sprint_id, activity_id, origin, carried_from_sprint_id, added_by)
             VALUES (:sprint_id, :activity_id, :origin, :carried_from, :added_by)
             ON DUPLICATE KEY UPDATE removed_at = NULL, removed_by = NULL'
        )->execute([
            'sprint_id' => $sprintId, 'activity_id' => $activityId, 'origin' => $origin,
            'carried_from' => $carriedFrom, 'added_by' => $addedBy,
        ]);
    }

    /** Before the start an activity simply leaves the sprint. */
    public function deleteFrom(int $sprintId, int $activityId): void
    {
        $this->db->prepare('DELETE FROM sprint_activity WHERE sprint_id = :s AND activity_id = :a')
            ->execute(['s' => $sprintId, 'a' => $activityId]);
    }

    /** After the start it is only marked as taken out, so it still counts as committed. */
    public function markRemoved(int $sprintId, int $activityId, ?int $removedBy): void
    {
        $this->db->prepare('UPDATE sprint_activity SET removed_at = NOW(), removed_by = :by WHERE sprint_id = :s AND activity_id = :a')
            ->execute(['by' => $removedBy, 's' => $sprintId, 'a' => $activityId]);
    }

    public function setOutcome(int $sprintId, int $activityId, string $outcome, ?string $reason = null): void
    {
        $this->db->prepare('UPDATE sprint_activity SET outcome = :o, outcome_reason = :r WHERE sprint_id = :s AND activity_id = :a')
            ->execute(['o' => $outcome, 'r' => $reason, 's' => $sprintId, 'a' => $activityId]);
    }

    /** Reopening: the terminadas are back in play; moved/returned/cancelled ones already left. */
    public function clearCompletedOutcomes(int $sprintId): void
    {
        $this->db->prepare("UPDATE sprint_activity SET outcome = NULL WHERE sprint_id = :s AND outcome = 'completed'")
            ->execute(['s' => $sprintId]);
    }

    /**
     * Freezes the hours of the committed activities (not estimated = 1 h) and
     * returns their total. Only called while the sprint hasn't started.
     */
    public function commitHours(int $sprintId): float
    {
        $this->db->prepare(
            "UPDATE sprint_activity sa JOIN activities a ON a.id = sa.activity_id
             SET sa.committed_hours = COALESCE(a.estimated_hours, 1)
             WHERE sa.sprint_id = :s AND sa.origin <> 'added' AND sa.removed_at IS NULL"
        )->execute(['s' => $sprintId]);

        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(committed_hours), 0) AS total FROM sprint_activity
             WHERE sprint_id = :s AND origin <> 'added' AND removed_at IS NULL",
            ['s' => $sprintId]
        );

        return (float) ($row['total'] ?? 0);
    }

    /** sprint_backlog follows the backlogs of the sprint's activities (other screens read it). */
    public function syncBacklogs(int $sprintId): void
    {
        $this->db->prepare(
            'DELETE FROM sprint_backlog WHERE sprint_id = :s AND backlog_item_id NOT IN (
                SELECT backlog_item_id FROM (
                    SELECT a.backlog_item_id FROM sprint_activity sa JOIN activities a ON a.id = sa.activity_id
                    WHERE sa.sprint_id = :s2 AND sa.removed_at IS NULL) keep)'
        )->execute(['s' => $sprintId, 's2' => $sprintId]);

        $this->db->prepare(
            'INSERT IGNORE INTO sprint_backlog (sprint_id, backlog_item_id)
             SELECT DISTINCT sa.sprint_id, a.backlog_item_id FROM sprint_activity sa JOIN activities a ON a.id = sa.activity_id
             WHERE sa.sprint_id = :s AND sa.removed_at IS NULL'
        )->execute(['s' => $sprintId]);
    }

    /** @return array<int,int> sprint_id => activities arrastradas into it */
    public function carriedCounts(): array
    {
        $rows = $this->fetchAll('SELECT sprint_id, COUNT(*) AS n FROM sprint_activity WHERE carried_from_sprint_id IS NOT NULL GROUP BY sprint_id');

        return array_map('intval', array_column($rows, 'n', 'sprint_id'));
    }
}
