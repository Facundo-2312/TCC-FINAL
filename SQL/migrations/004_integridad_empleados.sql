-- =====================================================================
-- Migración 004 — Integridad referencial de empleados (Rol como FK real)
-- =====================================================================
-- Motivo: empleado.Rol es un VARCHAR libre (no referencia a roles.id_rol),
-- lo que permite valores inconsistentes/typos ("cocina" vs "Cocina" vs
-- "cocinero") y obliga a la app a "adivinar" el id_rol con un mapeo de
-- alias en PHP (Empleado::resolverIdRolPorNombre). Se agrega una columna
-- id_rol con FK real hacia roles, y un trigger que la mantiene sincronizada
-- automáticamente a partir del texto Rol en cada INSERT/UPDATE, así el
-- código PHP existente (que solo escribe la columna Rol) no necesita
-- cambios y la integridad queda garantizada a nivel de base de datos.
-- =====================================================================
USE ProyectoMagnus;

ALTER TABLE empleado
    ADD COLUMN IF NOT EXISTS id_rol INT NULL,
    ADD COLUMN IF NOT EXISTS estado ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo';

-- Backfill de id_rol a partir del texto Rol actual (case-insensitive, con alias comunes).
UPDATE empleado e
JOIN roles r ON LOWER(r.nombre) = LOWER(
    CASE LOWER(TRIM(e.Rol))
        WHEN 'admin' THEN 'Administrador'
        WHEN 'administrador' THEN 'Administrador'
        WHEN 'caja' THEN 'Caja'
        WHEN 'cajero' THEN 'Caja'
        WHEN 'mozo' THEN 'Mozo'
        WHEN 'cocina' THEN 'Cocina'
        WHEN 'cocinero' THEN 'Cocina'
        ELSE e.Rol
    END
)
SET e.id_rol = r.id_rol
WHERE e.id_rol IS NULL;

ALTER TABLE empleado
    DROP FOREIGN KEY IF EXISTS fk_empleado_rol;

ALTER TABLE empleado
    ADD CONSTRAINT fk_empleado_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
    ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE empleado
    ADD INDEX IF NOT EXISTS idx_empleado_id_rol (id_rol);

DROP TRIGGER IF EXISTS trg_empleado_sync_rol_insert;
DROP TRIGGER IF EXISTS trg_empleado_sync_rol_update;

DELIMITER //
CREATE TRIGGER trg_empleado_sync_rol_insert
BEFORE INSERT ON empleado
FOR EACH ROW
BEGIN
    DECLARE v_id_rol INT DEFAULT NULL;

    SELECT id_rol INTO v_id_rol
    FROM roles
    WHERE LOWER(nombre) = LOWER(
        CASE LOWER(TRIM(NEW.Rol))
            WHEN 'admin' THEN 'Administrador'
            WHEN 'administrador' THEN 'Administrador'
            WHEN 'caja' THEN 'Caja'
            WHEN 'cajero' THEN 'Caja'
            WHEN 'mozo' THEN 'Mozo'
            WHEN 'cocina' THEN 'Cocina'
            WHEN 'cocinero' THEN 'Cocina'
            ELSE NEW.Rol
        END
    )
    LIMIT 1;

    IF v_id_rol IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol de empleado invalido: no existe en la tabla roles.';
    END IF;

    SET NEW.id_rol = v_id_rol;
END//

CREATE TRIGGER trg_empleado_sync_rol_update
BEFORE UPDATE ON empleado
FOR EACH ROW
BEGIN
    DECLARE v_id_rol INT DEFAULT NULL;

    IF NEW.Rol <> OLD.Rol OR OLD.Rol IS NULL THEN
        SELECT id_rol INTO v_id_rol
        FROM roles
        WHERE LOWER(nombre) = LOWER(
            CASE LOWER(TRIM(NEW.Rol))
                WHEN 'admin' THEN 'Administrador'
                WHEN 'administrador' THEN 'Administrador'
                WHEN 'caja' THEN 'Caja'
                WHEN 'cajero' THEN 'Caja'
                WHEN 'mozo' THEN 'Mozo'
                WHEN 'cocina' THEN 'Cocina'
                WHEN 'cocinero' THEN 'Cocina'
                ELSE NEW.Rol
            END
        )
        LIMIT 1;

        IF v_id_rol IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rol de empleado invalido: no existe en la tabla roles.';
        END IF;

        SET NEW.id_rol = v_id_rol;
    END IF;
END//
DELIMITER ;
