<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../../app_bootstrap.php';
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, (string) $error . PHP_EOL);
    exit(1);
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = app_db_connect();
$columns = array('roles' => 'id_rol', 'usuarios' => 'id_usuario', 'categorias' => 'id_categoria',
    'productos' => 'id_producto', 'mesas' => 'id_mesa', 'mesas_historial' => 'id_historial',
    'reservas' => 'id_reserva', 'caja' => 'id_caja', 'pedidos' => 'id_pedido',
    'detalle_pedido' => 'id_detalle', 'pagos' => 'id_pago', 'movimientos_caja' => 'id_movimiento',
    'intentos_login' => 'id');
foreach ($columns as $table => $column) {
    mysqli_query($db, "CREATE TEMPORARY TABLE `qa_auto_$table` LIKE `$table`");
    mysqli_query($db, "ALTER TABLE `qa_auto_$table` RENAME TO `$table`");
    mysqli_query($db, "ALTER TABLE `$table` MODIFY `$column` INT NOT NULL");
}
mysqli_query($db, "INSERT INTO usuarios (id_usuario,nombre,usuario,password,id_rol)
    VALUES (0,'Cero','qa_cero','no-login',1),(7,'Siete','qa_siete','no-login',1)");
$sql = file_get_contents(__DIR__ . '/../../SQL/reparar_autoincrement_infinityfree.sql');
for ($run = 1; $run <= 2; $run++) {
    mysqli_multi_query($db, $sql);
    do {
        if ($result = mysqli_store_result($db)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($db) && mysqli_next_result($db));
}
foreach ($columns as $table => $column) {
    $row = mysqli_fetch_assoc(mysqli_query($db, "SHOW COLUMNS FROM `$table` LIKE '$column'"));
    if (strpos($row['Extra'], 'auto_increment') === false) {
        throw new RuntimeException('No se restauro AUTO_INCREMENT: ' . $table);
    }
    echo 'OK: AUTO_INCREMENT restaurado en ' . $table . PHP_EOL;
}
mysqli_query($db, "INSERT INTO usuarios (nombre,usuario,password,id_rol) VALUES ('Nuevo','qa_nuevo','no-login',1)");
$id = mysqli_insert_id($db);
$zero = mysqli_fetch_row(mysqli_query($db, "SELECT usuario FROM usuarios WHERE id_usuario=0"))[0];
if ($id <= 7 || $zero !== 'qa_cero'
    || (int) mysqli_fetch_row(mysqli_query($db, 'SELECT COUNT(*) FROM usuarios'))[0] !== 3) {
    throw new RuntimeException('La reparacion cambio IDs existentes o no permite nuevas altas.');
}
echo 'OK: conserva el ID 0, conserva el ID 7 y genera un nuevo ID superior sin duplicados.' . PHP_EOL;
echo 'Pruebas completadas en tablas temporales, sin modificar datos persistentes.' . PHP_EOL;
