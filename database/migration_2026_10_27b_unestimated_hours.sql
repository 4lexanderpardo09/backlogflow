-- Migration 2026-10-27b: "sin horas estimadas" and "calculado con horas actuales".
--
-- 1. estimated_hours becomes optional: NULL = not estimated yet, which still
--    counts as 1 h in every roll-up but can be listed so someone completes it.
--    The 1.00 the previous migration gave every existing activity was only a
--    placeholder, so those go back to NULL.
-- 2. Sprints created before the hours rule have no committed hours recorded
--    at their start (the recalculation script had filled them with the hours
--    they held that day): back to NULL, so their cumplimiento is computed with
--    the current hours and says so.
-- Mirrors database/schema.sql.

SET NAMES utf8mb4;

ALTER TABLE activities
    MODIFY COLUMN estimated_hours DECIMAL(6,2) NULL DEFAULT NULL
        COMMENT 'Size of the activity: weight of its progress in every roll-up. NULL = not estimated yet (counts as 1 h)';

-- updated_at = updated_at: this is not a user edit, keep each row's real last-edit date.
UPDATE activities SET estimated_hours = NULL, updated_at = updated_at WHERE estimated_hours = 1.00;

UPDATE sprints SET committed_hours = NULL, updated_at = updated_at WHERE created_at < '2026-10-27 00:00:00';

-- Computed progress views: the single source of truth for backlog/project
-- progress. Application code (Services layer) reads these instead of
-- re-implementing the math in PHP, so dashboard and listings never diverge.
--
-- Progress = SUM(estimated_hours * progress_percent) / SUM(estimated_hours)
-- over ALL the activities a backlog / project contains (never the average of
-- its children's percentages). Cancelled activities are left out entirely;
-- an activity not estimated yet (estimated_hours NULL) counts as 1 h (only
-- real activity rows: a backlog/project without activities adds 0 h).
-- overdue_count / oldest_open_due feed the "N vencidas" tag and the days of
-- delay (today - oldest due date among the unfinished activities).
CREATE OR REPLACE VIEW vw_backlog_progress AS
SELECT
    b.id AS backlog_item_id,
    b.project_id,
    COUNT(a.id) AS activity_count,
    COALESCE(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1))), 0) AS hours_total,
    COALESCE(ROUND(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1)) * a.progress_percent) / NULLIF(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1))), 0), 2), 0) AS progress_percent,
    COALESCE(SUM(s.code <> 'completed' AND a.due_date < CURDATE()), 0) AS overdue_count,
    MIN(CASE WHEN s.code <> 'completed' THEN a.due_date END) AS oldest_open_due
FROM backlog_items b
LEFT JOIN (activities a JOIN cat_activity_statuses s ON s.id = a.status_id AND s.code <> 'cancelled')
       ON a.backlog_item_id = b.id
GROUP BY b.id, b.project_id;

CREATE OR REPLACE VIEW vw_project_progress AS
SELECT
    p.id AS project_id,
    COUNT(a.id) AS activity_count,
    COALESCE(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1))), 0) AS hours_total,
    COALESCE(ROUND(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1)) * a.progress_percent) / NULLIF(SUM(IF(a.id IS NULL, NULL, COALESCE(a.estimated_hours, 1))), 0), 2), 0) AS progress_percent,
    COALESCE(SUM(s.code <> 'completed' AND a.due_date < CURDATE()), 0) AS overdue_count,
    MIN(CASE WHEN s.code <> 'completed' THEN a.due_date END) AS oldest_open_due
FROM projects p
LEFT JOIN backlog_items b ON b.project_id = p.id
LEFT JOIN (activities a JOIN cat_activity_statuses s ON s.id = a.status_id AND s.code <> 'cancelled')
       ON a.backlog_item_id = b.id
GROUP BY p.id;
