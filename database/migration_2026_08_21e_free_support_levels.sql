-- Migration: el nivel de tipo de soporte deja de estar limitado a 1 o 2 —
-- ahora es un número libre que el equipo define según su propio esquema de
-- escalamiento (1 = primera línea, 2 = especialista, 3 = proveedor, etc.).
-- Aplica a cat_support_types (nivel por defecto del catálogo) y a
-- application_support_type (nivel específico por aplicación).
--
--   mysql -u usuario -p nombre_bd < database/migration_2026_08_21e_free_support_levels.sql

ALTER TABLE cat_support_types
  DROP CONSTRAINT chk_support_type_level;

ALTER TABLE cat_support_types
  ADD CONSTRAINT chk_support_type_level CHECK (level >= 1);

ALTER TABLE application_support_type
  DROP CONSTRAINT chk_appsupport_level;

ALTER TABLE application_support_type
  ADD CONSTRAINT chk_appsupport_level CHECK (level >= 1);
