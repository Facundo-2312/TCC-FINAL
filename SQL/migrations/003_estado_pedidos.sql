-- =====================================================================
-- Migración 003 — Estado de pedidos consistente (bug real corregido)
-- =====================================================================
-- Motivo: Cocina2.php y CAJA.php usan el estado 'ArchivadoCocina' para
-- ocultar pedidos ya entregados/retirados de la vista de cocina
-- (UPDATE pedidos SET estado = 'ArchivadoCocina' ...), pero el ENUM
-- original de pedidos.estado solo admite
-- ('Pendiente','Preparando','Entregado','Cancelado'). En MariaDB, asignar
-- un valor fuera del ENUM lo trunca silenciosamente a cadena vacía ''
-- (fuera de modo estricto) en vez de fallar, dejando el pedido en un
-- estado inconsistente que ninguna pantalla filtra correctamente.
-- Solución: ampliar el ENUM para incluir el valor realmente usado por el
-- código actual, sin eliminar ninguno de los valores existentes.
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE pedidos
    MODIFY estado ENUM('Pendiente','Preparando','Entregado','Cancelado','ArchivadoCocina')
    NOT NULL DEFAULT 'Pendiente';
