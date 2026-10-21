<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
ob_start();
require_once __DIR__ . '/../../app_bootstrap.php';

app_start_session();
$_SESSION['Usuario'] = 'prueba_cobro';
$_SESSION['Rol'] = '5';
$_SERVER['REQUEST_METHOD'] = 'GET';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, (string) $exception . PHP_EOL);
    exit(1);
});
$connection = app_db_connect();

// Connection-local temporary tables isolate the test even when services commit transactions.
foreach (array('mesas', 'pedidos', 'pagos', 'solicitudes_cobro', 'detalle_pedido', 'productos', 'usuarios') as $table) {
    mysqli_query($connection, "CREATE TEMPORARY TABLE `test_cobro_$table` LIKE `$table`");
    mysqli_query($connection, "ALTER TABLE `test_cobro_$table` RENAME TO `$table`");
}
mysqli_query($connection, "INSERT INTO mesas (id_mesa, numero) VALUES (1, 1)");
mysqli_query($connection, "INSERT INTO usuarios (id_usuario, nombre, usuario, password, id_rol) VALUES (1, 'Prueba', 'prueba_cobro', 'no-login', 1)");
mysqli_query($connection, "INSERT INTO pedidos (id_pedido, id_mesa, id_usuario, estado, total) VALUES
    (1, 1, 1, 'Entregado', 270),
    (2, 1, 2, 'Entregado', 100),
    (3, 1, 1, 'Preparando', 150),
    (4, 1, 1, 'Cancelado', 150),
    (5, 1, 1, 'ArchivadoCocina', 120)");
mysqli_query($connection, "INSERT INTO productos (id_producto, nombre, precio, stock) VALUES (1, 'Producto de prueba', 270, 10)");
mysqli_query($connection, "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal) VALUES (1, 1, 1, 270, 270)");

function verificar($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'OK: ' . $message . PHP_EOL;
}
function cantidadFilas($connection, $table)
{
    return (int) mysqli_fetch_row(mysqli_query($connection, "SELECT COUNT(*) FROM `$table`"))[0];
}
function renderizar($file)
{
    ob_start();
    require __DIR__ . '/../../' . $file;
    return ob_get_clean();
}

$service = new App\Services\PagoService(new App\Repositories\PagoRepository($connection));
verificar(!$service->solicitarCobro(0, 1, 'Efectivo')['ok'], 'Rechaza identificadores invalidos');
verificar(!$service->solicitarCobro(9999, 1, 'Efectivo')['ok'], 'Rechaza pedidos inexistentes');
verificar(!$service->solicitarCobro(2, 1, 'Efectivo')['ok'], 'Rechaza pedidos de otra cuenta');
verificar(!$service->solicitarCobro(1, 1, 'Bitcoin')['ok'], 'Rechaza medios no permitidos');
verificar(!$service->solicitarCobro(3, 1, 'Efectivo')['ok'], 'Rechaza pedidos en preparacion');
verificar(!$service->solicitarCobro(4, 1, 'Efectivo')['ok'], 'Rechaza pedidos cancelados');
verificar($service->solicitarCobro(1, 1, 'Tarjeta')['ok'], 'Acepta solicitud propia entregada');
verificar(cantidadFilas($connection, 'pagos') === 0, 'Solicitar no registra un pago');
verificar($service->solicitarCobro(1, 1, 'Transferencia')['ok'], 'Permite actualizar el medio');
verificar(cantidadFilas($connection, 'solicitudes_cobro') === 1, 'Reintentar no duplica solicitudes');
$pedido = mysqli_fetch_assoc(mysqli_query($connection, 'SELECT * FROM pedidos WHERE id_pedido = 1'));
verificar($pedido['total'] === '270.00' && $pedido['estado'] === 'Entregado', 'Solicitud conserva total y estado');
verificar($service->solicitarCobro(5, 1, 'Efectivo')['ok'], 'Permite cobrar pedidos archivados de cocina');

if (($argv[1] ?? '') === '--snapshot') {
    ob_clean();
    $_SESSION['Rol'] = '1';
    $_GET['solicitudes'] = '1';
    require __DIR__ . '/../../CAJA.php';
    exit;
}

$repo = new App\Repositories\PedidoRepository($connection);
$cuenta = $repo->listarDelUsuario('prueba_cobro', 10);
verificar(count($cuenta) === 4, 'Cuenta excluye pedidos de otros usuarios');
$cuentaPorId = array_column($cuenta, null, 'id_pedido');
verificar(!$cuentaPorId[1]['pagado'] && $cuentaPorId[1]['cobro_metodo'] === 'Transferencia', 'Cuenta indica cobro solicitado sin marcar pagado');
$clienteHtml = renderizar('cliente.php');
verificar(strpos($clienteHtml, 'id="customer-account"') !== false, 'Cliente muestra Mi cuenta');
verificar(strpos($clienteHtml, 'name="solicitar_cobro"') !== false && strpos($clienteHtml, 'name="_csrf"') !== false, 'Solicitud tiene formulario protegido por CSRF');
verificar(strpos($clienteHtml, 'id="mesa-estado"') !== false, 'Se conserva el panel de mesa');
$_SESSION['Rol'] = '1';
$cajaHtml = renderizar('CAJA.php');
$_SESSION['Rol'] = '5';
verificar(strpos($cajaHtml, 'Cliente solicita cobro: Transferencia') !== false, 'Caja muestra la solicitud del cliente');
verificar(strpos($cajaHtml, 'value="Transferencia" selected') !== false, 'Caja preselecciona el medio solicitado');
verificar(strpos($cajaHtml, 'Monto a cobrar') !== false && preg_match('/class="monto-total"[^>]*value="\\$270,00" readonly/', $cajaHtml) === 1, 'Caja autocompleta el total de 270 como solo lectura');
verificar(strpos($cajaHtml, 'name="propina"') === false, 'Formulario de Caja no pide propina');
verificar(strpos($cajaHtml, 'Propina hoy') === false, 'Panel no muestra apartado de propinas');
verificar(strpos($cajaHtml, 'id="caja-pendiente">$490,00') !== false, 'Pendiente suma solo pedidos entregados sin pagar');
verificar(strpos($cajaHtml, 'id="caja-solicitudes">2') !== false, 'Cuenta solicitudes no pagadas');
verificar(strpos($cajaHtml, 'Todavia no hay pagos confirmados hoy.') !== false, 'Historial muestra estado vacio');
verificar($service->facturar(1, 'Transferencia')['ok'], 'Caja confirma pago por circuito existente');
$pago = mysqli_fetch_assoc(mysqli_query($connection, 'SELECT monto, propina FROM pagos WHERE id_pedido = 1'));
verificar($pago['monto'] === '270.00' && $pago['propina'] === '0.00', 'Cobro usa el total de la base sin cargos adicionales');
verificar(!$service->facturar(1, 'Transferencia', 10)['ok'], 'Rechaza pago duplicado');
verificar(!$service->solicitarCobro(1, 1, 'Tarjeta')['ok'], 'Rechaza solicitud posterior al pago');
$cuentaPorId = array_column($repo->listarDelUsuario('prueba_cobro', 10), null, 'id_pedido');
verificar($cuentaPorId[1]['pagado'] && $cuentaPorId[1]['pago_metodo'] === 'Transferencia', 'Cuenta muestra pago confirmado');
verificar(cantidadFilas($connection, 'pagos') === 1, 'Solo existe un pago confirmado');
$ultimosPagos = mysqli_fetch_all(mysqli_query($connection,
    'SELECT pa.*, m.numero AS mesa_numero FROM pagos pa
     LEFT JOIN pedidos p ON p.id_pedido = pa.id_pedido
     LEFT JOIN mesas m ON m.id_mesa = p.id_mesa ORDER BY pa.fecha DESC LIMIT 10'
), MYSQLI_ASSOC);
ob_start();
require __DIR__ . '/../../src/Views/caja_pagos.php';
$historialHtml = ob_get_clean();
verificar(strpos($historialHtml, 'Confirmado') !== false && strpos($historialHtml, '$270,00') !== false, 'Historial muestra pago confirmado con su monto');
$estadoCliente = json_decode(renderizar('pedidos_cliente_estado.php'), true);
verificar(is_array($estadoCliente) && $estadoCliente['ok'], 'Endpoint devuelve JSON valido');
verificar(strpos($estadoCliente['cuenta_html'], 'Pagado - Transferencia') !== false, 'Actualizacion automatica muestra la confirmacion de Caja');
session_destroy();
echo 'Pruebas completadas sin modificar datos persistentes.' . PHP_EOL;
ob_end_flush();
