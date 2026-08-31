<?php

require_once __DIR__ . '/../app_bootstrap.php';

app_require_login('../Login.php', ['1', '3']);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Error: Método no permitido.');
    exit;
}

$pedido = json_decode(file_get_contents('php://input'), true);
if (!is_array($pedido) || !App\Support\Csrf::verify($pedido['_csrf'] ?? '')) {
    App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(403);
    echo json_encode('Error: Solicitud inválida.');
    exit;
}

if (!isset($pedido['items'], $pedido['Mesa']) || !is_array($pedido['items'])) {
    http_response_code(400);
    echo json_encode('Error: Datos incompletos o incorrectos.');
    exit;
}

$moneda = strtoupper(trim((string) ($pedido['moneda'] ?? 'UYU')));
if ($moneda !== 'BRL') {
    $moneda = 'UYU';
}

$cotizacion = (float) ($pedido['cotizacion'] ?? 1);
if ($cotizacion <= 0) {
    $cotizacion = $moneda === 'BRL' ? 9 : 1;
}

$metadata = '[Moneda: ' . $moneda . ' | Tasa: 1 BRL = ' . rtrim(rtrim(number_format($cotizacion, 2, '.', ''), '0'), '.') . ' UYU]';
$observaciones = trim((string) ($pedido['obs'] ?? ''));
$observaciones = $observaciones === '' ? $metadata : $observaciones . ' ' . $metadata;

$idPedido = (new App\Controllers\PedidoController())->crearPedidoMozo(
    (int) $pedido['Mesa'],
    (string) $_SESSION['Usuario'],
    $observaciones,
    $pedido['items']
);

if (!$idPedido) {
    http_response_code(400);
    echo json_encode('No se pudieron insertar los datos');
    exit;
}

echo json_encode('Se guardó el pedido');
