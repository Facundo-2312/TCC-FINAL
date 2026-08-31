-- =====================================================================
-- Migración 000 — Baseline: columna propina (idempotente)
-- =====================================================================
-- Ya aplicada manualmente en el entorno de desarrollo via SQL/propina.sql;
-- se re-expresa aquí de forma idempotente para que instalaciones nuevas o
-- desincronizadas queden al día ejecutando toda la carpeta migrations/ en
-- orden, sin depender de recordar aplicar SQL/propina.sql aparte.
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE pagos
    ADD COLUMN IF NOT EXISTS propina DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER monto;
