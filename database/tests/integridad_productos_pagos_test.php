<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../../app_bootstrap.php';
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, (string) $error . PHP_EOL);
    exit(1);
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = app_db_connect();
foreach (array('mesas', 'pedidos', 'pagos', 'productos', 'detalle_pedido') as $table) {
    mysqli_query($db, "CREATE TEMPORARY TABLE `qa_integridad_$table` LIKE `$table`");
    mysqli_query($db, "ALTER TABLE `qa_integridad_$table` RENAME TO `$table`");
}
function comprobarIntegridad($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'OK: ' . $message . PHP_EOL;
}
function valorIntegridad($db, $sql)
{
    return mysqli_fetch_row(mysqli_query($db, $sql))[0];
}
mysqli_query($db, "INSERT INTO mesas (id_mesa, numero, estado) VALUES (1,1,'Ocupada')");
mysqli_query($db, "INSERT INTO pedidos (id_pedido,id_mesa,id_usuario,total,estado,fecha) VALUES
    (1,1,1,100,'Entregado',NOW()),
    (2,1,1,100,'Pendiente',NOW() - INTERVAL 2 DAY),
    (3,1,1,100,'Cancelado',NOW())");
mysqli_query($db, "INSERT INTO pagos (id_pedido,metodo_pago,monto,propina) VALUES (1,'Efectivo',100,0)");
$pagos = new App\Repositories\PagoRepository($db);
mysqli_begin_transaction($db);
$pagos->liberarMesaSiNoQuedanPedidosAbiertos(1, 1);
comprobarIntegridad(valorIntegridad($db, 'SELECT estado FROM mesas WHERE id_mesa=1') === 'Ocupada', 'Cobrar un pedido no libera una mesa con otro sin pagar, aunque tenga mas de 12 horas');
mysqli_query($db, "INSERT INTO pagos (id_pedido,metodo_pago,monto,propina) VALUES (2,'Efectivo',100,0)");
$pagos->liberarMesaSiNoQuedanPedidosAbiertos(1, 2);
comprobarIntegridad(valorIntegridad($db, 'SELECT estado FROM mesas WHERE id_mesa=1') === 'Libre', 'Cobrar el ultimo pedido libera la mesa; cancelados no la bloquean');
mysqli_commit($db);
mysqli_query($db, "INSERT INTO productos (id_producto,nombre,precio,stock) VALUES (1,'Usado',100,10),(2,'Sin pedidos',50,10)");
mysqli_query($db, 'INSERT INTO detalle_pedido (id_pedido,id_producto,cantidad,precio,subtotal) VALUES (1,1,1,100,100)');
$productos = new App\Repositories\ProductoRepository($db);
comprobarIntegridad($productos->eliminar(1) === 'desactivado', 'Producto con pedidos se desactiva sin borrar el historial');
comprobarIntegridad(valorIntegridad($db, 'SELECT estado FROM productos WHERE id_producto=1') === 'Inactivo', 'Desactivacion persiste');
comprobarIntegridad((int) valorIntegridad($db, 'SELECT COUNT(*) FROM detalle_pedido WHERE id_producto=1') === 1, 'Detalle de venta permanece intacto');
comprobarIntegridad($productos->eliminar(2) === 'eliminado', 'Producto sin pedidos puede eliminarse');
comprobarIntegridad((int) valorIntegridad($db, 'SELECT COUNT(*) FROM productos WHERE id_producto=2') === 0, 'Eliminacion persiste');
echo 'Pruebas completadas en tablas temporales, sin modificar datos persistentes.' . PHP_EOL;
