-- =====================================================================
-- Migración 010 — Observaciones y personalización de pedidos de mozo
-- =====================================================================
-- El flujo MVCsix1.0/pedido permite observaciones del pedido y campos
-- "sin/agregar ingredientes" por producto. El esquema principal no los
-- tenía, por lo que se perderían al dejar de usar las tablas legacy.
-- Esta migración es aditiva e idempotente: no elimina ni modifica datos.
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(500) NULL AFTER total;

ALTER TABLE detalle_pedido
    ADD COLUMN IF NOT EXISTS sin_ingredientes VARCHAR(255) NULL AFTER subtotal,
    ADD COLUMN IF NOT EXISTS extra_ingredientes VARCHAR(255) NULL AFTER sin_ingredientes;