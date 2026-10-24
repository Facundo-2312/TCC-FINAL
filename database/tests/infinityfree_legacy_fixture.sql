-- Synthetic legacy-schema fixture; contains no hosting data or real credentials.
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_usuarios_id_rol (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    img VARCHAR(255),
    id_categoria INT,
    estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
    CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_productos_precio CHECK (precio > 0),
    CONSTRAINT chk_productos_stock CHECK (stock >= 0),
    INDEX idx_productos_estado (estado),
    INDEX idx_productos_id_categoria (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mesas (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    estado ENUM('Libre','Ocupada') NOT NULL DEFAULT 'Libre',
    UNIQUE KEY uq_mesas_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE empleado (
    CI INT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellido VARCHAR(50) NOT NULL,
    Direccion VARCHAR(100),
    Rol VARCHAR(30) NOT NULL,
    Usuario VARCHAR(30) UNIQUE NOT NULL,
    Pass VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE caja (
    id_caja INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME,
    monto_inicial DECIMAL(10,2) NOT NULL,
    monto_final DECIMAL(10,2),
    id_usuario INT,
    estado ENUM('Abierta','Cerrada') NOT NULL DEFAULT 'Abierta',
    CONSTRAINT fk_caja_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT,
    id_usuario INT,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente','Preparando','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_pedidos_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_fecha (fecha),
    INDEX idx_pedidos_estado_fecha (estado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE detalle_pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0),
    INDEX idx_detalle_id_pedido (id_pedido),
    INDEX idx_detalle_id_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    propina DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagos_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_pagos_monto CHECK (monto >= 0),
    CONSTRAINT chk_pagos_propina CHECK (propina >= 0),
    UNIQUE KEY uq_pagos_id_pedido (id_pedido),
    INDEX idx_pagos_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT INTO roles (id_rol,nombre) VALUES (1,'Administrador'),(2,'Caja'),(3,'Mozo'),(4,'Cocina');
INSERT INTO usuarios (nombre,usuario,password,id_rol) VALUES ('Synthetic Test','synthetic_test','not-a-real-password-hash',1);
INSERT INTO categorias (nombre) VALUES ('Synthetic');
INSERT INTO productos(nombre,precio,stock,id_categoria) VALUES ('Synthetic',100,7,1);
INSERT INTO mesas(numero) VALUES (1),(2);
INSERT INTO caja(fecha_apertura,monto_inicial,id_usuario) VALUES ('2026-01-01 12:00:00',123,1);
INSERT INTO pedidos(id_mesa,id_usuario,total) VALUES (1,1,100);
INSERT INTO detalle_pedido(id_pedido,id_producto,cantidad,precio,subtotal) VALUES (1,1,1,100,100);
