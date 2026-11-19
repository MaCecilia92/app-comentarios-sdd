-- Módulo: Esquema y migraciones
-- Responsabilidad: crea el usuario de solo lectura con el que el agente consulta la base por
--   MCP (Semana 7). Solo puede leer app_comentarios: no escribe, no borra, no cambia la
--   estructura y no ve otras bases.
-- Decisiones: D-08, D-10 (nadie borra en la v1) · design.md §3, "Configuración" y "Esquema y
--   migraciones"
-- Requisitos: RN-009, RN-011
-- Fronteras: se ejecuta a mano, como root. La app no lo usa: sigue con las credenciales de
--   config.php.
--
-- La contraseña NO está en este archivo. Se pasa al ejecutarlo, en la variable @clave, y se
-- guarda en la variable de entorno APP_COMENTARIOS_MYSQL_CLAVE del Host:
--
--   mysql -u root -e "SET @clave = '<contraseña>'; SOURCE sql/usuario_mcp_lectura.sql;"
--
-- Se puede volver a ejecutar: borra el usuario si existe y lo crea de nuevo.
-- Los permisos sobre app_comentarios.* sobreviven a sql/schema.sql (D-11), que borra y crea la
-- base pero no toca los usuarios.

DROP USER IF EXISTS 'app_comentarios_lectura'@'localhost';

SET @crear = CONCAT(
    'CREATE USER ''app_comentarios_lectura''@''localhost'' IDENTIFIED BY ',
    QUOTE(@clave)
);
PREPARE crear_usuario FROM @crear;
EXECUTE crear_usuario;
DEALLOCATE PREPARE crear_usuario;
SET @crear = NULL;

GRANT SELECT ON app_comentarios.* TO 'app_comentarios_lectura'@'localhost';
