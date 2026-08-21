-- Migration: fusiona la "matriz de soporte" (support_matrix, niveles 1-4
-- de escalamiento sin relación con los tipos de soporte) dentro de
-- application_support_type, para que cada tipo de soporte de una aplicación
-- lleve directamente su información de a quién contactar/escalar.
-- Requiere haber aplicado antes migration_2026_08_21_feedback.sql,
-- migration_2026_08_21b_support_type_levels_per_app.sql y
-- migration_2026_08_21c_support_types_crud.sql.
--
--   mysql -u root -p backlogflow < database/migration_2026_08_21d_merge_support_matrix.sql
--
-- Nota: support_matrix no tenía ninguna relación con los tipos de soporte
-- (era solo application_id + nivel de escalamiento 1-4), así que sus datos
-- de contacto NO se migran automáticamente fila por fila — revísalos y
-- vuelve a capturarlos en Sla > Aplicaciones > Editar, en el tipo de
-- soporte correspondiente, antes de borrar la tabla si quieres conservarlos.

ALTER TABLE application_support_type
  ADD COLUMN responsible VARCHAR(150) NULL AFTER level,
  ADD COLUMN channel VARCHAR(120) NULL AFTER responsible,
  ADD COLUMN hours VARCHAR(120) NULL AFTER channel,
  ADD COLUMN max_escalation_time VARCHAR(60) NULL AFTER hours,
  ADD COLUMN contact VARCHAR(120) NULL AFTER max_escalation_time,
  ADD COLUMN email VARCHAR(150) NULL AFTER contact,
  ADD COLUMN phone VARCHAR(60) NULL AFTER email,
  ADD COLUMN procedure_notes TEXT NULL AFTER phone;

DROP TABLE IF EXISTS support_matrix;
