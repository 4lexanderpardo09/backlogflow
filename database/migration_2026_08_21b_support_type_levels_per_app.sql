-- Migration: nivel de soporte por aplicación (además del nivel por defecto
-- del catálogo cat_support_types). Permite, para cada aplicación, marcar
-- qué tipos de soporte presta y a qué nivel corresponde cada uno.
-- Requiere haber aplicado antes database/migration_2026_08_21_feedback.sql.
--
--   mysql -u root -p backlogflow < database/migration_2026_08_21b_support_type_levels_per_app.sql

ALTER TABLE application_support_type
  ADD COLUMN level TINYINT UNSIGNED NOT NULL DEFAULT 2
    COMMENT '1 = creación de usuarios, permisos, etc.; 2 = otros soportes propios de la aplicación (por aplicación, puede diferir del nivel por defecto del catálogo)',
  ADD CONSTRAINT chk_appsupport_level CHECK (level IN (1, 2));
