-- Migration 2026-08-27: project parent/child hierarchy + sprint redesign
-- (manual multi-project / multi-backlog sprints) + Cronograma support.
--
-- Safe to run once on an existing database. Mirrors changes already folded
-- into database/schema.sql.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. Projects: parent/child (platform) hierarchy
-- -------------------------------------------------------------------------
-- parent_id  : a small "sub-project" (module / update / fix) points at its
--              platform project. NULL = standalone or platform itself.
-- is_platform: 1 marks the "proyecto padre / plataforma". A platform has no
--              end date; its progress and traffic light roll up from children.
ALTER TABLE projects
    ADD COLUMN parent_id INT UNSIGNED NULL AFTER id,
    ADD COLUMN is_platform TINYINT(1) NOT NULL DEFAULT 0 AFTER parent_id,
    ADD CONSTRAINT fk_projects_parent FOREIGN KEY (parent_id) REFERENCES projects(id) ON DELETE SET NULL;

-- -------------------------------------------------------------------------
-- 2. Sprints: manual editing, no longer bound to a single project
-- -------------------------------------------------------------------------
-- A sprint is now a manually created cycle with a name and a duration in
-- weeks, spanning any number of child projects and pulling backlog items
-- from any of them. project_id / sequence_number are kept nullable only so
-- the historical row survives; new sprints leave them NULL.
ALTER TABLE sprints
    ADD COLUMN name VARCHAR(150) NULL AFTER id,
    ADD COLUMN duration_weeks TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER end_date;

ALTER TABLE sprints ADD INDEX idx_sprints_project (project_id);
ALTER TABLE sprints DROP INDEX uq_project_sequence;
ALTER TABLE sprints
    MODIFY COLUMN project_id INT UNSIGNED NULL,
    MODIFY COLUMN sequence_number SMALLINT UNSIGNED NULL;

-- Sprint <-> child projects (many-to-many)
CREATE TABLE sprint_project (
    sprint_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (sprint_id, project_id),
    CONSTRAINT fk_sprintproj_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE,
    CONSTRAINT fk_sprintproj_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sprint <-> backlog items (many-to-many). Replaces backlog_items.sprint_id
-- as the source of truth; the old column is left in place but unused.
CREATE TABLE sprint_backlog (
    sprint_id INT UNSIGNED NOT NULL,
    backlog_item_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (sprint_id, backlog_item_id),
    CONSTRAINT fk_sprintbl_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE,
    CONSTRAINT fk_sprintbl_backlog FOREIGN KEY (backlog_item_id) REFERENCES backlog_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Carry any pre-existing single-sprint assignments into the new bridge table.
INSERT IGNORE INTO sprint_backlog (sprint_id, backlog_item_id)
SELECT sprint_id, id FROM backlog_items WHERE sprint_id IS NOT NULL;

INSERT IGNORE INTO sprint_project (sprint_id, project_id)
SELECT id, project_id FROM sprints WHERE project_id IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
