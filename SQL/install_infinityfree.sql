-- RESTAURANTE-UY - Esquema para InfinityFree.
-- Selecciona primero la base vacia en phpMyAdmin y luego importa este archivo.
-- No crea ni elimina bases de datos, no agrega cuentas demo y no requiere triggers ni rutinas.

-- ROLES
-- =========================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (nombre) VALUES
('Administrador'),
('Caja'),
('Mozo'),
('Cocina'),
('Cliente');

-- =========================
-- USUARIOS: crear una cuenta de prueba segura despues de importar este esquema.

-- CATEGORIAS
-- =========================
CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (nombre) VALUES
('Bebidas'),
('Comidas'),
('Postres'),
('Otros');

-- =========================
-- PRODUCTOS
-- =========================
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    img VARCHAR(255),
    id_categoria INT,
    estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_productos_precio CHECK (precio > 0),
    CONSTRAINT chk_productos_stock CHECK (stock >= 0),
    INDEX idx_productos_estado (estado),
    INDEX idx_productos_id_categoria (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Importa SQL/catalogo_infinityfree.sql para cargar el catalogo completo.

-- MESAS
-- =========================
CREATE TABLE mesas (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    estado ENUM('Libre','Ocupada','Limpieza','Reservada') NOT NULL DEFAULT 'Libre',
    capacidad INT DEFAULT 4 COMMENT 'Cantidad de personas que puede ocupar',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mesas_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO mesas (numero) VALUES (1),(2),(3),(4),(5),(6);

-- =========================
-- MESAS_HISTORIAL (auditoria de cambios de estado de mesa)
-- =========================
CREATE TABLE mesas_historial (
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

-- =========================
-- RESERVAS (reserva de mesas para clientes)
-- =========================
CREATE TABLE reservas (
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

-- =========================
-- EMPLEADO (datos de RRHH; el acceso real vive en `usuarios`)
-- =========================
CREATE TABLE empleado (
    CI INT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellido VARCHAR(50) NOT NULL,
    Direccion VARCHAR(100),
    Rol VARCHAR(30) NOT NULL,
    id_rol INT NULL,
    Usuario VARCHAR(30) UNIQUE NOT NULL,
    Pass VARCHAR(255) NOT NULL,
    estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_empleado_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_empleado_id_rol (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CAJA (apertura/cierre de sesion de caja)
-- =========================
CREATE TABLE caja (
    id_caja INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME,
    monto_inicial DECIMAL(10,2) NOT NULL,
    monto_final DECIMAL(10,2),
    id_usuario INT,
    estado ENUM('Abierta','Cerrada') NOT NULL DEFAULT 'Abierta',
    unico_abierta TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'Abierta' THEN 1 ELSE NULL END) VIRTUAL,
    CONSTRAINT fk_caja_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_caja_una_abierta (unico_abierta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PEDIDOS
-- =========================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT,
    id_usuario INT,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('Pendiente','Preparando','Entregado','Cancelado','ArchivadoCocina') NOT NULL DEFAULT 'Pendiente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    observaciones VARCHAR(500) NULL,
    CONSTRAINT fk_pedidos_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_fecha (fecha),
    INDEX idx_pedidos_estado_fecha (estado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- DETALLE PEDIDOS
-- =========================
CREATE TABLE detalle_pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    sin_ingredientes VARCHAR(255) NULL,
    extra_ingredientes VARCHAR(255) NULL,
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
    INDEX idx_detalle_id_pedido (id_pedido),
    INDEX idx_detalle_id_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- El control de stock se realiza en PHP en este hosting, que no permite triggers.

-- PAGOS
-- =========================
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    propina DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_dia DATE GENERATED ALWAYS AS (DATE(fecha)) STORED,
    CONSTRAINT fk_pagos_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_pagos_monto CHECK (monto >= 0),
    CONSTRAINT chk_pagos_propina CHECK (propina >= 0),
    UNIQUE KEY uq_pagos_id_pedido (id_pedido),
    INDEX idx_pagos_fecha (fecha),
    INDEX idx_pagos_fecha_dia (fecha_dia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- MOVIMIENTOS CAJA
-- =========================
CREATE TABLE movimientos_caja (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_caja INT NOT NULL,
    tipo ENUM('Ingreso','Egreso') NOT NULL,
    descripcion VARCHAR(255),
    monto DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimientos_caja FOREIGN KEY (id_caja) REFERENCES caja(id_caja)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_movimientos_monto CHECK (monto >= 0),
    INDEX idx_movimientos_id_caja (id_caja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- SEGURIDAD: rate limiting de login (ver App\Support\LoginThrottle)
-- =========================
CREATE TABLE intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(191) NOT NULL,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
