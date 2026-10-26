<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '4']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('InterfazObtenerPedidos.php');
}
csrf_verify_or_die('InterfazObtenerPedidos.php');

$controller = new App\Controllers\PedidoController();
$controller->cambiarEstadoDesdeQuery($_POST['id'] ?? 0, $_POST['estado'] ?? '', 'InterfazObtenerPedidos.php');