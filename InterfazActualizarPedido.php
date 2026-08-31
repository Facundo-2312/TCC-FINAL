<?php

require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php', ['1', '4']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

$jsonObject = file_get_contents("php://input");
$pedido = json_decode($jsonObject, true);

$controller = new App\Controllers\PedidoController();
$controller->avanzarEstadoJson($pedido['id'] ?? 0);