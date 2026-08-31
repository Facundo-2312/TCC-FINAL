<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '4']);

$controller = new App\Controllers\PedidoController();
$controller->eliminarDesdeQuery($_GET['id'] ?? 0, 'Cocina2.php');