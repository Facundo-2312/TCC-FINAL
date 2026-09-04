<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$conexion = app_db_connect();
if (!$conexion) {
    http_response_code(500);
    exit('Error de conexión');
}

// Obtener filtros
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroFechaInicio = trim($_GET['fecha_inicio'] ?? '');
$filtroFechaFin = trim($_GET['fecha_fin'] ?? '');
$filtroMesa = (int)($_GET['id_mesa'] ?? 0);

// Construir consulta
$query = 'SELECT r.*, m.numero as mesa_numero FROM reservas r
          INNER JOIN mesas m ON m.id_mesa = r.id_mesa WHERE 1=1';

$params = array();
$tipos = '';

if (!empty($filtroEstado)) {
    $query .= ' AND r.estado = ?';
    $params[] = $filtroEstado;
    $tipos .= 's';
}

if (!empty($filtroFechaInicio)) {
    $query .= ' AND DATE(r.hora_inicio) >= ?';
    $params[] = $filtroFechaInicio;
    $tipos .= 's';
}

if (!empty($filtroFechaFin)) {
    $query .= ' AND DATE(r.hora_inicio) <= ?';
    $params[] = $filtroFechaFin;
    $tipos .= 's';
}

if ($filtroMesa > 0) {
    $query .= ' AND r.id_mesa = ?';
    $params[] = $filtroMesa;
    $tipos .= 'i';
}

$query .= ' ORDER BY r.hora_inicio DESC';

$stmt = mysqli_prepare($conexion, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reservas = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Generar CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reservas_' . date('Y-m-d_H-i-s') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8

// Encabezados
$cabeceras = array('ID', 'Cliente', 'Mesa', 'Personas', 'Entrada', 'Salida', 'Teléfono', 'Notas', 'Estado', 'Creada');
fputcsv($output, $cabeceras);

// Datos
foreach ($reservas as $r) {
    $fila = array(
        $r['id_reserva'],
        $r['nombre_cliente'],
        'Mesa ' . $r['mesa_numero'],
        $r['cantidad_personas'],
        date('d/m/Y H:i', strtotime($r['hora_inicio'])),
        date('d/m/Y H:i', strtotime($r['hora_fin'])),
        $r['telefono'] ?? '',
        $r['notas'] ?? '',
        $r['estado'],
        date('d/m/Y H:i', strtotime($r['fecha_creacion']))
    );
    fputcsv($output, $fila);
}

fclose($output);
exit;
