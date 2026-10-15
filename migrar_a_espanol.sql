USE banco_preguntas;

ALTER TABLE exams DROP FOREIGN KEY fk_exams_user;
ALTER TABLE questions DROP FOREIGN KEY fk_questions_exam;

RENAME TABLE users TO usuarios, exams TO examenes, questions TO preguntas;

ALTER TABLE usuarios
    DROP INDEX uq_users_email,
    CHANGE COLUMN name nombre VARCHAR(100) NOT NULL,
    CHANGE COLUMN email correo VARCHAR(190) NOT NULL,
    CHANGE COLUMN password_hash clave_hash VARCHAR(255) NOT NULL,
    CHANGE COLUMN created_at creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD UNIQUE KEY uq_usuarios_correo (correo);

ALTER TABLE examenes
    DROP INDEX idx_exams_user_id,
    CHANGE COLUMN user_id usuario_id BIGINT UNSIGNED NOT NULL,
    CHANGE COLUMN title titulo VARCHAR(150) NOT NULL,
    CHANGE COLUMN description descripcion TEXT NULL,
    CHANGE COLUMN created_at creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD KEY idx_examenes_usuario_id (usuario_id),
    ADD CONSTRAINT fk_examenes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE;

ALTER TABLE preguntas
    DROP INDEX idx_questions_exam_id,
    CHANGE COLUMN exam_id examen_id BIGINT UNSIGNED NOT NULL,
    CHANGE COLUMN question_text texto_pregunta MEDIUMTEXT NOT NULL,
    CHANGE COLUMN answer_text texto_respuesta MEDIUMTEXT NULL,
    CHANGE COLUMN created_at creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD KEY idx_preguntas_examen_id (examen_id),
    ADD CONSTRAINT fk_preguntas_examen
        FOREIGN KEY (examen_id) REFERENCES examenes (id)
        ON DELETE CASCADE;
