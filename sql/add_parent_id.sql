USE app_comentarios;

ALTER TABLE comments
    ADD COLUMN parent_id INT NULL AFTER content;

ALTER TABLE comments
    ADD CONSTRAINT fk_comment_parent
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE;
