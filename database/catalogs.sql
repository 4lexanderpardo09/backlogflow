-- Reference/lookup data. Required for the application to function (dropdowns,
-- status labels) — distinct from database/seed.sql, which holds example
-- business records (developers, projects, applications...).

INSERT INTO cat_priorities (id, code, sort_order) VALUES
    (1, 'critical', 1),
    (2, 'high', 2),
    (3, 'medium', 3),
    (4, 'low', 4);

INSERT INTO cat_project_statuses (code, sort_order) VALUES
    ('not_started', 1),
    ('in_progress', 2),
    ('on_hold', 3),
    ('completed', 4),
    ('delayed', 5),
    ('cancelled', 6);

INSERT INTO cat_backlog_statuses (code, sort_order) VALUES
    ('pending', 1),
    ('in_analysis', 2),
    ('in_development', 3),
    ('in_testing', 4),
    ('blocked', 5),
    ('completed', 6),
    ('cancelled', 7);

INSERT INTO cat_activity_statuses (code, sort_order) VALUES
    ('pending', 1),
    ('in_progress', 2),
    ('blocked', 3),
    ('overdue', 4),
    ('completed', 5),
    ('cancelled', 6);

INSERT INTO cat_backlog_types (code) VALUES
    ('new_development'), ('migration'), ('maintenance'), ('integration'), ('support'), ('other');

INSERT INTO cat_activity_types (code) VALUES
    ('analysis'), ('development'), ('testing'), ('deployment'), ('documentation'), ('data_cleanup'), ('other');

INSERT INTO cat_application_types (code) VALUES
    ('erp'), ('crm'), ('accounting'), ('payroll'), ('collections'), ('inventory'), ('fixed_assets'),
    ('help_desk'), ('document_management'), ('bi_analytics'), ('email'), ('security'),
    ('infrastructure'), ('web_app'), ('mobile_app'), ('other');

INSERT INTO cat_modalities (code) VALUES
    ('in_house_development'), ('outsourced_development'), ('perpetual_license'),
    ('subscription_license'), ('saas'), ('cloud'), ('lease'), ('managed_service'),
    ('open_source'), ('other');

INSERT INTO cat_criticality_levels (id, code, sort_order) VALUES
    (1, 'critical', 1),
    (2, 'high', 2),
    (3, 'medium', 3),
    (4, 'low', 4);

INSERT INTO cat_support_types (code) VALUES
    ('functional_support'), ('technical_support'), ('infrastructure_support'), ('database_support'),
    ('integration_support'), ('security_support'), ('user_support'), ('development'),
    ('bug_fixing'), ('preventive_maintenance'), ('evolutionary_maintenance'), ('updates'),
    ('user_administration'), ('permission_administration'), ('backup_and_recovery');

-- Default global incident SLA matrix (application_id = NULL), per spec
-- section 9. These are starting values, editable per application.
INSERT INTO sla_incidents (application_id, priority, description, response_time_minutes, resolution_time_minutes) VALUES
    (NULL, 'P1', 'Aplicación completamente indisponible', 15, 240),
    (NULL, 'P2', 'Funcionalidad crítica afectada', 30, 480),
    (NULL, 'P3', 'Afectación parcial', 240, 2880),
    (NULL, 'P4', 'Solicitud o inconveniente menor', 1440, 7200);
