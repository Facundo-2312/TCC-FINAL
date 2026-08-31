-- =====================================================================
-- Migración 001 — Índices para consultas frecuentes
-- =====================================================================
-- Motivo: pedidos.estado y pedidos.fecha se filtran/ordenan en casi todas
-- las pantallas (cocina, caja, pedidos) sin índice; productos.estado se
-- filtra en el catálogo; mesas.numero y roles.nombre deben ser únicos por
-- regla de negocio (no puede haber dos mesas con el mismo número ni dos
-- roles con el mismo nombre).
-- Seguro de re-ejecutar: usa IF NOT EXISTS (soportado en MariaDB >= 10.0).
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE pedidos
    ADD INDEX IF NOT EXISTS idx_pedidos_estado (estado),
    ADD INDEX IF NOT EXISTS idx_pedidos_fecha (fecha),
    ADD INDEX IF NOT EXISTS idx_pedidos_estado_fecha (estado, fecha);

ALTER TABLE productos
    ADD INDEX IF NOT EXISTS idx_productos_estado (estado);

ALTER TABLE mesas
    ADD UNIQUE KEY IF NOT EXISTS uq_mesas_numero (numero);

ALTER TABLE roles
    ADD UNIQUE KEY IF NOT EXISTS uq_roles_nombre (nombre);

ALTER TABLE pagos
    ADD INDEX IF NOT EXISTS idx_pagos_fecha (fecha);
