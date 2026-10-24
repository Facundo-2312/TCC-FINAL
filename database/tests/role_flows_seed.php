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
