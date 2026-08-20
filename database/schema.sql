-- BacklogFlow database schema (MariaDB)
-- Two modules: Projects/Backlog/Activities tracking, and Application SLA management.
-- All identifiers are in English; Spanish labels live in application code (app/Helpers/Labels.php).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================================
-- CATALOGS (shared lookup tables backing every <select> in the UI)
-- =========================================================================

CREATE TABLE cat_priorities (
    id TINYINT UNSIGNED PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,        -- critical | high | medium | low
    sort_order TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cat_project_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    sort_order TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cat_backlog_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    sort_order TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cat_activity_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    sort_order TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cat_backlog_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cat_activity_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cat_application_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cat_modalities (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cat_criticality_levels (
    id TINYINT UNSIGNED PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,        -- critical | high | medium | low
    sort_order TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cat_support_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =========================================================================
-- MODULE 1: PROJECTS / BACKLOG / ACTIVITIES
-- =========================================================================

CREATE TABLE developers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    position VARCHAR(120) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    start_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    developer_id INT UNSIGNED NOT NULL COMMENT 'Primary responsible developer',
    description TEXT NULL,
    start_date DATE NULL,
    estimated_end_date DATE NULL,
    actual_end_date DATE NULL,
    priority_id TINYINT UNSIGNED NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_projects_priority FOREIGN KEY (priority_id) REFERENCES cat_priorities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_projects_status FOREIGN KEY (status_id) REFERENCES cat_project_statuses(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Bridge table: allows a project to have more than one developer in the
-- future without changing the schema. Today the UI only manages the single
-- primary developer via projects.developer_id.
CREATE TABLE project_developer (
    project_id INT UNSIGNED NOT NULL,
    developer_id INT UNSIGNED NOT NULL,
    role VARCHAR(60) NULL,
    PRIMARY KEY (project_id, developer_id),
    CONSTRAINT fk_pd_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE backlog_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    developer_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    type_id TINYINT UNSIGNED NULL,
    priority_id TINYINT UNSIGNED NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL,
    created_date DATE NOT NULL,
    target_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_backlog_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_backlog_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_backlog_type FOREIGN KEY (type_id) REFERENCES cat_backlog_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_backlog_priority FOREIGN KEY (priority_id) REFERENCES cat_priorities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_backlog_status FOREIGN KEY (status_id) REFERENCES cat_backlog_statuses(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backlog_item_id INT UNSIGNED NOT NULL,
    developer_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    type_id TINYINT UNSIGNED NULL,
    priority_id TINYINT UNSIGNED NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL COMMENT 'Manually editable status; system-suggested status is computed in PHP from progress/due date',
    start_date DATE NULL,
    due_date DATE NULL,
    end_date DATE NULL,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100, the only manually entered progress value in the whole module',
    depends_on_activity_id INT UNSIGNED NULL COMMENT 'Self-reference; a non-completed dependency blocks this activity',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_backlog FOREIGN KEY (backlog_item_id) REFERENCES backlog_items(id) ON DELETE RESTRICT,
    CONSTRAINT fk_activities_developer FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_activities_type FOREIGN KEY (type_id) REFERENCES cat_activity_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_activities_priority FOREIGN KEY (priority_id) REFERENCES cat_priorities(id) ON DELETE RESTRICT,
    CONSTRAINT fk_activities_status FOREIGN KEY (status_id) REFERENCES cat_activity_statuses(id) ON DELETE RESTRICT,
    CONSTRAINT fk_activities_dependency FOREIGN KEY (depends_on_activity_id) REFERENCES activities(id) ON DELETE SET NULL,
    CONSTRAINT chk_progress_range CHECK (progress_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- Computed progress views: the single source of truth for backlog/project
-- progress. Application code (Services layer) reads these instead of
-- re-implementing the average in PHP, so dashboard and listings never diverge.
CREATE VIEW vw_backlog_progress AS
SELECT
    b.id AS backlog_item_id,
    b.project_id,
    COUNT(a.id) AS activity_count,
    COALESCE(ROUND(AVG(a.progress_percent)), 0) AS progress_percent
FROM backlog_items b
LEFT JOIN activities a ON a.backlog_item_id = b.id
GROUP BY b.id, b.project_id;

-- Project progress is the weighted average of its backlog items' progress,
-- weighted by how many activities each backlog item has (a backlog with more
-- activities counts more, so one lonely 100% task can't fake full progress).
CREATE VIEW vw_project_progress AS
SELECT
    p.id AS project_id,
    COALESCE(SUM(bp.progress_percent * bp.activity_count) / NULLIF(SUM(bp.activity_count), 0), 0) AS progress_percent,
    SUM(bp.activity_count) AS activity_count
FROM projects p
LEFT JOIN vw_backlog_progress bp ON bp.project_id = p.id
GROUP BY p.id;

-- =========================================================================
-- MODULE 2: APPLICATION SLA MANAGEMENT
-- =========================================================================

CREATE TABLE providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    tax_id VARCHAR(40) NULL,
    commercial_contact VARCHAR(120) NULL,
    technical_contact VARCHAR(120) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(60) NULL,
    support_portal VARCHAR(255) NULL,
    support_channel VARCHAR(150) NULL,
    support_hours VARCHAR(150) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    commercial_name VARCHAR(150) NULL,
    description TEXT NULL,
    business_process VARCHAR(200) NULL,
    requesting_area VARCHAR(120) NULL,
    functional_owner VARCHAR(120) NULL COMMENT 'Person/area who knows and administers the application functionally',
    technical_owner VARCHAR(120) NULL COMMENT 'Person/area in charge of technical administration',
    application_type_id TINYINT UNSIGNED NOT NULL,
    category VARCHAR(100) NULL,
    criticality_id TINYINT UNSIGNED NOT NULL,
    status ENUM('active','in_implementation','retired') NOT NULL DEFAULT 'active',
    approx_users INT UNSIGNED NULL,
    url VARCHAR(255) NULL,
    environment VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_apps_type FOREIGN KEY (application_type_id) REFERENCES cat_application_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_apps_criticality FOREIGN KEY (criticality_id) REFERENCES cat_criticality_levels(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE application_ownership (
    application_id INT UNSIGNED PRIMARY KEY,
    ownership VARCHAR(40) NOT NULL COMMENT 'in_house | outsourced',
    modality_id TINYINT UNSIGNED NULL,
    license_type VARCHAR(100) NULL,
    acquisition_model VARCHAR(100) NULL,
    acquisition_date DATE NULL,
    contract_start_date DATE NULL,
    expiration_date DATE NULL,
    auto_renewal TINYINT(1) NOT NULL DEFAULT 0,
    license_count INT UNSIGNED NULL,
    approx_cost DECIMAL(14,2) NULL,
    payment_frequency VARCHAR(60) NULL,
    CONSTRAINT fk_ownership_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_ownership_modality FOREIGN KEY (modality_id) REFERENCES cat_modalities(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- NULL provider_id means "In-house development / IT department", per spec.
CREATE TABLE application_provider (
    application_id INT UNSIGNED PRIMARY KEY,
    provider_id INT UNSIGNED NULL,
    contract_number VARCHAR(80) NULL,
    contract_start_date DATE NULL,
    contract_expiration_date DATE NULL,
    CONSTRAINT fk_appprov_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_appprov_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE application_support_type (
    application_id INT UNSIGNED NOT NULL,
    support_type_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (application_id, support_type_id),
    CONSTRAINT fk_appsupp_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_appsupp_type FOREIGN KEY (support_type_id) REFERENCES cat_support_types(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE support_matrix (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    level TINYINT UNSIGNED NOT NULL COMMENT '1=first line, 2=internal specialist, 3=provider/vendor',
    responsible VARCHAR(150) NULL,
    channel VARCHAR(120) NULL,
    hours VARCHAR(120) NULL,
    max_escalation_time VARCHAR(60) NULL,
    contact VARCHAR(120) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(60) NULL,
    procedure_notes TEXT NULL,
    CONSTRAINT fk_supportmatrix_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT chk_support_level CHECK (level BETWEEN 1 AND 4),
    UNIQUE KEY uq_app_level (application_id, level)
) ENGINE=InnoDB;

CREATE TABLE application_schedule (
    application_id INT UNSIGNED PRIMARY KEY,
    operating_hours VARCHAR(150) NULL COMMENT 'When the application itself is available to users',
    support_hours VARCHAR(150) NULL COMMENT 'When support is actually staffed, distinct from operating_hours',
    service_days VARCHAR(120) NULL,
    after_hours_support TINYINT(1) NOT NULL DEFAULT 0,
    weekend_support TINYINT(1) NOT NULL DEFAULT 0,
    holiday_support TINYINT(1) NOT NULL DEFAULT 0,
    support_24x7 TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_schedule_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Global default incident SLA matrix (application_id NULL) plus optional
-- per-application overrides, per spec section 9.
CREATE TABLE sla_incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NULL,
    priority VARCHAR(4) NOT NULL COMMENT 'P1 | P2 | P3 | P4',
    description VARCHAR(200) NULL,
    response_time_minutes INT UNSIGNED NOT NULL,
    resolution_time_minutes INT UNSIGNED NOT NULL,
    CONSTRAINT fk_slainc_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sla_request_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NULL,
    type VARCHAR(20) NOT NULL COMMENT 'incident | request | requirement | problem | change',
    response_time VARCHAR(60) NULL,
    resolution_time VARCHAR(60) NULL,
    CONSTRAINT fk_slareq_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_availability (
    application_id INT UNSIGNED PRIMARY KEY,
    target_availability DECIMAL(5,2) NULL COMMENT 'e.g. 99.90',
    actual_availability DECIMAL(5,2) NULL,
    max_downtime VARCHAR(60) NULL,
    rto VARCHAR(60) NULL,
    rpo VARCHAR(60) NULL,
    backup_frequency VARCHAR(100) NULL,
    estimated_recovery_time VARCHAR(60) NULL,
    CONSTRAINT fk_availability_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_integrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    related_system VARCHAR(150) NOT NULL,
    integration_type VARCHAR(60) NULL COMMENT 'API | web_service | database | file | manual',
    frequency VARCHAR(60) NULL,
    source_system VARCHAR(150) NULL,
    target_system VARCHAR(150) NULL,
    CONSTRAINT fk_integrations_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_dependencies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    dependency VARCHAR(150) NOT NULL,
    CONSTRAINT fk_dependencies_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE raci_matrix (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    activity VARCHAR(100) NOT NULL COMMENT 'Support | Changes | Incidents | Backups | Recovery | Updates | Security | User admin | Provider escalation',
    responsible VARCHAR(150) NULL,
    accountable VARCHAR(150) NULL,
    consulted VARCHAR(150) NULL,
    informed VARCHAR(150) NULL,
    CONSTRAINT fk_raci_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE contract_expirations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL COMMENT 'contract | license | certificate | domain | cloud | provider_support',
    label VARCHAR(150) NULL,
    expiration_date DATE NOT NULL,
    notes TEXT NULL,
    CONSTRAINT fk_contractexp_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sla_monthly_indicators (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    month DATE NOT NULL COMMENT 'First day of the reported month, e.g. 2026-07-01',
    response_compliance_pct DECIMAL(5,2) NULL,
    resolution_compliance_pct DECIMAL(5,2) NULL,
    availability_pct DECIMAL(5,2) NULL,
    incident_count INT UNSIGNED NOT NULL DEFAULT 0,
    critical_incident_count INT UNSIGNED NOT NULL DEFAULT 0,
    recurring_incident_count INT UNSIGNED NOT NULL DEFAULT 0,
    avg_response_time VARCHAR(60) NULL,
    avg_resolution_time VARCHAR(60) NULL,
    breach_count INT UNSIGNED NOT NULL DEFAULT 0,
    escalation_count INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_indicators_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    UNIQUE KEY uq_app_month (application_id, month)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
