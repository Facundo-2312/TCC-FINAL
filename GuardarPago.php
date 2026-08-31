<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Método no permitido'));
    exit;
}

$pedido = json_decode(file_get_contents('php://input'), true);
if (!is_array($pedido)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Datos incompletos'));
    exit;
}

if (!App\Support\Csrf::verify($pedido['_csrf'] ?? '')) {
    App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(403);
    echo json_encode(array('error' => 'Solicitud invalida'));
    exit;
}

if (!isset($pedido['TipoPago'], $pedido['id_pedido'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Datos incompletos'));
    exit;
}

$controller = new App\Controllers\PagoController();
$resultado = $controller->facturar(
    (int) $pedido['id_pedido'],
    (string) $pedido['TipoPago'],
    isset($pedido['propina']) ? (float) $pedido['propina'] : 0.0
);

if ($resultado['ok']) {
    echo json_encode(array('success' => 'Pago registrado correctamente'));
    exit;
}

http_response_code($resultado['codigo'] === 'error' ? 500 : 400);
echo json_encode(array('error' => $resultado['codigo'] === 'error' ? 'No se pudo registrar el pago' : 'Datos inválidos'));