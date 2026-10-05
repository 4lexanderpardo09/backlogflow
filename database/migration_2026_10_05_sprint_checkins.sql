-- Migration 2026-10-05: weekly follow-up ("seguimiento semanal") of a sprint.
-- Each check-in stores a snapshot of the sprint at that moment (progress and
-- activity counts) plus the notes taken in the review. Mirrors the table
-- already folded into database/schema.sql.

SET NAMES utf8mb4;

CREATE TABLE sprint_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sprint_id INT UNSIGNED NOT NULL,
    checkin_date DATE NOT NULL,
    week_number TINYINT UNSIGNED NOT NULL COMMENT 'Sprint week (1..duration_weeks) the check-in date falls in',
    registered_by INT UNSIGNED NULL COMMENT 'Developer who ran/registered the review (no authentication yet)',
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Snapshot: sprint progress when the check-in was saved',
    activities_total SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    activities_done SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    activities_overdue SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    summary TEXT NOT NULL COMMENT 'How the sprint is going',
    blockers TEXT NULL,
    next_steps TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sprintcheckins_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE CASCADE,
    CONSTRAINT fk_sprintcheckins_dev FOREIGN KEY (registered_by) REFERENCES developers(id) ON DELETE SET NULL,
    INDEX idx_sprintcheckins_sprint (sprint_id, checkin_date)
) ENGINE=InnoDB;
