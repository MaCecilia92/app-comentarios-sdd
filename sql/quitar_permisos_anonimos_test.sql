-- Módulo: Esquema y migraciones
-- Responsabilidad: quita los permisos que XAMPP le da de fábrica a un usuario anónimo sobre las
--   bases test y test_*. MariaDB los aplica a cualquier usuario, así que
--   app_comentarios_lectura podía escribir en esas bases aunque solo tiene SELECT sobre
--   app_comentarios (Semana 7, sección de seguridad).
-- Decisiones: D-08, D-10 (el agente no escribe)
-- Requisitos: RN-009
-- Fronteras: se ejecuta a mano, como root. Cambia la configuración global de XAMPP: cualquier
--   otro proyecto que use la base test sin usuario propio deja de poder hacerlo.
--
--   mysql -u root < sql/quitar_permisos_anonimos_test.sql

DELETE FROM mysql.db WHERE User = '' AND Db IN ('test', 'test\\_%');
FLUSH PRIVILEGES;
