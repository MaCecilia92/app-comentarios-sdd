USE app_comentarios;

ALTER TABLE comments
    ADD COLUMN likes INT NOT NULL DEFAULT 0 AFTER content;
