CREATE DATABASE IF NOT EXISTS banco_preguntas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE banco_preguntas;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(190) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS examenes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_examenes_usuario_id (usuario_id),
    CONSTRAINT fk_examenes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS preguntas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    examen_id BIGINT UNSIGNED NOT NULL,
    texto_pregunta MEDIUMTEXT NOT NULL,
    texto_respuesta MEDIUMTEXT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_preguntas_examen_id (examen_id),
    CONSTRAINT fk_preguntas_examen
        FOREIGN KEY (examen_id) REFERENCES examenes (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
