CREATE DATABASE IF NOT EXISTS app_comentarios
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE app_comentarios;

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_parent
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO comments (author, content, parent_id, created_at) VALUES
('María', '¡Muy buena la clase de hoy! El concepto de vibe coding me dejó pensando.', NULL, NOW()),
('Juan', '¿Alguien pudo hacer funcionar la app después del sprint?', NULL, NOW()),
('Ana', 'Creo que el problema principal es que sin spec la IA no sabe qué puede tocar.', NULL, NOW());
