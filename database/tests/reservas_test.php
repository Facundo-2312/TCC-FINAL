<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../app_bootstrap.php';
set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, (string) $exception . PHP_EOL);
    exit(1);
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$connection = app_db_connect();

// Temporary tables survive service commits without touching real reservations.
foreach (array('mesas', 'mesas_historial', 'reservas') as $table) {
    mysqli_query($connection, "CREATE TEMPORARY TABLE `test_reservas_$table` LIKE `$table`");
    mysqli_query($connection, "ALTER TABLE `test_reservas_$table` RENAME TO `$table`");
}
mysqli_query($connection, 'INSERT INTO mesas (id_mesa, numero, capacidad) VALUES (1, 1, 4)');

function verificarReserva($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'OK: ' . $message . PHP_EOL;
}

$repo = new App\Repositories\ReservaRepository($connection);
$mesas = new App\Repositories\MesaRepository($connection);
$service = new App\Services\ReservaService($repo);
$datos = array(
    'id_mesa' => 1,
    'nombre_cliente' => 'Prueba aislada',
    'cantidad_personas' => 2,
    'hora_inicio' => date('Y-m-d H:i:s', time() + 3600),
    'hora_fin' => date('Y-m-d H:i:s', time() + 9000),
    'id_usuario' => 42,
    'usuario' => 'prueba_reservas'
);
$exceso = $datos;
$exceso['cantidad_personas'] = 5;
verificarReserva(!$service->crear($exceso)['ok'], 'Rechaza reservas que exceden la capacidad');
verificarReserva(count($repo->listarActivas()) === 0, 'Rechazo no guarda una reserva');
$creada = $service->crear($datos);
verificarReserva($creada['ok'], 'Guarda una reserva con el esquema actualizado');
$id = $creada['id'];
verificarReserva($repo->obtener($id)['nombre_cliente'] === $datos['nombre_cliente'], 'Recupera la reserva guardada');
verificarReserva($mesas->listar()[0]['estado'] === 'Reservada', 'El estado Reservada se guarda sin truncarse');
verificarReserva(count($mesas->historialReciente()) === 1, 'Guarda el historial de la mesa');
verificarReserva(count($repo->listarActivas()) === 1, 'Centro de reservas encuentra la reserva activa');
verificarReserva(!$service->crear($datos)['ok'], 'Rechaza horarios superpuestos');
verificarReserva(count($repo->mesasParaPedidoCliente(42)) === 1, 'Cliente ve su mesa reservada para pedir');
verificarReserva(count($repo->mesasParaPedidoCliente(43)) === 0, 'Otro cliente no puede pedir en la mesa reservada');
verificarReserva($service->cancelar($id)['ok'], 'Cancela la reserva');
$repo->sincronizarEstadoMesa(1);
verificarReserva($mesas->listar()[0]['estado'] === 'Libre', 'Mesa sin reservas vuelve a Libre al sincronizar');
verificarReserva(count($repo->listarActivas()) === 0, 'Reserva cancelada no figura como activa');

$creada = $service->crear($datos);
verificarReserva($creada['ok'], 'Permite reutilizar el horario cancelado');
mysqli_query($connection, 'UPDATE reservas SET hora_inicio = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE estado = "Confirmada"');
$service->completarPorMesa(1);
verificarReserva($repo->obtener($creada['id'])['estado'] === 'Completada', 'Liberar mesa completa su reserva en curso');
verificarReserva($mesas->listar()[0]['estado'] === 'Libre', 'Mesa queda libre despues de completar');
echo 'Pruebas completadas en tablas temporales, sin modificar datos persistentes.' . PHP_EOL;
