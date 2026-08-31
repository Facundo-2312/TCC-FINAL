<?php
require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php', ['1', '2', '3']);

app_start_session();

if (!isset($_SESSION['Usuario'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$conexion = app_db_connect();
if (!$conexion) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$vistaMesas = (new App\Controllers\MesaController())->listar();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'mesas' => $vistaMesas['mesas'],
    'stats' => $vistaMesas['stats'],
    'updatedAt' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
