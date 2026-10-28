-- Módulo: Esquema y migraciones
-- Responsabilidad: define el almacén completo. Dos colecciones (comentario y respuesta) con
--   integridad referencial restrictiva, e índices para el orden de D-04.
-- Decisiones: D-03, D-04, D-09, D-11, D-12
-- Requisitos: RN-004, RN-005, RN-006, RN-009 · F-COM-002 CA-3
-- Fronteras: se ejecuta a mano (phpMyAdmin o cliente mysql). La aplicación nunca modifica la
--   estructura del almacén mientras atiende pedidos.
-- Estado: esquema completo de la v1.
--
-- ADVERTENCIA (D-11): este script BORRA la base app_comentarios si existe y la crea vacía.
-- Cualquier otra copia local de la app que use la misma base pierde sus datos.
--
-- Fechas (D-12): fecha_creacion se guarda en UTC; la conexión de la app fija la zona +00:00.

DROP DATABASE IF EXISTS app_comentarios;

CREATE DATABASE app_comentarios
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE app_comentarios;

-- Comentario (requirements.md §3). VARCHAR cuenta caracteres, no bytes (D-02).
CREATE TABLE comentario (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    autor VARCHAR(100) NOT NULL,
    contenido VARCHAR(500) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comentario_orden (fecha_creacion, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Respuesta (requirements.md §3). Siempre pertenece a un comentario existente (RN-005) y no
-- puede tener respuestas (RN-004): no hay ninguna referencia a otra respuesta. La relación es
-- restrictiva porque en la v1 nada se borra (D-03, RN-009).
CREATE TABLE respuesta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comentario_id INT UNSIGNED NOT NULL,
    autor VARCHAR(100) NOT NULL,
    contenido VARCHAR(500) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_respuesta_orden (comentario_id, fecha_creacion, id),
    CONSTRAINT fk_respuesta_comentario
        FOREIGN KEY (comentario_id) REFERENCES comentario(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sin datos de ejemplo: la base arranca vacía (D-11, F-COM-002 CA-3).
