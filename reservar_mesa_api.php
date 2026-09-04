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

$usuarioAccion = (string) ($_SESSION['Usuario'] ?? 'sistema');
$mesaController = new App\Controllers\MesaController();
$servicio = new App\Services\ReservaService();

// Ocupar la mesa primero (operación atómica): evita que dos clientes reserven
// la misma mesa al mismo tiempo, ya que solo puede pasar a Ocupada si está Libre.
$ocupacion = $mesaController->cambiarEstado($datos['id_mesa'], 'Ocupada', $usuarioAccion);
if (!$ocupacion['ok']) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'La mesa ya está ocupada o reservada, elegí otro horario u otra mesa']);
    exit;
}

$resultado = $servicio->crear($datos);

if (!$resultado['ok']) {
    // La reserva no se pudo crear: liberar la mesa que se había ocupado
    $mesaController->cambiarEstado($datos['id_mesa'], 'Libre', $usuarioAccion);
}

http_response_code($resultado['ok'] ? 200 : 400);
echo json_encode($resultado);
