-- Migration (paso 2 de 2, DESTRUCTIVO): borra la tabla support_matrix.
-- Ejecuta esto SOLO después de:
--   1. Haber corrido migration_2026_08_21d1_add_support_type_contact_columns.sql.
--   2. Haber revisado cada fila de support_matrix (SELECT * FROM support_matrix)
--      y vuelto a capturar esa información en Sla > Aplicaciones > Editar,
--      ligada al tipo de soporte que corresponda para esa aplicación.
--   3. Haber confirmado en la pantalla de cada aplicación que los datos de
--      contacto ya aparecen correctamente en "Tipos de soporte".
--
--   mysql -u usuario -p nombre_bd < database/migration_2026_08_21d2_drop_support_matrix.sql

DROP TABLE IF EXISTS support_matrix;
