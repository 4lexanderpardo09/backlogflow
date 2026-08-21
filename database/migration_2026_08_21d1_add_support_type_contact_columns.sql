-- Migration (paso 1 de 2, seguro / no destructivo): agrega a
-- application_support_type las columnas de contacto/escalamiento que antes
-- vivían en support_matrix, sin relación a ningún tipo de soporte. No borra
-- ni modifica support_matrix — tus datos actuales de esa tabla siguen ahí
-- intactos hasta que los recapturés por tipo de soporte en la pantalla
-- Sla > Aplicaciones > Editar y corras el paso 2
-- (migration_2026_08_21d2_drop_support_matrix.sql).
-- Requiere haber aplicado antes migration_2026_08_21_feedback.sql,
-- migration_2026_08_21b_support_type_levels_per_app.sql y
-- migration_2026_08_21c_support_types_crud.sql.
--
--   mysql -u usuario -p nombre_bd < database/migration_2026_08_21d1_add_support_type_contact_columns.sql

ALTER TABLE application_support_type
  ADD COLUMN responsible VARCHAR(150) NULL AFTER level,
  ADD COLUMN channel VARCHAR(120) NULL AFTER responsible,
  ADD COLUMN hours VARCHAR(120) NULL AFTER channel,
  ADD COLUMN max_escalation_time VARCHAR(60) NULL AFTER hours,
  ADD COLUMN contact VARCHAR(120) NULL AFTER max_escalation_time,
  ADD COLUMN email VARCHAR(150) NULL AFTER contact,
  ADD COLUMN phone VARCHAR(60) NULL AFTER email,
  ADD COLUMN procedure_notes TEXT NULL AFTER phone;
