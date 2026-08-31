-- =====================================================================
-- Migración 005 — Integridad referencial de mesas_historial
-- =====================================================================
-- Motivo: mesas.php crea mesas_historial en tiempo de ejecución
-- (CREATE TABLE IF NOT EXISTS) pero sin FK hacia mesas, permitiendo
-- registros huérfanos si se borra una mesa. Se agrega la FK sin afectar
-- el comportamiento actual (la app nunca borra mesas).
-- =====================================================================
USE ProyectoMagnus;

CREATE TABLE IF NOT EXISTS mesas_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    estado_anterior VARCHAR(30) NOT NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mesa_fecha (id_mesa, fecha),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE mesas_historial
    DROP FOREIGN KEY IF EXISTS fk_mesas_historial_mesa;

ALTER TABLE mesas_historial
    ADD CONSTRAINT fk_mesas_historial_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa)
    ON DELETE CASCADE ON UPDATE CASCADE;
