<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '5']);
header('Content-Type: application/json; charset=utf-8');

$idMesa = (int) ($_GET['id_mesa'] ?? 0);
if ($idMesa <= 0) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Mesa invalida'));
    exit;
}

$conexion = app_db_connect();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'No se pudo conectar con la base de datos'));
    exit;
}

$stmtMesa = mysqli_prepare($conexion, 'SELECT id_mesa, numero, estado, capacidad FROM mesas WHERE id_mesa = ? LIMIT 1');
mysqli_stmt_bind_param($stmtMesa, 'i', $idMesa);
mysqli_stmt_execute($stmtMesa);
$resultadoMesa = mysqli_stmt_get_result($stmtMesa);
$mesa = $resultadoMesa ? mysqli_fetch_assoc($resultadoMesa) : null;
mysqli_stmt_close($stmtMesa);

if (!$mesa) {
    http_response_code(404);
    echo json_encode(array('ok' => false, 'error' => 'Mesa no encontrada'));
    exit;
}

$esCliente = (string) ($_SESSION['Rol'] ?? '') === '5';
if ($esCliente) {
    $cuenta = (new App\Repositories\UsuarioRepository())->buscarAcceso((string) $_SESSION['Usuario']);
    if (!$cuenta) {
        http_response_code(403);
        echo json_encode(array('ok' => false, 'error' => 'No se pudo identificar la cuenta de cliente'));
        exit;
    }
    $idUsuarioCliente = (int) $cuenta['id_usuario'];
    $stmtReserva = mysqli_prepare($conexion, 'SELECT nombre_cliente, cantidad_personas, hora_inicio, hora_fin FROM reservas WHERE id_mesa = ? AND id_usuario = ? AND estado = "Confirmada" AND hora_fin >= NOW() ORDER BY hora_inicio ASC LIMIT 1');
    mysqli_stmt_bind_param($stmtReserva, 'ii', $idMesa, $idUsuarioCliente);
} else {
    $stmtReserva = mysqli_prepare($conexion, 'SELECT nombre_cliente, cantidad_personas, hora_inicio, hora_fin FROM reservas WHERE id_mesa = ? AND estado = "Confirmada" AND hora_fin >= NOW() ORDER BY hora_inicio ASC LIMIT 1');
    mysqli_stmt_bind_param($stmtReserva, 'i', $idMesa);
}
mysqli_stmt_execute($stmtReserva);
$resultadoReserva = mysqli_stmt_get_result($stmtReserva);
$reserva = $resultadoReserva ? mysqli_fetch_assoc($resultadoReserva) : null;
mysqli_stmt_close($stmtReserva);

if ($esCliente) {
    $stmtPedidos = mysqli_prepare($conexion, 'SELECT p.id_pedido, p.estado, p.total, p.fecha, dp.cantidad, pr.nombre FROM pedidos p LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido LEFT JOIN productos pr ON pr.id_producto = dp.id_producto WHERE p.id_mesa = ? AND p.id_usuario = ? AND p.estado IN ("Pendiente", "Preparando", "Pronto para entregar", "Entregado") AND NOT EXISTS (SELECT 1 FROM pagos pg WHERE pg.id_pedido = p.id_pedido) ORDER BY p.fecha DESC, p.id_pedido DESC');
    mysqli_stmt_bind_param($stmtPedidos, 'ii', $idMesa, $idUsuarioCliente);
} else {
    $stmtPedidos = mysqli_prepare($conexion, 'SELECT p.id_pedido, p.estado, p.total, p.fecha, dp.cantidad, pr.nombre FROM pedidos p LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido LEFT JOIN productos pr ON pr.id_producto = dp.id_producto WHERE p.id_mesa = ? AND p.estado IN ("Pendiente", "Preparando", "Pronto para entregar", "Entregado") ORDER BY p.fecha DESC, p.id_pedido DESC');
    mysqli_stmt_bind_param($stmtPedidos, 'i', $idMesa);
}
mysqli_stmt_execute($stmtPedidos);
$resultadoPedidos = mysqli_stmt_get_result($stmtPedidos);
$pedidos = array();
while ($fila = mysqli_fetch_assoc($resultadoPedidos)) {
    $idPedido = (int) $fila['id_pedido'];
    if (!isset($pedidos[$idPedido])) {
        $pedidos[$idPedido] = array('id_pedido' => $idPedido, 'estado' => $fila['estado'], 'total' => $fila['total'], 'fecha' => $fila['fecha'], 'productos' => array());
    }
    if ($fila['cantidad'] !== null) {
        $pedidos[$idPedido]['productos'][] = array('cantidad' => (int) $fila['cantidad'], 'nombre' => $fila['nombre']);
    }
}
mysqli_stmt_close($stmtPedidos);

echo json_encode(array('ok' => true, 'mesa' => $mesa, 'reserva' => $reserva, 'pedidos' => array_values($pedidos)), JSON_UNESCAPED_UNICODE);
