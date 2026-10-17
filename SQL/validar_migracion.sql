-- Validacion de solo lectura tras importar el esquema en produccion. No modifica datos.
-- Ejecutar con la base seleccionada:  mysql -h HOST -P PUERTO -u USUARIO -p BASE < SQL/validar_migracion.sql

-- 1) Deben existir 14 tablas
SELECT COUNT(*) AS tablas_esperadas_14 FROM information_schema.tables WHERE table_schema = DATABASE();

-- 2) Tablas faltantes (debe devolver 0 filas)
SELECT t.n AS tabla_faltante FROM (
  SELECT 'roles' n UNION SELECT 'categorias' UNION SELECT 'productos' UNION SELECT 'mesas'
  UNION SELECT 'mesas_historial' UNION SELECT 'reservas' UNION SELECT 'usuarios' UNION SELECT 'empleado'
  UNION SELECT 'caja' UNION SELECT 'pedidos' UNION SELECT 'detalle_pedido' UNION SELECT 'pagos'
  UNION SELECT 'movimientos_caja' UNION SELECT 'intentos_login'
) t LEFT JOIN information_schema.tables i ON i.table_schema = DATABASE() AND i.table_name = t.n
WHERE i.table_name IS NULL;

-- 3) Datos base esperados en base vacia: roles=5, categorias>=3, productos=40, mesas=6, usuarios=0 hasta crear admin
SELECT (SELECT COUNT(*) FROM roles) roles, (SELECT COUNT(*) FROM categorias) categorias,
       (SELECT COUNT(*) FROM productos) productos, (SELECT COUNT(*) FROM mesas) mesas,
       (SELECT COUNT(*) FROM usuarios) usuarios;

-- 4) Roles necesarios para el login/registro
SELECT nombre FROM roles ORDER BY id_rol;

-- 5) Juego de caracteres y zona horaria
SELECT @@character_set_database AS charset_bd, @@collation_database AS collation_bd,
       @@time_zone AS tz_servidor, NOW() AS ahora_mysql;

-- 6) Tablas que no sean InnoDB o utf8mb4 (debe devolver 0 filas)
SELECT table_name, engine, table_collation FROM information_schema.tables
WHERE table_schema = DATABASE() AND (engine <> 'InnoDB' OR table_collation NOT LIKE 'utf8mb4%');

-- 7) Claves foraneas presentes (esperado: varias; si devuelve 0, el esquema no se importo completo)
SELECT COUNT(*) AS claves_foraneas FROM information_schema.table_constraints
WHERE table_schema = DATABASE() AND constraint_type = 'FOREIGN KEY';

-- 8) Integridad: registros huerfanos (todas deben devolver 0)
SELECT COUNT(*) AS detalle_sin_pedido FROM detalle_pedido d LEFT JOIN pedidos p ON p.id_pedido = d.id_pedido WHERE p.id_pedido IS NULL;
SELECT COUNT(*) AS pagos_sin_pedido FROM pagos g LEFT JOIN pedidos p ON p.id_pedido = g.id_pedido WHERE p.id_pedido IS NULL;
SELECT COUNT(*) AS usuarios_sin_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol = u.id_rol WHERE r.id_rol IS NULL;

-- 9) Permisos del usuario actual (en Railway el usuario es root del servicio; ver DEPLOY_MIGRACION_BD.md)
SHOW GRANTS FOR CURRENT_USER();
