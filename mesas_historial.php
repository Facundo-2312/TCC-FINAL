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

$historial = [];
$sql = "
    SELECT h.id_historial, h.id_mesa, m.numero, h.estado_anterior, h.estado_nuevo, h.usuario,
           DATE_FORMAT(h.fecha, '%Y-%m-%d %H:%i:%s') AS fecha
    FROM mesas_historial h
    INNER JOIN mesas m ON m.id_mesa = h.id_mesa
    ORDER BY h.fecha DESC
    LIMIT 12
";
$res = mysqli_query($conexion, $sql);

while ($res && $row = mysqli_fetch_assoc($res)) {
    $historial[] = [
        'id_historial' => (int) $row['id_historial'],
        'id_mesa' => (int) $row['id_mesa'],
        'numero' => (int) $row['numero'],
        'estado_anterior' => (string) $row['estado_anterior'],
        'estado_nuevo' => (string) $row['estado_nuevo'],
        'usuario' => (string) $row['usuario'],
        'fecha' => (string) $row['fecha'],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['historial' => $historial], JSON_UNESCAPED_UNICODE);
