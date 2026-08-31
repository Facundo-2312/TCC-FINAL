-- =====================================================================
-- Migración 009 — Formaliza tablas creadas en tiempo de ejecución por PHP
-- =====================================================================
-- Motivo: intentos_login (rate limiting de login) se crea hoy solo desde
-- App\Support\LoginThrottle en el primer intento de login. Se formaliza
-- aquí para que una instalación limpia (o una réplica de esquema) tenga
-- la tabla desde el principio, sin depender de que el flujo de login se
-- ejecute primero. 100% compatible: misma definición que crea el código.
-- =====================================================================
USE ProyectoMagnus;

CREATE TABLE IF NOT EXISTS intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(191) NOT NULL,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL,
    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
