<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2', '3']);

header('Content-Type: application/json; charset=utf-8');

$idMesa = (int) ($_GET['id_mesa'] ?? 0);

if ($idMesa <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Mesa inválida']);
    exit;
}

$mesaController = new App\Controllers\MesaController();
$servicioReserva = new App\Services\ReservaService();

$vistaMesas = $mesaController->listar();
$mesaActual = null;
foreach ($vistaMesas['mesas'] as $mesa) {
    if ((int) $mesa['id_mesa'] === $idMesa) {
        $mesaActual = $mesa;
        break;
    }
}

if (!$mesaActual) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Mesa no encontrada']);
    exit;
}

// Si la mesa ya está ocupada o en limpieza, no hay turnos disponibles hoy
$mesaLibre = $mesaActual['estado'] === 'Libre';

$duracionMinutos = 90;
$horaAperturaJornada = '18:00';
$horaCierreJornada = '00:00';

$hoy = date('Y-m-d');
$inicioJornada = strtotime($hoy . ' ' . $horaAperturaJornada);
$finJornada = strtotime($hoy . ' ' . $horaCierreJornada) + 86400;
$ahora = time();

$slots = [];
for ($actual = $inicioJornada; $actual < $finJornada; $actual += 30 * 60) {
    $finTurno = $actual + $duracionMinutos * 60;
    $horaInicio = date('Y-m-d\TH:i', $actual);
    $horaFin = date('Y-m-d\TH:i', $finTurno);

    $esPasado = $actual <= $ahora;
    $disponible = $mesaLibre && !$esPasado
        && $servicioReserva->disponible($idMesa, date('Y-m-d H:i:s', $actual), date('Y-m-d H:i:s', $finTurno));

    $slots[] = [
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
        'etiqueta' => date('H:i', $actual) . ' - ' . date('H:i', $finTurno),
        'disponible' => $disponible,
    ];
}

echo json_encode([
    'ok' => true,
    'mesa_libre' => $mesaLibre,
    'slots' => $slots,
]);
