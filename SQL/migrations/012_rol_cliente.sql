-- Migracion 012 - Rol de cliente para pedidos desde el catalogo.
-- Es aditiva y no modifica cuentas ni pedidos existentes.
USE ProyectoMagnus;

INSERT INTO roles (nombre)
SELECT 'Cliente'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE LOWER(nombre) = 'cliente');