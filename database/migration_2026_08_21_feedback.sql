-- Migration: feedback round (dashboard colors, multi-desarrollador, notas,
-- sprints, niveles de soporte ANS, tablero de ideas).
-- Run this ONCE against an existing backlogflow database that already has
-- schema.sql + catalogs.sql applied. It only adds columns/tables — nothing
-- is dropped and no existing data is touched.
--
--   mysql -u root -p backlogflow < database/migration_2026_08_21_feedback.sql

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1) ANS: nivel (1/2) por tipo de soporte
-- ---------------------------------------------------------------------
ALTER TABLE cat_support_types
  ADD COLUMN level TINYINT UNSIGNED NOT NULL DEFAULT 2
    COMMENT '1 = creación de usuarios, permisos, etc.; 2 = otros soportes propios de la aplicación',
  ADD CONSTRAINT chk_support_type_level CHECK (level IN (1, 2));

UPDATE cat_support_types SET level = 1 WHERE code IN ('user_administration', 'permission_administration');

-- ---------------------------------------------------------------------
-- 2) Multi-desarrollador en Backlog y Actividades
--    (project_developer ya existía en el esquema, solo se activa en código)
-- ---------------------------------------------------------------------
CREATE TABLE backlog_item_developer (
    backlog_item_id INT UNSIGNED NOT NULL,
    developer_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (backlog_item_id, developer_id),
    CONSTRAINT fk_bid_backlog FOREIGN KEY (backlog_item_id) REFERENCES backlog_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_bid_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE activity_developer (
    activity_id INT UNSIGNED NOT NULL,
    developer_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (activity_id, developer_id),
    CONSTRAINT fk_ad_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3) Sprints por proyecto
-- ---------------------------------------------------------------------
ALTER TABLE projects
  ADD COLUMN sprint_duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 8
    COMMENT 'Cycle length in days between backlog review meetings with process owners'
    AFTER priority_id;

CREATE TABLE sprints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    sequence_number SMALLINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    process_owner VARCHAR(150) NULL COMMENT 'Person/area that owns the process reviewed in this sprint meeting',
    completion_percent DECIMAL(5,2) NULL COMMENT 'Filled in when the sprint is closed: % of its backlog items completed',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sprints_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uq_project_sequence (project_id, sequence_number)
) ENGINE=InnoDB;

ALTER TABLE backlog_items
  ADD COLUMN sprint_id INT UNSIGNED NULL AFTER project_id,
  ADD CONSTRAINT fk_backlog_sprint FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- 4) Tablero de ideas/notas estilo Trello
-- ---------------------------------------------------------------------
CREATE TABLE idea_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    status ENUM('new','clarifying','ready','converted') NOT NULL DEFAULT 'new',
    created_by VARCHAR(150) NULL,
    backlog_item_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_idea_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_idea_backlog FOREIGN KEY (backlog_item_id) REFERENCES backlog_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
