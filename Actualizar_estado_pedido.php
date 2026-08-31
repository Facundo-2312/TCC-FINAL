<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Error: Método no permitido.');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !App\Support\Csrf::verify($data['_csrf'] ?? '')) {
    App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(403);
    echo json_encode('Error: Solicitud inválida.');
    exit;
}

if (!isset($data['pedidoId'], $data['metodo'])) {
    http_response_code(400);
    echo json_encode('Error: Datos incompletos o incorrectos.');
    exit;
}

$resultado = (new App\Controllers\PagoController())->facturar((int) $data['pedidoId'], (string) $data['metodo'], 0.0);
if (!$resultado['ok']) {
    http_response_code(400);
    echo json_encode('Error: No se pudo facturar el pedido.');
    exit;
}

echo json_encode(true);
