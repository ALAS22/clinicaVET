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

CREATE TABLE IF NOT EXISTS mascota (
  mascota_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  usuario_id      INT UNSIGNED     NOT NULL,
  mascota_nombre  VARCHAR(40)      NOT NULL,
  mascota_especie ENUM('perro','gato','ave','conejo','otro') NOT NULL,
  mascota_edad    TINYINT UNSIGNED NOT NULL,
  mascota_creada  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (mascota_id),
  KEY idx_mascota_usuario (usuario_id),
  CONSTRAINT fk_mascota_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuario (usuario_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cita(
  cita_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mascota_id INT UNSIGNED NOT NULL ,
  usuario_id INT UNSIGNED NOT NULL,
  cita_fecha_hora DATETIME NOT NULL,
  cita_motivo VARCHAR(200) NOT NULL,
  cita_estado ENUM('aprobada','pendiente','cancelada','atendida') NOT NULL DEFAULT 'pendiente',
  cita_comentario VARCHAR(200) NULL,
  cita_slot DATETIME AS(IF(cita_estado = 'cancelada',NULL,cita_fecha_hora))STORED,

  PRIMARY KEY(cita_id),
  UNIQUE KEY uq_cita_slot(cita_slot),
  FOREIGN KEY (usuario_id) REFERENCES usuario(usuario_id),
  FOREIGN KEY (mascota_id) REFERENCES mascota(mascota_id)ON DELETE CASCADE
)ENGINE=InnoDB;
