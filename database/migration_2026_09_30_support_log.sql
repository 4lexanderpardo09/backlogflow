-- Migration 2026-09-30: daily support log ("Registro de soporte diario").
-- Records the everyday requests the team attends (printer, mouse, Excel...)
-- that never go through the help desk, plus whether each one did.
-- Mirrors the table already folded into database/schema.sql.

SET NAMES utf8mb4;

CREATE TABLE support_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_date DATE NOT NULL,
    developer_id INT UNSIGNED NOT NULL COMMENT 'Who attended and registered it (no authentication yet)',
    requester VARCHAR(150) NULL COMMENT 'Person who asked for the help',
    requester_area VARCHAR(120) NULL,
    category VARCHAR(30) NOT NULL DEFAULT 'other' COMMENT 'hardware | software | office | network | access | other',
    description TEXT NOT NULL,
    in_helpdesk TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = also registered in the help desk',
    helpdesk_ticket VARCHAR(50) NULL,
    time_minutes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Time taken to solve it',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supportlogs_developer FOREIGN KEY (developer_id) REFERENCES developers(id),
    INDEX idx_supportlogs_date (log_date)
) ENGINE=InnoDB;
