-- =====================================================================
-- Migración 008 — Optimización de consultas de agregados diarios
-- =====================================================================
-- Motivo: CAJA.php y chatbot_api.php calculan totales/propinas del día con
-- `WHERE DATE(fecha) = CURDATE()`. Envolver la columna en DATE(...) impide
-- que MySQL/MariaDB use el índice de fecha (funcion no sargable => escaneo
-- completo de la tabla `pagos` a medida que crece). Se agrega una columna
-- generada persistida con la fecha (sin hora) e indexada; las consultas
-- deben reescribirse para usarla (ver cambios aplicados en CAJA.php /
-- chatbot_api.php en este mismo cambio).
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE pagos
    ADD COLUMN IF NOT EXISTS fecha_dia DATE GENERATED ALWAYS AS (DATE(fecha)) STORED;

ALTER TABLE pagos
    ADD INDEX IF NOT EXISTS idx_pagos_fecha_dia (fecha_dia);
