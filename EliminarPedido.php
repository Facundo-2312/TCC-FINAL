<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '4']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('Cocina2.php');
}
csrf_verify_or_die('Cocina2.php');

$controller = new App\Controllers\PedidoController();
$controller->eliminarDesdeQuery($_POST['id'] ?? 0, 'Cocina2.php');