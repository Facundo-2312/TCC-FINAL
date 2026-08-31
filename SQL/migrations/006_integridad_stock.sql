-- =====================================================================
-- Migración 006 — Integridad de stock de productos (bugs reales corregidos)
-- =====================================================================
-- Motivo (bug 1 - sobreventa): el trigger original `descontar_stock` resta
-- stock en cada INSERT de detalle_pedido sin comprobar antes si hay stock
-- suficiente, permitiendo stock negativo (sobreventa) sin ningún aviso.
-- Motivo (bug 2 - stock perdido): al cancelar/eliminar un pedido
-- (EliminarPedido/PedidoRepository::eliminar borra detalle_pedido) el
-- stock descontado nunca se repone: el stock decrece permanentemente
-- aunque el pedido nunca se haya entregado.
-- Solución: trigger BEFORE INSERT que valida stock disponible y aborta
-- con SIGNAL si no alcanza, y trigger AFTER DELETE que repone el stock.
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE productos
    ADD CONSTRAINT IF NOT EXISTS chk_productos_precio CHECK (precio > 0),
    ADD CONSTRAINT IF NOT EXISTS chk_productos_stock CHECK (stock >= 0);

DROP TRIGGER IF EXISTS descontar_stock;
DROP TRIGGER IF EXISTS trg_detalle_pedido_validar_stock;
DROP TRIGGER IF EXISTS trg_detalle_pedido_reponer_stock;

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
