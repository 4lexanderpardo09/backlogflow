-- Migration 2026-10-28: sprint life cycle (edit, close wizard, next sprint, history).
--
-- 1. sprints.goal: the objective of the sprint.
-- 2. Two activity statuses between "En progreso" and "Terminada":
--    in_review ("En revisión (PR)", 90 %) and in_testing ("En pruebas (ST)", 95 %).
--    Only "Terminada" counts for cumplimiento.
-- 3. sprint_activity: which activities are in a sprint (until now a sprint only
--    held whole backlogs), how each one got there (committed at the start,
--    alcance agregado, arrastrada from another sprint, or heredada from before
--    this migration) and what happened to it at the close.
-- 4. sprint_closures: the snapshot saved when a sprint is closed. Never
--    updated afterwards except to record that it was reopened; closing again
--    saves a new row.
-- 5. Data: every activity of the backlogs already in a sprint becomes a
--    "heredada" member of that sprint (nobody recorded which ones were there at
--    the start). Sprints closed before this migration get no snapshot and their
--    heredadas no outcome: that information was never recorded.
--
-- Nothing existing is deleted or modified (sprint_backlog stays as it is).
-- Mirrors database/schema.sql and database/catalogs.sql.

SET NAMES utf8mb4;

ALTER TABLE sprints
    ADD COLUMN goal TEXT NULL COMMENT 'Objective of the sprint' AFTER name;

-- New statuses, ordered between in_progress and completed.
UPDATE cat_activity_statuses SET sort_order = sort_order + 2 WHERE sort_order >= 3;
INSERT INTO cat_activity_statuses (code, sort_order) VALUES ('in_review', 3), ('in_testing', 4);

CREATE TABLE sprint_activity (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sprint_id INT UNSIGNED NOT NULL,
    activity_id INT UNSIGNED NOT NULL,
    origin ENUM('committed','added','carried','legacy') NOT NULL
        COMMENT 'committed = in the sprint at its start; added = alcance agregado after the start; carried = moved from carried_from_sprint_id at its close; legacy = in the sprint before 2026-10-28 (counts as committed)',
    carried_from_sprint_id INT UNSIGNED NULL,
    committed_hours DECIMAL(6,2) NULL COMMENT 'Estimated hours when the sprint started (committed/carried rows); NULL = use the current hours',
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by INT UNSIGNED NULL COMMENT 'Developer who added it (no authentication yet)',
    removed_at DATETIME NULL COMMENT 'Taken out of the sprint after its start (kept so it still counts as committed)',
    removed_by INT UNSIGNED NULL,
    outcome ENUM('completed','moved','returned','cancelled') NULL COMMENT 'Set when the sprint is closed (returned = back to the backlog, no sprint)',
    outcome_reason TEXT NULL COMMENT 'Mandatory when cancelled',
    UNIQUE KEY uq_sprint_activity (sprint_id, activity_id),
    INDEX idx_sprint_activity_activity (activity_id),
    CONSTRAINT fk_sprintact_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE,
    CONSTRAINT fk_sprintact_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_sprintact_from FOREIGN KEY (carried_from_sprint_id) REFERENCES sprints(id) ON DELETE SET NULL,
    CONSTRAINT fk_sprintact_added_by FOREIGN KEY (added_by) REFERENCES developers(id) ON DELETE SET NULL,
    CONSTRAINT fk_sprintact_removed_by FOREIGN KEY (removed_by) REFERENCES developers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sprint_closures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sprint_id INT UNSIGNED NOT NULL,
    closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by INT UNSIGNED NULL,
    committed_count SMALLINT UNSIGNED NULL COMMENT 'Activities committed at the start; NULL = not known',
    committed_hours DECIMAL(8,2) NULL,
    committed_from_legacy TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = the commitment was not recorded at the start; the activities heredadas were used',
    added_count SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Alcance agregado after the start',
    added_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    added_completed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    committed_completed_hours DECIMAL(8,2) NULL COMMENT 'Hours of the committed activities that were terminadas: numerator of cumplimiento',
    moved_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    returned_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    cancelled_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    removed_count SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Committed activities taken out after the start',
    progress_percent DECIMAL(5,2) NULL COMMENT 'Avance at the close',
    compliance_percent DECIMAL(5,2) NULL COMMENT 'committed_completed_hours / committed_hours',
    next_sprint_id INT UNSIGNED NULL COMMENT 'Sprint the moved activities went to',
    reopened_at DATETIME NULL,
    reopened_reason TEXT NULL,
    INDEX idx_sprint_closures_sprint (sprint_id),
    CONSTRAINT fk_closure_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE,
    CONSTRAINT fk_closure_closed_by FOREIGN KEY (closed_by) REFERENCES developers(id) ON DELETE SET NULL,
    CONSTRAINT fk_closure_next FOREIGN KEY (next_sprint_id) REFERENCES sprints(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Heredadas: every activity of the backlogs already in a sprint, added when
-- the activity was created (or when the sprint was, if later).
INSERT INTO sprint_activity (sprint_id, activity_id, origin, added_at)
SELECT sb.sprint_id, a.id, 'legacy', GREATEST(a.created_at, s.created_at)
FROM sprint_backlog sb
JOIN sprints s ON s.id = sb.sprint_id
JOIN activities a ON a.backlog_item_id = sb.backlog_item_id;

