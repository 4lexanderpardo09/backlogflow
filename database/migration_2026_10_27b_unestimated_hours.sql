-- Migration 2026-10-27: activities can be "sin horas estimadas".
-- Mirrors database/schema.sql. Run AFTER migration_2026_10_26_hours_weighted_progress.sql
-- and BEFORE `php database/recalculate_progress.php --apply`.
--
-- estimated_hours becomes NULL-able: NULL = not estimated yet (still weighs
-- 1 h in every roll-up, so no percentage changes). The 2026-10-26 migration
-- gave every existing activity a placeholder 1.00, indistinguishable from a
-- real estimate, so every 1.00 goes back to NULL and shows up in the
-- "sin horas estimadas" count to be completed with the size selector
-- (an activity that really is 1 h just needs XS picked again).

SET NAMES utf8mb4;

ALTER TABLE activities
    MODIFY COLUMN estimated_hours DECIMAL(6,2) NULL
        COMMENT 'Size of the activity: weight of its progress in every roll-up (backlog, project, sprint); NULL = not estimated yet, weighs 1 h';

-- updated_at is kept: this is a data fix, not an edit by anyone.
UPDATE activities SET estimated_hours = NULL, updated_at = updated_at WHERE estimated_hours = 1.00;

-- Same views as 2026-10-26, reading a missing estimate as 1 h.
CREATE OR REPLACE VIEW vw_backlog_progress AS
SELECT
    b.id AS backlog_item_id,
    b.project_id,
    COUNT(a.id) AS activity_count,
    COALESCE(SUM(COALESCE(a.estimated_hours, 1)), 0) AS hours_total,
    COALESCE(ROUND(SUM(COALESCE(a.estimated_hours, 1) * a.progress_percent) / NULLIF(SUM(COALESCE(a.estimated_hours, 1)), 0), 2), 0) AS progress_percent,
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
    COALESCE(SUM(COALESCE(a.estimated_hours, 1)), 0) AS hours_total,
    COALESCE(ROUND(SUM(COALESCE(a.estimated_hours, 1) * a.progress_percent) / NULLIF(SUM(COALESCE(a.estimated_hours, 1)), 0), 2), 0) AS progress_percent,
    COALESCE(SUM(s.code <> 'completed' AND a.due_date < CURDATE()), 0) AS overdue_count,
    MIN(CASE WHEN s.code <> 'completed' THEN a.due_date END) AS oldest_open_due
FROM projects p
LEFT JOIN backlog_items b ON b.project_id = p.id
LEFT JOIN (activities a JOIN cat_activity_statuses s ON s.id = a.status_id AND s.code <> 'cancelled')
       ON a.backlog_item_id = b.id
GROUP BY p.id;
