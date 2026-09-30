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
$reservas = (new App\Services\ReservaService())->listarActivas();
$reservasPorMesa = array();
foreach ($reservas as $reserva) {
    $reservasPorMesa[(int) $reserva['id_mesa']] = array(
        'nombre_cliente' => $reserva['nombre_cliente'],
        'cantidad_personas' => (int) $reserva['cantidad_personas'],
        'hora_inicio' => $reserva['hora_inicio'],
        'hora_fin' => $reserva['hora_fin']
    );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'mesas' => $vistaMesas['mesas'],
    'stats' => $vistaMesas['stats'],
    'reservas' => $reservasPorMesa,
    'updatedAt' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
