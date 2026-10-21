<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
ob_start();
require_once __DIR__ . '/../../app_bootstrap.php';
app_start_session();
$_SESSION['Usuario'] = 'prueba_cocina';
$_SESSION['Rol'] = '4';
$_SERVER['REQUEST_METHOD'] = 'GET';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, (string) $exception . PHP_EOL);
    exit(1);
});
$connection = app_db_connect();
foreach (array('mesas', 'pedidos', 'pagos', 'solicitudes_cobro', 'detalle_pedido', 'productos', 'usuarios') as $table) {
    mysqli_query($connection, "CREATE TEMPORARY TABLE `test_cocina_$table` LIKE `$table`");
    mysqli_query($connection, "ALTER TABLE `test_cocina_$table` RENAME TO `$table`");
}
mysqli_query($connection, "INSERT INTO mesas (id_mesa, numero) VALUES (1, 42)");
mysqli_query($connection, "INSERT INTO pedidos (id_pedido, id_mesa, id_usuario, estado, total, fecha, observaciones) VALUES
    (1, 1, 1, 'Pendiente', 270, NOW() - INTERVAL 30 MINUTE, '<script>alert(1)</script>'),
    (2, 1, 1, 'Preparando', 100, NOW() - INTERVAL 10 MINUTE, ''),
    (3, 1, 1, 'Entregado', 150, NOW() - INTERVAL 5 MINUTE, ''),
    (4, 1, 1, 'ArchivadoCocina', 150, NOW(), ''),
    (5, 1, 1, 'Cancelado', 120, NOW(), ''),
    (6, 1, 1, 'Pendiente', 120, NOW(), '')");
mysqli_query($connection, "INSERT INTO productos (id_producto, nombre, precio, stock) VALUES (1, 'Plato de prueba', 270, 10)");
mysqli_query($connection, "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal, sin_ingredientes, extra_ingredientes)
    VALUES (1, 1, 1, 270, 270, 'Cebolla', 'Queso')");
$mode = $argv[1] ?? '';
if (in_array($mode, array('--snapshot', '--readonly'), true)) {
    if ($mode === '--readonly') $_SESSION['Rol'] = '3';
    $_GET['actualizar'] = '1';
    require __DIR__ . '/../../Cocina2.php';
    exit;
}
if (in_array($mode, array('--post', '--stale', '--csrf', '--readonly-post'), true)) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';
    $_POST = array(
        'id_pedido' => 1, 'estado_anterior' => $mode === '--stale' ? 'Preparando' : 'Pendiente',
        'estado' => $mode === '--stale' ? 'Entregado' : 'Preparando',
        '_csrf' => $mode === '--csrf' ? 'incorrecto' : App\Support\Csrf::token()
    );
    if ($mode === '--readonly-post') $_SESSION['Rol'] = '3';
    require __DIR__ . '/../../Cocina2.php';
    exit;
}
function comprobarCocina($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
    echo 'OK: ' . $message . PHP_EOL;
}
$service = new App\Services\PedidoService(new App\Repositories\PedidoRepository($connection));
$vista = $service->listarCocina();
comprobarCocina(count($vista['pedidos']) === 4, 'Excluye cancelados y archivados');
comprobarCocina($vista['resumen'] === array('Pendiente' => 2, 'Preparando' => 1, 'Entregado' => 1), 'Contadores exactos por estado');
comprobarCocina($vista['pedidos'][0]['id_pedido'] === 1 && $vista['pedidos'][1]['id_pedido'] === 6, 'Pendientes ordenados por antiguedad');
comprobarCocina((int) $vista['pedidos'][0]['mesa_numero'] === 42, 'Muestra numero real de mesa, no su identificador');
ob_start();
require __DIR__ . '/../../Cocina2.php';
$html = ob_get_clean();
comprobarCocina(strpos($html, 'http-equiv="refresh"') === false, 'No recarga automaticamente la pagina');
comprobarCocina(strpos($html, 'Notas para cocina') !== false && strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false, 'Notas visibles y escapadas');
comprobarCocina(strpos($html, 'Sin: Cebolla') !== false && strpos($html, 'Agregar: Queso') !== false, 'Muestra personalizaciones');
comprobarCocina(strpos($html, 'Hace 30 min') !== false, 'Muestra espera desde la creacion');
comprobarCocina(strpos($html, 'Comenzar preparacion') !== false && strpos($html, 'Marcar entregado') !== false, 'Acciones explicitas sin clic accidental en tarjeta');
comprobarCocina(strpos($html, 'name="_csrf"') !== false, 'Formularios protegidos');
comprobarCocina(!$service->cambiarEstadoCocina(1, 'Pendiente', 'Entregado'), 'Impide saltos de estado');
comprobarCocina(!$service->cambiarEstadoCocina(0, 'Pendiente', 'Preparando'), 'Rechaza identificador invalido');
comprobarCocina($service->cambiarEstadoCocina(1, 'Pendiente', 'Preparando'), 'Comienza preparacion');
comprobarCocina(!$service->cambiarEstadoCocina(1, 'Pendiente', 'Preparando'), 'Clic repetido o pantalla antigua no vuelve a cambiar estado');
comprobarCocina($service->cambiarEstadoCocina(1, 'Preparando', 'Entregado'), 'Marca entregado');
comprobarCocina($service->cambiarEstadoCocina(1, 'Entregado', 'Pendiente'), 'Permite volver explicitamente a pendientes');
comprobarCocina($service->cambiarEstadoCocina(1, 'Pendiente', 'ArchivadoCocina'), 'Retira pedido sin eliminarlo');
comprobarCocina((int) mysqli_fetch_row(mysqli_query($connection, 'SELECT COUNT(*) FROM pedidos'))[0] === 6, 'Conserva todos los pedidos');
comprobarCocina((int) mysqli_fetch_row(mysqli_query($connection, 'SELECT COUNT(*) FROM pagos'))[0] === 0, 'Cambiar cocina no registra pagos');
session_destroy();
echo 'Pruebas completadas en tablas temporales.' . PHP_EOL;
ob_end_flush();
