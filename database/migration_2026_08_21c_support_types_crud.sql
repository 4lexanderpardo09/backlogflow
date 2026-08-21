-- Migration: catálogo de tipos de soporte (cat_support_types) totalmente
-- administrable: agregar, renombrar y eliminar tipos desde la pantalla
-- Sla > Tipos de soporte, en vez de venir fijos en el código.
-- Requiere haber aplicado antes migration_2026_08_21_feedback.sql y
-- migration_2026_08_21b_support_type_levels_per_app.sql.
--
--   mysql -u root -p backlogflow < database/migration_2026_08_21c_support_types_crud.sql

ALTER TABLE cat_support_types
  ADD COLUMN name VARCHAR(120) NOT NULL DEFAULT '' AFTER code;

UPDATE cat_support_types SET name = CASE code
    WHEN 'functional_support' THEN 'Soporte funcional'
    WHEN 'technical_support' THEN 'Soporte técnico'
    WHEN 'infrastructure_support' THEN 'Soporte de infraestructura'
    WHEN 'database_support' THEN 'Soporte de base de datos'
    WHEN 'integration_support' THEN 'Soporte de integración'
    WHEN 'security_support' THEN 'Soporte de seguridad'
    WHEN 'user_support' THEN 'Soporte de usuario'
    WHEN 'development' THEN 'Desarrollo'
    WHEN 'bug_fixing' THEN 'Corrección de errores'
    WHEN 'preventive_maintenance' THEN 'Mantenimiento preventivo'
    WHEN 'evolutionary_maintenance' THEN 'Mantenimiento evolutivo'
    WHEN 'updates' THEN 'Actualizaciones'
    WHEN 'user_administration' THEN 'Administración de usuarios'
    WHEN 'permission_administration' THEN 'Administración de permisos'
    WHEN 'backup_and_recovery' THEN 'Backup y recuperación'
    ELSE code
END
WHERE name = '';

ALTER TABLE cat_support_types
  MODIFY COLUMN name VARCHAR(120) NOT NULL;
