<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2', '3']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

csrf_verify_or_die();

$datos = array(
    'id_mesa' => (int)($_POST['id_mesa'] ?? 0),
    'nombre_cliente' => trim($_POST['nombre_cliente'] ?? ''),
    'cantidad_personas' => (int)($_POST['cantidad_personas'] ?? 0),
    'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
    'hora_fin' => trim($_POST['hora_fin'] ?? ''),
    'telefono' => trim($_POST['telefono'] ?? ''),
    'notas' => trim($_POST['notas'] ?? ''),
    'id_usuario' => $_SESSION['IdUsuario'] ?? null
);

$servicio = new App\Services\ReservaService();
$resultado = $servicio->crear($datos);

http_response_code($resultado['ok'] ? 200 : 400);
echo json_encode($resultado);
