-- =====================================================================
-- Migración 011 — Sistema de Reservas de Mesas
-- =====================================================================
-- Motivo: Agregar funcionalidad de reservas con detalles de cliente,
-- duración y capacidad. Permite gestionar reservas futuras y cambiar
-- el estado de mesas de forma más flexible.
-- =====================================================================
USE ProyectoMagnus;

-- Agregar columnas a mesas para soporte de reservas
ALTER TABLE mesas ADD COLUMN capacidad INT DEFAULT 4 COMMENT 'Cantidad de personas que puede ocupar';

-- Nueva tabla de reservas
CREATE TABLE IF NOT EXISTS reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    nombre_cliente VARCHAR(100) NOT NULL COMMENT 'Nombre de la persona que hizo la reserva',
    cantidad_personas INT NOT NULL COMMENT 'Cantidad de comensales',
    hora_inicio DATETIME NOT NULL COMMENT 'Cuando comienza la reserva',
    hora_fin DATETIME NOT NULL COMMENT 'Cuando termina la reserva',
    telefono VARCHAR(20) COMMENT 'Teléfono de contacto',
    notas TEXT COMMENT 'Observaciones adicionales',
    id_usuario INT COMMENT 'Usuario que creó la reserva',
    estado ENUM('Confirmada','Cancelada','Completada') DEFAULT 'Confirmada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservas_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reservas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_mesa_fecha (id_mesa, hora_inicio),
    INDEX idx_hora_inicio (hora_inicio),
    INDEX idx_estado (estado),
    INDEX idx_cliente (nombre_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Actualizar tabla mesas para agregar "Reservada" como estado
ALTER TABLE mesas MODIFY estado ENUM('Libre','Ocupada','Limpieza','Reservada') DEFAULT 'Libre';
