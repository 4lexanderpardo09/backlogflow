-- Example business data for both modules. Dates are anchored around
-- 2026-08-14 so the seed always contains overdue activities, activities
-- due soon, and contracts in every expiration alert bucket (90/60/30/expired).

-- =========================================================================
-- MODULE 1: PROJECTS / BACKLOG / ACTIVITIES
-- =========================================================================

INSERT INTO developers (id, name, position, status, start_date, notes) VALUES
    (1, 'Juan Pérez', 'Desarrollador Senior', 'active', '2023-01-15', NULL),
    (2, 'María Gómez', 'Desarrolladora', 'active', '2023-06-01', NULL),
    (3, 'Carlos Ruiz', 'Desarrollador Junior', 'active', '2024-02-10', NULL);

-- priorities: 1 critical, 2 high, 3 medium, 4 low
-- project statuses: 1 not_started, 2 in_progress, 3 on_hold, 4 completed, 5 delayed, 6 cancelled
INSERT INTO projects (id, name, developer_id, description, start_date, estimated_end_date, actual_end_date, priority_id, status_id, notes) VALUES
    (1, 'Sistema de Inventario', 1, 'Modernización del sistema de control de activos e inventario.', '2026-05-01', '2026-09-30', NULL, 2, 2, NULL),
    (2, 'Portal de Cartera', 2, 'Portal de gestión de cartera con integración bancaria.', '2026-03-01', '2026-07-15', NULL, 1, 5, 'Retrasado por certificación de seguridad pendiente con el banco.'),
    (3, 'App Móvil de Ventas', 3, 'Aplicación móvil para la fuerza de ventas.', '2026-06-01', '2026-10-01', NULL, 3, 2, NULL);

-- backlog statuses: 1 pending, 2 in_analysis, 3 in_development, 4 in_testing, 5 blocked, 6 completed, 7 cancelled
-- backlog types: 1 new_development, 2 migration, 3 maintenance, 4 integration, 5 support, 6 other
INSERT INTO backlog_items (id, project_id, developer_id, description, type_id, priority_id, status_id, created_date, target_date, notes) VALUES
    (1, 1, 1, 'Migración de activos', 2, 2, 3, '2026-05-05', '2026-07-30', NULL),
    (2, 1, 1, 'Módulo de reportes', 1, 3, 1, '2026-06-01', '2026-09-15', NULL),
    (3, 2, 2, 'Integración con banco', 4, 1, 5, '2026-03-05', '2026-06-30', 'Bloqueado a la espera de certificación de seguridad.'),
    (4, 2, 2, 'Depuración de cartera vencida', 3, 2, 4, '2026-04-01', '2026-08-01', NULL),
    (5, 3, 3, 'Diseño UX/UI', 1, 3, 6, '2026-06-05', '2026-07-01', NULL),
    (6, 3, 3, 'Desarrollo de catálogo de productos', 1, 2, 3, '2026-06-20', '2026-10-15', NULL);

-- activity statuses: 1 pending, 2 in_progress, 3 blocked, 4 overdue, 5 completed, 6 cancelled
-- activity types: 1 analysis, 2 development, 3 testing, 4 deployment, 5 documentation, 6 data_cleanup, 7 other
INSERT INTO activities (id, backlog_item_id, developer_id, name, description, type_id, priority_id, status_id, start_date, due_date, end_date, progress_percent, depends_on_activity_id, notes) VALUES
    -- Backlog 1: Migración de activos (mirrors the example from the spec)
    (1, 1, 1, 'Exportar activos', NULL, 6, 3, 5, '2026-05-10', '2026-05-20', '2026-05-19', 100, NULL, NULL),
    (2, 1, 1, 'Homologar empleados', NULL, 6, 3, 5, '2026-05-15', '2026-05-25', '2026-05-24', 100, NULL, NULL),
    (3, 1, 1, 'Limpiar información', NULL, 6, 2, 2, '2026-05-26', '2026-08-10', NULL, 50, NULL, 'Vencida: fecha límite ya pasó con avance parcial.'),
    (4, 1, 1, 'Preparar archivo de importación', NULL, 2, 2, 1, NULL, '2026-08-25', NULL, 0, NULL, NULL),
    (5, 1, 1, 'Importar activos', NULL, 2, 2, 1, NULL, '2026-09-01', NULL, 0, 4, NULL),
    (6, 1, 1, 'Validar información', NULL, 3, 3, 1, NULL, '2026-09-05', NULL, 0, 5, NULL),
    -- Backlog 2: Módulo de reportes
    (7, 2, 1, 'Analizar requerimientos', NULL, 1, 3, 2, '2026-08-01', '2026-08-20', NULL, 30, NULL, NULL),
    (8, 2, 1, 'Diseñar reportes', NULL, 1, 3, 1, NULL, '2026-09-01', NULL, 0, 7, NULL),
    (9, 2, 1, 'Desarrollar reportes', NULL, 2, 3, 1, NULL, '2026-09-10', NULL, 0, 8, NULL),
    (10, 2, 1, 'Pruebas de reportes', NULL, 3, 3, 1, NULL, '2026-09-15', NULL, 0, 9, NULL),
    -- Backlog 3: Integración con banco
    (11, 3, 2, 'Conectar API bancaria', NULL, 2, 1, 2, '2026-05-01', '2026-06-15', NULL, 70, NULL, 'Vencida.'),
    (12, 3, 2, 'Validar transacciones', NULL, 3, 1, 1, NULL, '2026-07-01', NULL, 0, 11, 'Vencida.'),
    (13, 3, 2, 'Certificación de seguridad', NULL, 1, 1, 3, NULL, '2026-07-10', NULL, 0, 12, 'Bloqueada: pendiente del banco.'),
    -- Backlog 4: Depuración de cartera vencida
    (14, 4, 2, 'Identificar cuentas vencidas', NULL, 1, 2, 5, '2026-04-05', '2026-05-01', '2026-04-28', 100, NULL, NULL),
    (15, 4, 2, 'Generar reporte de castigo', NULL, 2, 2, 5, '2026-04-29', '2026-05-15', '2026-05-14', 100, NULL, NULL),
    (16, 4, 2, 'Validar con contabilidad', NULL, 3, 2, 2, '2026-05-16', '2026-08-05', NULL, 80, NULL, 'Vencida.'),
    -- Backlog 5: Diseño UX/UI
    (17, 5, 3, 'Wireframes', NULL, 2, 3, 5, '2026-06-05', '2026-06-15', '2026-06-14', 100, NULL, NULL),
    (18, 5, 3, 'Prototipo interactivo', NULL, 2, 3, 5, '2026-06-16', '2026-06-25', '2026-06-24', 100, NULL, NULL),
    -- Backlog 6: Desarrollo de catálogo de productos
    (19, 6, 3, 'Modelado de datos', NULL, 1, 2, 5, '2026-06-20', '2026-07-10', '2026-07-09', 100, NULL, NULL),
    (20, 6, 3, 'Pantalla de listado', NULL, 2, 2, 2, '2026-07-11', '2026-08-30', NULL, 60, NULL, 'Próxima a vencer.'),
    (21, 6, 3, 'Carrito de compras', NULL, 2, 2, 1, NULL, '2026-09-20', NULL, 0, NULL, NULL),
    (22, 6, 3, 'Integración de pagos', NULL, 2, 1, 1, NULL, '2026-10-05', NULL, 0, 21, NULL);

-- =========================================================================
-- MODULE 2: APPLICATION SLA MANAGEMENT
-- =========================================================================

INSERT INTO providers (id, name, tax_id, commercial_contact, technical_contact, email, phone, support_portal, support_channel, support_hours, notes) VALUES
    (1, 'SoftCol S.A.S.', '900123456-1', 'Laura Méndez', 'Pedro Sánchez', 'soporte@softcol.com', '+57 601 789 4561', 'https://soporte.softcol.com', 'Portal web y teléfono', 'L-V 8:00-18:00', NULL),
    (2, 'Microsoft', 'Por definir', 'Por definir', 'Por definir', 'Por definir', 'Por definir', 'https://admin.microsoft.com', 'Portal Microsoft 365 Admin', '24x7', NULL),
    (3, 'Novasoft', '830045678-2', 'Diana Rojas', 'Soporte Novasoft', 'soporte@novasoft.com.co', '+57 601 555 2233', 'https://soporte.novasoft.com.co', 'Mesa de ayuda telefónica', 'L-V 7:00-17:00', NULL);

-- application types: 1 erp,2 crm,3 accounting,4 payroll,5 collections,6 inventory,7 fixed_assets,8 help_desk,
--   9 document_management,10 bi_analytics,11 email,12 security,13 infrastructure,14 web_app,15 mobile_app,16 other
-- criticality: 1 critical, 2 high, 3 medium, 4 low
INSERT INTO applications (id, name, commercial_name, description, business_process, requesting_area, functional_owner, technical_owner, application_type_id, category, criticality_id, status, approx_users, url, environment, notes) VALUES
    (1, 'SAP Business One', 'SAP B1', 'ERP financiero y contable de la compañía.', 'Contabilidad y Finanzas', 'Finanzas', 'Ana Torres', 'Carlos Ruiz (TI)', 1, 'ERP', 1, 'active', 45, 'https://erp.interno.local', 'Producción', NULL),
    (2, 'Contífico', 'Contífico Cloud', 'Plataforma contable en la nube.', 'Contabilidad', 'Contabilidad', 'Ana Torres', 'María Gómez (TI)', 3, 'Contabilidad', 2, 'active', 12, 'https://app.contifico.com', 'Producción', NULL),
    (3, 'GLPI', 'GLPI', 'Mesa de ayuda y gestión de incidentes de TI.', 'Soporte de TI', 'Sistemas', 'Carlos Ruiz (TI)', 'Carlos Ruiz (TI)', 8, 'Mesa de ayuda', 3, 'active', 60, 'https://helpdesk.interno.local', 'Producción', NULL),
    (4, 'Microsoft 365', 'Office 365', 'Correo electrónico y suite ofimática colaborativa.', 'Comunicaciones corporativas', 'Toda la empresa', 'Sistemas', 'Sistemas', 11, 'Correo electrónico', 1, 'active', 130, 'https://portal.office.com', 'Producción', NULL),
    (5, 'Novasoft Nómina', 'Novasoft Nómina', 'Liquidación y gestión de nómina.', 'Nómina', 'Talento Humano', 'Diana Ríos (RRHH)', 'María Gómez (TI)', 4, 'Nómina', 1, 'active', 8, 'https://nomina.novasoft.com.co', 'Producción', NULL),
    (6, 'Sistema de Inventario', 'Sistema de Inventario', 'Control de activos e inventario, desarrollo interno.', 'Inventario y Activos Fijos', 'Almacén', 'Juan Pérez (TI)', 'Juan Pérez (TI)', 6, 'Inventario', 2, 'in_implementation', 15, 'https://inventario.interno.local', 'Desarrollo', 'En construcción, ver proyecto "Sistema de Inventario".');

-- modalities: 1 in_house_development,2 outsourced_development,3 perpetual_license,4 subscription_license,
--   5 saas,6 cloud,7 lease,8 managed_service,9 open_source,10 other
INSERT INTO application_ownership (application_id, ownership, modality_id, license_type, acquisition_model, acquisition_date, contract_start_date, expiration_date, auto_renewal, license_count, approx_cost, payment_frequency) VALUES
    (1, 'outsourced', 3, 'Licencia perpetua + soporte anual', 'Compra directa', '2022-01-10', '2026-09-11', '2026-09-10', 0, 45, 85000000.00, 'Anual'),
    (2, 'outsourced', 5, 'Suscripción SaaS', 'Suscripción', '2024-02-01', '2025-10-01', '2026-10-01', 1, 12, 9600000.00, 'Anual'),
    (3, 'in_house', 9, 'Open Source', 'Instalación interna', '2023-05-01', NULL, NULL, 0, NULL, 0.00, 'No aplica'),
    (4, 'outsourced', 5, 'Suscripción SaaS', 'Suscripción', '2021-01-01', '2026-01-01', '2027-01-01', 1, 130, 45500000.00, 'Anual'),
    (5, 'outsourced', 4, 'Licencia por suscripción', 'Suscripción', '2023-09-01', '2025-09-01', '2026-09-01', 0, 8, 6200000.00, 'Anual'),
    (6, 'in_house', 1, 'No aplica', 'Desarrollo interno', NULL, NULL, NULL, 0, NULL, 0.00, 'No aplica');

INSERT INTO application_provider (application_id, provider_id, contract_number, contract_start_date, contract_expiration_date) VALUES
    (1, 1, 'CT-2022-045', '2026-09-11', '2026-09-10'),
    (2, NULL, 'Por definir', NULL, '2026-10-01'),
    (3, NULL, NULL, NULL, NULL),
    (4, 2, 'MS-EA-2021', '2026-01-01', '2027-01-01'),
    (5, 3, 'CT-2023-102', '2025-09-01', '2026-09-01'),
    (6, NULL, NULL, NULL, NULL);

INSERT INTO application_support_type (application_id, support_type_id) VALUES
    (1, 2), (1, 4), (1, 9),
    (2, 1), (2, 9),
    (3, 7), (3, 13),
    (4, 2), (4, 12),
    (5, 1), (5, 9),
    (6, 2), (6, 8);

INSERT INTO support_matrix (application_id, level, responsible, channel, hours, max_escalation_time, contact, email, phone, procedure_notes) VALUES
    (1, 1, 'Mesa de ayuda / Sistemas', 'GLPI / Teléfono', 'L-V 7:00-19:00', '30 min', 'Carlos Ruiz', 'sistemas@empresa.com', '+57 601 000 0000', 'Registrar ticket en GLPI antes de escalar.'),
    (1, 2, 'Equipo interno TI - Financiero', 'Correo / GLPI', 'L-V 7:00-19:00', '2 horas', 'Carlos Ruiz', 'carlos.ruiz@empresa.com', '+57 300 000 0001', NULL),
    (1, 3, 'SoftCol S.A.S.', 'Portal de soporte', 'L-V 8:00-18:00', '4 horas', 'Pedro Sánchez', 'soporte@softcol.com', '+57 601 789 4561', 'Escalar solo si nivel 2 no resuelve en el tiempo máximo.'),
    (4, 1, 'Mesa de ayuda / Sistemas', 'GLPI / Teléfono', '24x7', '15 min', 'Carlos Ruiz', 'sistemas@empresa.com', '+57 601 000 0000', NULL),
    (4, 3, 'Microsoft', 'Portal Microsoft 365 Admin', '24x7', '1 hora', 'Por definir', 'Por definir', 'Por definir', 'Casos P1 por el portal de administración.'),
    (5, 1, 'Mesa de ayuda / Sistemas', 'GLPI / Teléfono', 'L-V 7:00-17:00', '30 min', 'María Gómez', 'sistemas@empresa.com', '+57 601 000 0000', NULL),
    (5, 3, 'Novasoft', 'Mesa de ayuda telefónica', 'L-V 7:00-17:00', '4 horas', 'Soporte Novasoft', 'soporte@novasoft.com.co', '+57 601 555 2233', NULL);

INSERT INTO application_schedule (application_id, operating_hours, support_hours, service_days, after_hours_support, weekend_support, holiday_support, support_24x7) VALUES
    (1, 'L-V 6:00-22:00', 'L-V 7:00-19:00', 'Lunes a viernes', 0, 0, 0, 0),
    (2, 'L-V 6:00-22:00', 'L-V 8:00-18:00', 'Lunes a viernes', 0, 0, 0, 0),
    (3, '24x7', 'L-V 7:00-19:00', 'Lunes a viernes', 0, 0, 0, 0),
    (4, '24x7', '24x7', 'Todos los días', 1, 1, 1, 1),
    (5, 'L-V 6:00-20:00', 'L-V 7:00-17:00', 'Lunes a viernes', 0, 0, 0, 0),
    (6, 'L-V 7:00-18:00', 'L-V 7:00-18:00', 'Lunes a viernes', 0, 0, 0, 0);

INSERT INTO application_availability (application_id, target_availability, actual_availability, maintenance_window, max_downtime, rto, rpo, backup_frequency, estimated_recovery_time) VALUES
    (1, 99.50, 99.20, 'Domingos 22:00-02:00', '4 horas/mes', '4 horas', '1 hora', 'Diaria', '4 horas'),
    (4, 99.90, 99.95, 'Gestionada por Microsoft', 'Por definir', 'Por definir', 'Por definir', 'Gestionada por Microsoft', 'Por definir'),
    (5, 99.00, 98.50, 'Sábados 20:00-23:00', '6 horas/mes', '6 horas', '2 horas', 'Diaria', '6 horas');

INSERT INTO application_integrations (application_id, related_system, integration_type, frequency, source_system, target_system) VALUES
    (1, 'Sistema de Inventario', 'API', 'Diaria', 'SAP Business One', 'Sistema de Inventario'),
    (1, 'Novasoft Nómina', 'Archivo plano', 'Mensual', 'Novasoft Nómina', 'SAP Business One'),
    (6, 'SAP Business One', 'API', 'Diaria', 'Sistema de Inventario', 'SAP Business One'),
    (3, 'Microsoft 365', 'Web Service', 'Tiempo real', 'GLPI', 'Microsoft 365');

INSERT INTO application_dependencies (application_id, dependency) VALUES
    (1, 'Servidor de base de datos on-premise'),
    (1, 'Internet / VPN con proveedor'),
    (4, 'Servicios cloud de Microsoft'),
    (4, 'Active Directory'),
    (6, 'Servidor de aplicaciones interno');

INSERT INTO raci_matrix (application_id, activity, responsible, accountable, consulted, informed) VALUES
    (1, 'Soporte', 'Carlos Ruiz (TI)', 'Jefe de Sistemas', 'SoftCol S.A.S.', 'Finanzas'),
    (1, 'Cambios', 'Carlos Ruiz (TI)', 'Jefe de Sistemas', 'Ana Torres (Finanzas)', 'SoftCol S.A.S.'),
    (1, 'Backups', 'Carlos Ruiz (TI)', 'Jefe de Sistemas', 'SoftCol S.A.S.', 'Finanzas'),
    (4, 'Administración de usuarios', 'Sistemas', 'Jefe de Sistemas', NULL, 'Toda la empresa'),
    (5, 'Soporte', 'María Gómez (TI)', 'Jefe de Sistemas', 'Novasoft', 'Talento Humano');

-- Contract/license/certificate expirations covering every alert bucket
-- (>90, 60-90, 30-60, <30, expired) relative to 2026-08-14.
INSERT INTO contract_expirations (application_id, type, label, expiration_date, notes) VALUES
    (5, 'contract', 'Contrato de soporte Novasoft', '2026-09-01', 'Vence en menos de 30 días.'),
    (1, 'contract', 'Contrato de licenciamiento SAP B1', '2026-09-10', 'Vence en menos de 30 días.'),
    (2, 'contract', 'Suscripción Contífico', '2026-10-01', 'Vence entre 30 y 60 días.'),
    (4, 'contract', 'Enterprise Agreement Microsoft', '2027-01-01', 'Vence en más de 90 días, renovación automática.'),
    (3, 'certificate', 'Certificado SSL portal GLPI', '2026-07-20', 'Vencido, pendiente de renovar.');

-- Two months of compliance indicators for the critical applications, to
-- exercise the green/yellow/red compliance semaphore on the SLA dashboard.
INSERT INTO sla_monthly_indicators (application_id, month, response_compliance_pct, resolution_compliance_pct, availability_pct, incident_count, critical_incident_count, recurring_incident_count, avg_response_time, avg_resolution_time, breach_count, escalation_count) VALUES
    (1, '2026-06-01', 96.00, 90.00, 99.30, 18, 2, 1, '18 min', '3.5 horas', 1, 2),
    (1, '2026-07-01', 88.00, 82.00, 99.10, 22, 4, 2, '25 min', '5 horas', 4, 3),
    (4, '2026-06-01', 99.00, 97.00, 99.95, 6, 0, 0, '10 min', '40 min', 0, 0),
    (4, '2026-07-01', 100.00, 100.00, 99.98, 4, 0, 0, '8 min', '30 min', 0, 0),
    (5, '2026-06-01', 92.00, 85.00, 98.60, 9, 1, 1, '20 min', '3 horas', 1, 1),
    (5, '2026-07-01', 75.00, 70.00, 97.90, 14, 3, 3, '40 min', '6 horas', 5, 4);
