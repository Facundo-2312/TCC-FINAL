<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '5']);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pedidos = (new App\Controllers\PedidoController())->listarDelUsuario((string) $_SESSION['Usuario'], 10);
$pedidosCliente = $pedidos;
ob_start();
require __DIR__ . '/src/Views/cliente_cuenta.php';
$cuentaHtml = ob_get_clean();

echo json_encode(
    array('ok' => true, 'pedidos' => $pedidos, 'cuenta_html' => $cuentaHtml),
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
