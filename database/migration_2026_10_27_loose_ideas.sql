-- Migration 2026-10-27: ideas no longer need a project. A new idea often
-- comes before any project exists; it can be tied to one later, or when it
-- is converted into a backlog item. Mirrors database/schema.sql.

SET NAMES utf8mb4;

ALTER TABLE idea_notes
    MODIFY COLUMN project_id INT UNSIGNED NULL COMMENT 'Optional: NULL = loose idea, not tied to any project yet';
