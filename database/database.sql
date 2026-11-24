CREATE DATABASE IF NOT EXISTS sistema_somnolencia
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_somnolencia;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(120) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN','CONDUCTOR') NOT NULL DEFAULT 'CONDUCTOR',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesiones (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NULL,
    duracion_segundos INT NULL,
    cantidad_alertas INT NOT NULL DEFAULT 0,
    estado ENUM('EN_CURSO','FINALIZADA','ERROR') NOT NULL DEFAULT 'EN_CURSO',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_sesiones_usuario_fecha (id_usuario, fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_sesion INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    ear DECIMAL(6,4) NOT NULL,
    duracion_cierre_segundos DECIMAL(6,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_sesion) REFERENCES sesiones(id_sesion)
      ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_alertas_sesion_fecha (id_sesion, fecha_hora)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS configuracion (
    id_configuracion INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(60) NOT NULL UNIQUE,
    valor VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('umbral_ear', '0.20', 'Umbral de apertura ocular para detectar cierre'),
('tiempo_somnolencia_segundos', '2.0', 'Tiempo mínimo de cierre para generar alerta'),
('frecuencia_alarma', '3500', 'Frecuencia del pitido en Hz'),
('duracion_pitido_ms', '215', 'Duración del pitido en milisegundos');

CREATE TABLE IF NOT EXISTS vehiculos (
    id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
    codigo_vehiculo VARCHAR(30) NOT NULL UNIQUE,
    placa VARCHAR(15) NOT NULL UNIQUE,
    tamano VARCHAR(50) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    capacidad VARCHAR(50) NOT NULL,
    disponibilidad ENUM('Disponible','En uso','Mantenimiento') NOT NULL DEFAULT 'Disponible',
    categoria VARCHAR(60) NOT NULL,
    id_cliente_seleccion INT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente_seleccion) REFERENCES usuarios(id_usuario)
      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reportes_vehiculo (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    id_vehiculo INT NOT NULL,
    id_conductor INT NOT NULL,
    tipo_problema VARCHAR(60) NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_reporte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
      ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_conductor) REFERENCES usuarios(id_usuario)
      ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reportes_fecha (fecha_reporte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehículo que tenía el conductor al iniciar cada sesión (lo usa el reporte PDF)
ALTER TABLE sesiones
    ADD COLUMN IF NOT EXISTS id_vehiculo INT NULL AFTER id_usuario,
    ADD FOREIGN KEY IF NOT EXISTS sesiones_vehiculo_fk (id_vehiculo)
      REFERENCES vehiculos(id_vehiculo) ON DELETE SET NULL ON UPDATE CASCADE;
