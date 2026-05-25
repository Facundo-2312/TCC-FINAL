<?php
require_once __DIR__ . '/app_bootstrap.php';

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

$mesas = [];
$res = mysqli_query($conexion, 'SELECT id_mesa, numero, estado FROM mesas ORDER BY numero ASC');
while ($row = mysqli_fetch_assoc($res)) {
    $numero = (int) $row['numero'];
    $zona = ($numero <= 3) ? 'Salón A' : 'Salón B';

    $mesas[] = [
        'id_mesa' => (int) $row['id_mesa'],
        'numero' => $numero,
        'estado' => (string) $row['estado'],
        'zona' => $zona,
    ];
}

$stats = [
    'Libre' => 0,
    'Ocupada' => 0,
    'Limpieza' => 0,
];

foreach ($mesas as $mesa) {
    if (isset($stats[$mesa['estado']])) {
        $stats[$mesa['estado']]++;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'mesas' => $mesas,
    'stats' => $stats,
    'updatedAt' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
