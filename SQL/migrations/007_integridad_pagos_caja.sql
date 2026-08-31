-- =====================================================================
-- Migración 007 — Integridad de pagos y caja (riesgo de concurrencia)
-- =====================================================================
-- Motivo (pagos): CAJA.php/GuardarPago.php verifican "¿ya existe un pago
-- para este pedido?" con un SELECT antes del INSERT, pero sin constraint
-- de base de datos eso es una condición de carrera (TOCTOU): dos clics de
-- "Facturar" simultáneos pueden generar dos pagos para el mismo pedido.
-- Un UNIQUE(id_pedido) hace esa duplicación imposible a nivel de BD.
-- Motivo (caja): no había ninguna regla que impida abrir dos cajas a la
-- vez; se agrega una columna virtual + índice único que garantiza que
-- como máximo una fila puede tener estado='Abierta' en todo momento.
-- =====================================================================
USE ProyectoMagnus;

-- Si ya existieran pagos duplicados por pedido (no debería, la app los evita),
-- esta migración falla con un error claro en vez de corromper datos en silencio.
ALTER TABLE pagos
    ADD UNIQUE KEY IF NOT EXISTS uq_pagos_id_pedido (id_pedido);

ALTER TABLE caja
    ADD COLUMN IF NOT EXISTS unico_abierta TINYINT
        GENERATED ALWAYS AS (CASE WHEN estado = 'Abierta' THEN 1 ELSE NULL END) VIRTUAL;

ALTER TABLE caja
    ADD UNIQUE KEY IF NOT EXISTS uq_caja_una_abierta (unico_abierta);
