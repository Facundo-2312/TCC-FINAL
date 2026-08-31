-- =====================================================================
-- RESTAURANTE-UY — Datos de demostración
-- =====================================================================
-- Ejecutar DESPUÉS de SQL/install.sql (o de una base ya migrada con
-- SQL/migrations/*). Agrega funcionarios, más productos/mesas, y un
-- historial de pedidos/pagos de ejemplo para poder mostrar el sistema
-- con datos realistas. No borra nada existente (usa INSERT, no TRUNCATE).
-- =====================================================================
USE ProyectoMagnus;

-- Más categorías/productos de catálogo
INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, img) VALUES
('Papas Fritas', 'Porción de papas fritas', 220, 60, 2, 'papas.jpg'),
('Milanesa Napolitana', 'Milanesa con jamón, muzzarella y salsa', 480, 30, 2, 'milanesa.jpg'),
('Ensalada César', 'Lechuga, pollo, crutones y aderezo César', 390, 25, 2, 'ensalada.jpg'),
('Tiramisú', 'Postre italiano clásico', 260, 20, 3, 'tiramisu.jpg'),
('Flan Casero', 'Flan con dulce de leche y crema', 210, 25, 3, 'flan.jpg'),
('Cerveza Artesanal', 'Botella 500ml', 320, 40, 1, 'cerveza.jpg'),
('Limonada', 'Limonada natural con menta', 180, 50, 1, 'limonada.jpg');

-- Más mesas (salón B)
INSERT INTO mesas (numero) VALUES (7),(8),(9),(10);

-- Funcionarios de ejemplo (uno por rol, ademas del admin/juan/maria/pedro ya creados
-- por install.sql). El trigger trg_empleado_sync_rol_insert resuelve id_rol solo.
INSERT INTO empleado (CI, Nombre, Apellido, Direccion, Rol, Usuario, Pass) VALUES
(40111222, 'Lucia', 'Fernandez', 'Av. Italia 1234', 'Caja', 'lucia.caja', '$2y$10$4Q8k5r0m9G8t8YQ1o1s8QOe1o3m1x0d8y2n4b6q9r8t2v5w7z9a1S'),
(40222333, 'Martin', 'Perez', '18 de Julio 2200', 'Mozo', 'martin.mozo', '$2y$10$4Q8k5r0m9G8t8YQ1o1s8QOe1o3m1x0d8y2n4b6q9r8t2v5w7z9a1S'),
(40333444, 'Sofia', 'Gomez', 'Bvar. Artigas 900', 'Cocina', 'sofia.cocina', '$2y$10$4Q8k5r0m9G8t8YQ1o1s8QOe1o3m1x0d8y2n4b6q9r8t2v5w7z9a1S');

-- NOTA: el hash de ejemplo arriba NO corresponde a una contraseña real utilizable;
-- reemplaza estos usuarios con `password_hash()` real o usa el flujo normal de
-- alta de empleados desde la app (que genera el hash correctamente).

-- Pedido de ejemplo ya entregado y facturado (mesa 1, mozo juan)
SET @id_usuario_mozo = (SELECT id_usuario FROM usuarios WHERE usuario = 'juan' LIMIT 1);
SET @id_mesa_1 = (SELECT id_mesa FROM mesas WHERE numero = 1 LIMIT 1);
SET @id_prod_hamburguesa = (SELECT id_producto FROM productos WHERE nombre = 'Hamburguesa' LIMIT 1);
SET @id_prod_coca = (SELECT id_producto FROM productos WHERE nombre = 'Coca Cola' LIMIT 1);

INSERT INTO pedidos (id_mesa, id_usuario, estado, total)
VALUES (@id_mesa_1, @id_usuario_mozo, 'Entregado', 0);
SET @id_pedido_demo1 = LAST_INSERT_ID();

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
VALUES (@id_pedido_demo1, @id_prod_hamburguesa, 2, 350, 700);
INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
VALUES (@id_pedido_demo1, @id_prod_coca, 2, 150, 300);

UPDATE pedidos SET total = 1000 WHERE id_pedido = @id_pedido_demo1;

INSERT INTO pagos (id_pedido, metodo_pago, monto, propina)
VALUES (@id_pedido_demo1, 'Efectivo', 1000, 100);

-- Pedido de ejemplo pendiente en cocina (mesa 2)
SET @id_mesa_2 = (SELECT id_mesa FROM mesas WHERE numero = 2 LIMIT 1);
SET @id_prod_pizza = (SELECT id_producto FROM productos WHERE nombre = 'Pizza' LIMIT 1);

INSERT INTO pedidos (id_mesa, id_usuario, estado, total)
VALUES (@id_mesa_2, @id_usuario_mozo, 'Pendiente', 0);
SET @id_pedido_demo2 = LAST_INSERT_ID();

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
VALUES (@id_pedido_demo2, @id_prod_pizza, 1, 400, 400);

UPDATE pedidos SET total = 400 WHERE id_pedido = @id_pedido_demo2;
UPDATE mesas SET estado = 'Ocupada' WHERE id_mesa = @id_mesa_2;
