-- =====================================================================
-- RESTAURANTE-UY — Instalación limpia (esquema profesional consolidado)
-- =====================================================================
-- Este script reemplaza a SQL/BD.sql + SQL/propina.sql + SQL/migrations/*
-- para instalaciones NUEVAS: crea la base de datos desde cero ya con
-- todas las correcciones/mejoras de la auditoría aplicadas directamente
-- (no hace falta correr las migraciones después de este script).
--
-- Si ya tienes una base de datos ProyectoMagnus con datos reales,
-- NO ejecutes este archivo (borra la base existente). Usa en su lugar
-- SQL/migrations/*.sql en orden, que son idempotentes y no destructivos.
--
-- Nombres de tablas/columnas 100% compatibles con el código PHP actual.
-- =====================================================================

DROP DATABASE IF EXISTS ProyectoMagnus;
CREATE DATABASE ProyectoMagnus CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ProyectoMagnus;

-- =========================
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
('Cocina');

-- =========================
-- USUARIOS (acceso al sistema)
-- =========================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_usuarios_id_rol (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password real de ejemplo: admin123 / 1234 (hash MD5 heredado). Empleado::Login()
-- migra automaticamente a password_hash() (bcrypt) en el primer login exitoso.
INSERT INTO usuarios (nombre,usuario,password,id_rol) VALUES
('Administrador General','admin',MD5('admin123'),1),
('Juan Mozo','juan',MD5('1234'),3),
('Maria Caja','maria',MD5('1234'),2),
('Pedro Cocina','pedro',MD5('1234'),4);

-- =========================
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

INSERT INTO productos (nombre,descripcion,precio,stock,id_categoria,img) VALUES
('Hamburguesa','Hamburguesa completa',350,50,2,'hamburguesa.jpg'),
('Pizza','Pizza muzzarella',400,40,2,'pizza.jpg'),
('Coca Cola','Bebida 500ml',150,100,1,'coca.jpg'),
('Agua','Agua mineral',120,80,1,'agua.jpg');

-- =========================
-- MESAS
-- =========================
CREATE TABLE mesas (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    estado ENUM('Libre','Ocupada','Limpieza') NOT NULL DEFAULT 'Libre',
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

-- Mantiene empleado.id_rol sincronizada automaticamente a partir del texto Rol,
-- para que el codigo PHP existente (que solo escribe la columna Rol) siga
-- funcionando sin cambios y la integridad quede garantizada por la BD.
DELIMITER //
CREATE TRIGGER trg_empleado_sync_rol_insert
BEFORE INSERT ON empleado
FOR EACH ROW
BEGIN
    DECLARE v_id_rol INT DEFAULT NULL;

    SELECT id_rol INTO v_id_rol
    FROM roles
    WHERE LOWER(nombre) = LOWER(
        CASE LOWER(TRIM(NEW.Rol))
            WHEN 'admin' THEN 'Administrador'
            WHEN 'administrador' THEN 'Administrador'
            WHEN 'caja' THEN 'Caja'
            WHEN 'cajero' THEN 'Caja'
            WHEN 'mozo' THEN 'Mozo'
            WHEN 'cocina' THEN 'Cocina'
            WHEN 'cocinero' THEN 'Cocina'
            ELSE NEW.Rol
        END
    )
    LIMIT 1;

    IF v_id_rol IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol de empleado invalido: no existe en la tabla roles.';
    END IF;

    SET NEW.id_rol = v_id_rol;
END//

CREATE TRIGGER trg_empleado_sync_rol_update
BEFORE UPDATE ON empleado
FOR EACH ROW
BEGIN
    DECLARE v_id_rol INT DEFAULT NULL;

    IF NEW.Rol <> OLD.Rol THEN
        SELECT id_rol INTO v_id_rol
        FROM roles
        WHERE LOWER(nombre) = LOWER(
            CASE LOWER(TRIM(NEW.Rol))
                WHEN 'admin' THEN 'Administrador'
                WHEN 'administrador' THEN 'Administrador'
                WHEN 'caja' THEN 'Caja'
                WHEN 'cajero' THEN 'Caja'
                WHEN 'mozo' THEN 'Mozo'
                WHEN 'cocina' THEN 'Cocina'
                WHEN 'cocinero' THEN 'Cocina'
                ELSE NEW.Rol
            END
        )
        LIMIT 1;

        IF v_id_rol IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol de empleado invalido: no existe en la tabla roles.';
        END IF;

        SET NEW.id_rol = v_id_rol;
    END IF;
END//
DELIMITER ;

-- =========================
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

INSERT INTO caja (fecha_apertura,monto_inicial,id_usuario)
VALUES (NOW(),5000,2);

-- =========================
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

-- Integridad de stock: valida disponibilidad antes de vender y repone stock
-- si el detalle se elimina (p. ej. al cancelar un pedido).
DELIMITER //
CREATE TRIGGER trg_detalle_pedido_validar_stock
BEFORE INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    SELECT stock INTO v_stock FROM productos WHERE id_producto = NEW.id_producto FOR UPDATE;

    IF v_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El producto del detalle de pedido no existe.';
    ELSEIF v_stock < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para el producto solicitado.';
    END IF;

    UPDATE productos SET stock = stock - NEW.cantidad WHERE id_producto = NEW.id_producto;
END//

CREATE TRIGGER trg_detalle_pedido_reponer_stock
AFTER DELETE ON detalle_pedido
FOR EACH ROW
BEGIN
    UPDATE productos SET stock = stock + OLD.cantidad WHERE id_producto = OLD.id_producto;
END//
DELIMITER ;

-- =========================
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
-- VISTAS
-- =========================
CREATE VIEW vista_ventas AS
SELECT p.id_pedido,p.fecha,u.nombre AS empleado,p.total
FROM pedidos p
JOIN usuarios u ON p.id_usuario=u.id_usuario
WHERE p.estado='Entregado';

CREATE VIEW vista_productos_vendidos AS
SELECT pr.nombre,SUM(dp.cantidad) AS total_vendidos
FROM detalle_pedido dp
JOIN productos pr ON dp.id_producto=pr.id_producto
GROUP BY pr.nombre;

CREATE VIEW vista_pedidos_mesa AS
SELECT p.id_pedido,m.numero,p.estado,p.total
FROM pedidos p
JOIN mesas m ON p.id_mesa=m.id_mesa;

-- =========================
-- PROCEDIMIENTOS
-- =========================
DELIMITER //
CREATE PROCEDURE crear_pedido(IN mesa INT, IN usuario INT)
BEGIN
    INSERT INTO pedidos(id_mesa,id_usuario) VALUES (mesa,usuario);
    UPDATE mesas SET estado='Ocupada' WHERE id_mesa=mesa;
END//

CREATE PROCEDURE agregar_producto(
    IN pedido INT,
    IN producto INT,
    IN cant INT
)
BEGIN
    DECLARE precio_prod DECIMAL(10,2);
    SELECT precio INTO precio_prod FROM productos WHERE id_producto=producto;

    INSERT INTO detalle_pedido(id_pedido,id_producto,cantidad,precio,subtotal)
    VALUES(pedido,producto,cant,precio_prod,precio_prod*cant);

    UPDATE pedidos
    SET total = (SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido=pedido)
    WHERE id_pedido=pedido;
END//
DELIMITER ;
