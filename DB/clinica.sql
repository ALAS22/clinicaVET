CREATE DATABASE IF NOT EXISTS clinica
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clinica;

CREATE TABLE IF NOT EXISTS usuario (
  usuario_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  usuario_nombre  VARCHAR(80)      NOT NULL,
  usuario_edad    TINYINT UNSIGNED NOT NULL,
  usuario_email   VARCHAR(100)     NOT NULL,
  usuario_clave   VARCHAR(255)     NOT NULL,
  usuario_rol     ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  usuario_creado  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id),
  UNIQUE KEY uq_usuario_email (usuario_email)
) ENGINE=InnoDB;