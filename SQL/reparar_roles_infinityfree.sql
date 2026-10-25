-- RESTAURANTE-UY - Reparacion de roles para una base ya importada en InfinityFree.
-- El codigo identifica los roles por numero: 1 Administrador, 2 Caja, 3 Mozo, 4 Cocina, 5 Cliente.
-- Si el hosting numero los roles de otra forma, cada usuario se trata con un rol equivocado.
-- Este script renumera los roles segun su NOMBRE; usuarios.id_rol y empleado.id_rol se actualizan
-- solos por ON UPDATE CASCADE, asi que cada cuenta conserva su rol real. Se puede ejecutar mas de una vez.
-- Uso: phpMyAdmin > selecciona la base > pestana SQL (o Importar) > ejecutar este archivo completo.

START TRANSACTION;

-- 1) Liberar los IDs 1..5 moviendo temporalmente todos los roles fuera de ese rango.
UPDATE roles SET id_rol = id_rol + 100000 WHERE id_rol < 100000;

-- 2) Asignar el ID que espera el codigo segun el nombre del rol.
UPDATE roles
SET id_rol = CASE LOWER(TRIM(nombre))
    WHEN 'administrador' THEN 1
    WHEN 'caja' THEN 2
    WHEN 'mozo' THEN 3
    WHEN 'cocina' THEN 4
    WHEN 'cliente' THEN 5
END
WHERE LOWER(TRIM(nombre)) IN ('administrador', 'caja', 'mozo', 'cocina', 'cliente');

-- 3) Crear cualquier rol que falte (no modifica los existentes).
INSERT IGNORE INTO roles (id_rol, nombre) VALUES
(1, 'Administrador'),
(2, 'Caja'),
(3, 'Mozo'),
(4, 'Cocina'),
(5, 'Cliente');

COMMIT;

-- 4) Comprobacion: cada usuario debe aparecer junto al rol que corresponde.
SELECT u.usuario, u.id_rol, r.nombre AS rol
FROM usuarios u
LEFT JOIN roles r ON r.id_rol = u.id_rol
ORDER BY u.id_rol, u.usuario;
