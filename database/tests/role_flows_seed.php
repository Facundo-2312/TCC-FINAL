<?php
if (PHP_SAPI !== 'cli' || getenv('APP_DB_NAME') !== 'ci_test') exit(1);
require __DIR__ . '/../../app_bootstrap.php';
$c = app_db_connect();
$password = getenv('FLOW_TEST_PASSWORD');
if (!$password) throw new RuntimeException('Missing temporary test password');
$hash = password_hash($password, PASSWORD_DEFAULT);
for ($role = 1; $role <= 5; $role++) {
    $username = 'flow_role_' . $role;
    $stmt = mysqli_prepare($c, 'INSERT INTO usuarios(nombre,usuario,password,id_rol) VALUES (?,?,?,?)');
    mysqli_stmt_bind_param($stmt, 'sssi', $username, $username, $hash, $role);
    mysqli_stmt_execute($stmt);
}
if (!mysqli_query($c, "INSERT INTO productos (nombre, descripcion, precio, stock) VALUES ('Producto QA Eliminacion', 'Producto sintetico', 10, 1)")) {
    throw new RuntimeException('Could not create synthetic product: ' . mysqli_error($c));
}
if (!mysqli_query($c, "INSERT INTO productos (nombre, descripcion, precio, stock) VALUES ('Producto QA Historial', 'Producto sintetico con pedidos', 10, 1)")) {
    throw new RuntimeException('Could not create historical product: ' . mysqli_error($c));
}
$historicalProductId = (int) mysqli_insert_id($c);
if (!mysqli_query($c, "INSERT INTO pedidos (estado, total) VALUES ('Cancelado', 10)")) {
    throw new RuntimeException('Could not create historical order: ' . mysqli_error($c));
}
$historicalOrderId = (int) mysqli_insert_id($c);
if (!mysqli_query($c, 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal) VALUES (' . $historicalOrderId . ', ' . $historicalProductId . ', 1, 10, 10)')) {
    throw new RuntimeException('Could not create historical order detail: ' . mysqli_error($c));
}
