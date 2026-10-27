-- Migration 2026-10-26: progress weighted by estimated hours, computed
-- statuses, delay and sprint cumplimiento. Mirrors database/schema.sql.
--
-- Schema only: existing activities get estimated_hours = 1 (so nothing
-- changes size until someone estimates them). Recomputing the stored
-- statuses of backlogs/projects and the sprints' committed hours is done
-- afterwards by database/recalculate_progress.php (--dry-run first).

SET NAMES utf8mb4;

ALTER TABLE activities
    ADD COLUMN estimated_hours DECIMAL(6,2) NOT NULL DEFAULT 1.00
        COMMENT 'Size of the activity: weight of its progress in every roll-up (backlog, project, sprint)'
        AFTER progress_percent,
    ADD CONSTRAINT chk_estimated_hours CHECK (estimated_hours > 0);

ALTER TABLE sprints
    MODIFY COLUMN completion_percent DECIMAL(5,2) NULL COMMENT 'Frozen on close: avance (hours-weighted progress) of the sprint backlog items',
    ADD COLUMN committed_hours DECIMAL(8,2) NULL COMMENT 'Hours committed when the sprint started: the denominator of cumplimiento' AFTER completion_percent,
    ADD COLUMN compliance_percent DECIMAL(5,2) NULL COMMENT 'Frozen on close: cumplimiento = hours of terminadas / committed_hours' AFTER committed_hours;

-- Computed progress views: the single source of truth for backlog/project
-- progress. Application code (Services layer) reads these instead of
-- re-implementing the math in PHP, so dashboard and listings never diverge.
--
-- Progress = SUM(estimated_hours * progress_percent) / SUM(estimated_hours)
-- over ALL the activities a backlog / project contains (never the average of
-- its children's percentages). Cancelled activities are left out entirely.
-- overdue_count / oldest_open_due feed the "N vencidas" tag and the days of
-- delay (today - oldest due date among the unfinished activities).
CREATE OR REPLACE VIEW vw_backlog_progress AS
SELECT
    b.id AS backlog_item_id,
    b.project_id,
    COUNT(a.id) AS activity_count,
    COALESCE(SUM(a.estimated_hours), 0) AS hours_total,
    COALESCE(ROUND(SUM(a.estimated_hours * a.progress_percent) / NULLIF(SUM(a.estimated_hours), 0), 2), 0) AS progress_percent,
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
    COALESCE(SUM(a.estimated_hours), 0) AS hours_total,
    COALESCE(ROUND(SUM(a.estimated_hours * a.progress_percent) / NULLIF(SUM(a.estimated_hours), 0), 2), 0) AS progress_percent,
    COALESCE(SUM(s.code <> 'completed' AND a.due_date < CURDATE()), 0) AS overdue_count,
    MIN(CASE WHEN s.code <> 'completed' THEN a.due_date END) AS oldest_open_due
FROM projects p
LEFT JOIN backlog_items b ON b.project_id = p.id
LEFT JOIN (activities a JOIN cat_activity_statuses s ON s.id = a.status_id AND s.code <> 'cancelled')
       ON a.backlog_item_id = b.id
GROUP BY p.id;
