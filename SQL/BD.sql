DROP DATABASE IF EXISTS ProyectoMagnus;
CREATE DATABASE ProyectoMagnus CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ProyectoMagnus;

-- =========================
-- ROLES
-- =========================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

INSERT INTO roles (nombre) VALUES
('Administrador'),
('Caja'),
('Mozo'),
('Cocina');

-- =========================
-- USUARIOS / EMPLEADOS
-- =========================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    estado ENUM('Activo','Inactivo') DEFAULT 'Activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

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
);

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
    stock INT DEFAULT 0,
    img VARCHAR(255),
    id_categoria INT,
    estado ENUM('Activo','Inactivo') DEFAULT 'Activo',
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

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
    estado ENUM('Libre','Ocupada') DEFAULT 'Libre'
);

CREATE TABLE empleado (
    CI INT PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellido VARCHAR(50) NOT NULL,
    Direccion VARCHAR(100),
    Rol VARCHAR(30),
    Usuario VARCHAR(30) UNIQUE NOT NULL,
    Pass VARCHAR(255) NOT NULL
);

INSERT INTO mesas (numero) VALUES (1),(2),(3),(4),(5),(6);

-- =========================
-- CAJA
-- =========================
CREATE TABLE caja (
    id_caja INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME,
    monto_inicial DECIMAL(10,2) NOT NULL,
    monto_final DECIMAL(10,2),
    id_usuario INT,
    estado ENUM('Abierta','Cerrada') DEFAULT 'Abierta',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

INSERT INTO caja (fecha_apertura,monto_inicial,id_usuario)
VALUES (NOW(),5000,2);

-- =========================
-- PEDIDOS
-- =========================
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT,
    id_usuario INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente','Preparando','Entregado','Cancelado') DEFAULT 'Pendiente',
    total DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =========================
-- DETALLE PEDIDOS
-- =========================
CREATE TABLE detalle_pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_producto INT,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- =========================
-- PAGOS
-- =========================
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia'),
    monto DECIMAL(10,2),
    propina DECIMAL(10,2) DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
);

-- =========================
-- MOVIMIENTOS CAJA
-- =========================
CREATE TABLE movimientos_caja (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_caja INT,
    tipo ENUM('Ingreso','Egreso'),
    descripcion VARCHAR(255),
    monto DECIMAL(10,2),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_caja) REFERENCES caja(id_caja)
);

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
DELIMITER ;

DELIMITER //
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

-- =========================
-- TRIGGERS
-- =========================
DELIMITER //
CREATE TRIGGER descontar_stock
AFTER INSERT ON detalle_pedido
FOR EACH ROW
BEGIN
    UPDATE productos
    SET stock = stock - NEW.cantidad
    WHERE id_producto = NEW.id_producto;
END//
DELIMITER ;

-- =========================
-- CONSULTAS IMPORTANTES
-- =========================

-- Login
SELECT u.id_usuario,u.nombre,r.nombre
FROM usuarios u
JOIN roles r ON u.id_rol=r.id_rol
WHERE u.usuario='admin' AND u.password=MD5('admin123');

-- Productos activos
SELECT * FROM productos WHERE estado='Activo';

-- Pedidos pendientes cocina
SELECT p.id_pedido,m.numero,p.fecha
FROM pedidos p
JOIN mesas m ON p.id_mesa=m.id_mesa
WHERE p.estado='Pendiente';

-- Total vendido hoy
SELECT SUM(total) AS total_dia
FROM pedidos
WHERE DATE(fecha)=CURDATE() AND estado='Entregado';

-- Caja abierta
SELECT * FROM caja WHERE estado='Abierta';