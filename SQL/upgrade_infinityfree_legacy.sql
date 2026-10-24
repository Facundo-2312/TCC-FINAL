-- Additive compatibility upgrade for the legacy InfinityFree schema.
-- MariaDB 11.4. Back up the database and review before executing manually.
-- Does not run in deployment; does not delete tables, rows or credentials.
-- Preflight: stop if any query below returns a row.
SELECT id_rol, nombre FROM roles WHERE (id_rol = 5 AND LOWER(nombre) <> 'cliente') OR (LOWER(nombre) = 'cliente' AND id_rol <> 5);
SELECT numero FROM mesas GROUP BY numero HAVING COUNT(*) > 1;
SELECT nombre FROM roles GROUP BY nombre HAVING COUNT(*) > 1;
SELECT id_pedido FROM pagos WHERE id_pedido IS NOT NULL GROUP BY id_pedido HAVING COUNT(*) > 1;
SELECT COUNT(*) AS cajas_abiertas FROM caja WHERE estado = 'Abierta' HAVING COUNT(*) > 1;

-- Execute the following only after the preflight is clear.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE mesas ADD COLUMN IF NOT EXISTS capacidad INT DEFAULT 4;
ALTER TABLE mesas ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE mesas ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE empleado ADD COLUMN IF NOT EXISTS id_rol INT NULL;
ALTER TABLE empleado ADD COLUMN IF NOT EXISTS estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo';
ALTER TABLE empleado ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE empleado ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE caja ADD COLUMN IF NOT EXISTS unico_abierta TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'Abierta' THEN 1 ELSE NULL END) VIRTUAL;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500) NULL;
ALTER TABLE detalle_pedido ADD COLUMN IF NOT EXISTS sin_ingredientes VARCHAR(255) NULL;
ALTER TABLE detalle_pedido ADD COLUMN IF NOT EXISTS extra_ingredientes VARCHAR(255) NULL;
ALTER TABLE pagos ADD COLUMN IF NOT EXISTS fecha_dia DATE GENERATED ALWAYS AS (DATE(fecha)) STORED;

ALTER TABLE mesas MODIFY COLUMN estado ENUM('Libre','Ocupada','Limpieza','Reservada') NULL DEFAULT 'Libre';
ALTER TABLE pedidos MODIFY COLUMN estado ENUM('Pendiente','Preparando','Entregado','Cancelado','ArchivadoCocina') NULL DEFAULT 'Pendiente';

CREATE TABLE IF NOT EXISTS mesas_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    estado_anterior VARCHAR(30) NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mesas_historial_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_mesa_fecha (id_mesa, fecha),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    nombre_cliente VARCHAR(100) NOT NULL COMMENT 'Nombre de la persona que hizo la reserva',
    cantidad_personas INT NOT NULL COMMENT 'Cantidad de comensales',
    hora_inicio DATETIME NOT NULL COMMENT 'Cuando comienza la reserva',
    hora_fin DATETIME NOT NULL COMMENT 'Cuando termina la reserva',
    telefono VARCHAR(20) COMMENT 'Telefono de contacto',
    notas TEXT COMMENT 'Observaciones adicionales',
    id_usuario INT COMMENT 'Usuario que creo la reserva',
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

CREATE TABLE IF NOT EXISTS solicitudes_cobro (
    id_pedido INT NOT NULL PRIMARY KEY,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL,
    solicitada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitudes_cobro_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(191) NOT NULL,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id_rol, nombre)
SELECT 5, 'Cliente' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE id_rol = 5 OR LOWER(nombre) = 'cliente');

CREATE UNIQUE INDEX IF NOT EXISTS uq_roles_nombre ON roles (nombre);
CREATE UNIQUE INDEX IF NOT EXISTS uq_mesas_numero ON mesas (numero);
CREATE UNIQUE INDEX IF NOT EXISTS uq_caja_una_abierta ON caja (unico_abierta);
CREATE UNIQUE INDEX IF NOT EXISTS uq_pagos_id_pedido ON pagos (id_pedido);
CREATE INDEX IF NOT EXISTS idx_productos_estado ON productos (estado);
CREATE INDEX IF NOT EXISTS idx_empleado_id_rol ON empleado (id_rol);
CREATE INDEX IF NOT EXISTS idx_pedidos_estado_fecha ON pedidos (estado, fecha);
CREATE INDEX IF NOT EXISTS idx_pagos_fecha ON pagos (fecha);
CREATE INDEX IF NOT EXISTS idx_pagos_fecha_dia ON pagos (fecha_dia);

-- Existing nullability and foreign keys are preserved intentionally.
-- No triggers: APP_DB_STOCK_TRIGGERS=0 remains required.
