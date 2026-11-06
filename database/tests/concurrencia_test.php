<?php

if (PHP_SAPI !== 'cli' || getenv('APP_DB_NAME') !== 'ci_test'
    || getenv('APP_DB_STOCK_TRIGGERS') !== '0') exit(1);

require_once __DIR__ . '/../../app_bootstrap.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, (string) $exception . PHP_EOL);
    exit(1);
});

function checkConcurrent($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS ' . $message . PHP_EOL;
}

$connection = app_db_connect();
mysqli_query($connection, 'SET SESSION innodb_lock_wait_timeout = 8');

if (($argv[1] ?? '') === '--worker') {
    $job = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $barrier = $argv[3];
    touch($barrier . '.ready.' . $argv[4]);
    $deadline = microtime(true) + 10;
    while (!is_file($barrier . '.go')) {
        if (microtime(true) > $deadline) throw new RuntimeException('Worker start timeout');
        usleep(10000);
        clearstatcache();
    }
    if ($job['type'] === 'reserva') {
        $result = (new App\Services\ReservaService())->crear($job['data']);
    } elseif ($job['type'] === 'pago') {
        $result = (new App\Services\PagoService())->facturar($job['id'], 'Efectivo');
    } else {
        $service = new App\Services\PedidoService();
        $id = $service->crearPedidoCliente($job['mesa'], $job['usuario'], 'Synthetic concurrency',
            array(array('IDProducto' => $job['producto'], 'quantity' => 1)), $job['id_usuario']);
        $result = array('ok' => $id !== false && $id > 0, 'id' => $id, 'error' => $service->ultimoError());
    }
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}

function concurrentPair(array $jobs)
{
    $barrier = tempnam(sys_get_temp_dir(), 'restaurant-race-');
    if ($barrier === false) throw new RuntimeException('Cannot create worker barrier');
    $workers = array();
    try {
        foreach ($jobs as $index => $job) {
            $process = proc_open(array(PHP_BINARY, __FILE__, '--worker',
                base64_encode(json_encode($job, JSON_THROW_ON_ERROR)), $barrier, (string) $index),
                array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
            if (!is_resource($process)) throw new RuntimeException('Cannot start worker');
            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $workers[] = array('process' => $process, 'pipes' => $pipes, 'out' => '', 'err' => '', 'exit' => null);
        }
        $deadline = microtime(true) + 10;
        while (!is_file($barrier . '.ready.0') || !is_file($barrier . '.ready.1')) {
            if (microtime(true) > $deadline) throw new RuntimeException('Workers did not become ready');
            usleep(10000);
            clearstatcache();
        }
        touch($barrier . '.go');
        $deadline = microtime(true) + 20;
        do {
            $running = false;
            foreach ($workers as &$worker) {
                $worker['out'] .= stream_get_contents($worker['pipes'][1]);
                $worker['err'] .= stream_get_contents($worker['pipes'][2]);
                $status = proc_get_status($worker['process']);
                if (!$status['running'] && $worker['exit'] === null) $worker['exit'] = $status['exitcode'];
                $running = $status['running'] || $running;
            }
            unset($worker);
            if (microtime(true) > $deadline) throw new RuntimeException('Concurrent operations timed out');
            usleep(10000);
        } while ($running);
        $results = array();
        foreach ($workers as $worker) {
            $worker['out'] .= stream_get_contents($worker['pipes'][1]);
            $worker['err'] .= stream_get_contents($worker['pipes'][2]);
            if ($worker['err'] !== '') throw new RuntimeException($worker['err']);
            if ($worker['exit'] !== 0) throw new RuntimeException('Worker exited unsuccessfully');
            $results[] = json_decode($worker['out'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $results;
    } finally {
        foreach ($workers as $worker) {
            if (proc_get_status($worker['process'])['running']) proc_terminate($worker['process']);
            fclose($worker['pipes'][1]);
            fclose($worker['pipes'][2]);
            proc_close($worker['process']);
        }
        foreach (array('', '.go', '.ready.0', '.ready.1') as $suffix) {
            if (is_file($barrier . $suffix)) unlink($barrier . $suffix);
        }
    }
}

$prefix = 'race_' . bin2hex(random_bytes(6));
$users = $tables = $products = array();
try {
    $role = (new App\Repositories\UsuarioRepository())->idRolPorNombre('Cliente');
    if (!$role) throw new RuntimeException('Missing customer role');
    for ($i = 0; $i < 6; $i++) {
        $name = $prefix . '_' . $i;
        $stmt = mysqli_prepare($connection,
            "INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, 'no-login', ?)");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $name, $role);
        mysqli_stmt_execute($stmt);
        $users[] = array('usuario' => $name, 'id' => mysqli_insert_id($connection));
        mysqli_stmt_close($stmt);
        mysqli_query($connection, 'INSERT INTO mesas (numero, capacidad) SELECT COALESCE(MAX(numero), 0) + 1, 4 FROM mesas');
        $id = (int) mysqli_insert_id($connection);
        $number = (int) mysqli_fetch_row(mysqli_query($connection, 'SELECT numero FROM mesas WHERE id_mesa=' . $id))[0];
        $tables[] = array('id' => $id, 'numero' => $number);
    }
    foreach (array(1, 10, 0) as $stock) {
        $stmt = mysqli_prepare($connection, 'INSERT INTO productos (nombre, precio, stock) VALUES (?, 10, ?)');
        mysqli_stmt_bind_param($stmt, 'si', $prefix, $stock);
        mysqli_stmt_execute($stmt);
        $products[] = (int) mysqli_insert_id($connection);
        mysqli_stmt_close($stmt);
    }
    $job = function ($user, $table, $product) use (&$users, &$tables, &$products) {
        return array('type' => 'pedido', 'usuario' => $users[$user]['usuario'],
            'id_usuario' => $users[$user]['id'], 'mesa' => $tables[$table]['numero'],
            'producto' => $products[$product]);
    };
    $service = new App\Services\PedidoService();
    foreach (array(array(2, 1, 'agotado'), array(0, 2, 'stock insuficiente')) as $case) {
        $id = $service->crearPedidoCliente($tables[0]['numero'], $users[0]['usuario'], 'Synthetic rejection',
            array(array('IDProducto' => $products[$case[0]], 'quantity' => $case[1])), $users[0]['id']);
        checkConcurrent($id === false && strpos($service->ultimoError(), 'Stock insuficiente') !== false,
            'Rechaza producto ' . $case[2] . ' con error explicito');
        checkConcurrent((int) mysqli_fetch_row(mysqli_query($connection,
            'SELECT COUNT(*) FROM pedidos WHERE id_usuario=' . $users[0]['id']))[0] === 0,
            'Rechazo de stock no deja pedidos parciales');
        checkConcurrent(mysqli_fetch_row(mysqli_query($connection,
            'SELECT estado FROM mesas WHERE id_mesa=' . $tables[0]['id']))[0] === 'Libre',
            'Rechazo de stock no ocupa la mesa');
    }
    $results = concurrentPair(array($job(0, 0, 0), $job(1, 1, 0)));
    checkConcurrent(count(array_filter($results, function ($r) { return $r['ok']; })) === 1,
        'Ultima unidad: un solo pedido simultaneo aceptado');
    checkConcurrent((int) mysqli_fetch_row(mysqli_query($connection,
        'SELECT stock FROM productos WHERE id_producto=' . $products[0]))[0] === 0,
        'Ultima unidad: stock final cero, nunca negativo');

    $results = concurrentPair(array($job(2, 2, 1), $job(3, 2, 1)));
    checkConcurrent(count(array_filter($results, function ($r) { return $r['ok']; })) === 1,
        'Misma mesa: solo un cliente obtiene la mesa libre');

    $results = concurrentPair(array($job(4, 3, 1), $job(4, 3, 1)));
    checkConcurrent($results[0]['ok'] && $results[1]['ok'] && $results[0]['id'] === $results[1]['id'],
        'Mismo cliente simultaneo: una cuenta con ambas adiciones');
    $order = (int) $results[0]['id'];
    checkConcurrent((int) mysqli_fetch_row(mysqli_query($connection,
        'SELECT SUM(cantidad) FROM detalle_pedido WHERE id_pedido=' . $order))[0] === 2,
        'Adiciones simultaneas conservan las dos unidades');
    checkConcurrent((float) mysqli_fetch_row(mysqli_query($connection,
        'SELECT total FROM pedidos WHERE id_pedido=' . $order))[0] === 20.0,
        'Adiciones simultaneas suman el total exacto');

    $data = array('id_mesa' => $tables[4]['id'], 'nombre_cliente' => $prefix,
        'cantidad_personas' => 2, 'hora_inicio' => date('Y-m-d H:i:s', time() + 86400),
        'hora_fin' => date('Y-m-d H:i:s', time() + 91800), 'id_usuario' => $users[5]['id']);
    $results = concurrentPair(array(array('type' => 'reserva', 'data' => $data),
        array('type' => 'reserva', 'data' => $data)));
    checkConcurrent(count(array_filter($results, function ($r) { return $r['ok']; })) === 1,
        'Reservas simultaneas: un solo horario confirmado');
    checkConcurrent((int) mysqli_fetch_row(mysqli_query($connection,
        'SELECT COUNT(*) FROM reservas WHERE id_mesa=' . $tables[4]['id']))[0] === 1,
        'Conflicto simultaneo no duplica reservas');

    foreach (array(array('Pendiente', 'Preparando'), array('Preparando', 'Pronto para entregar'),
        array('Pronto para entregar', 'Entregado')) as $transition) {
        checkConcurrent($service->cambiarEstadoCocina($order, $transition[0], $transition[1]),
            'Cocina concurrente preparada para cobro: ' . $transition[1]);
    }
    $results = concurrentPair(array(array('type' => 'pago', 'id' => $order),
        array('type' => 'pago', 'id' => $order)));
    checkConcurrent(count(array_filter($results, function ($r) { return $r['ok']; })) === 1,
        'Cobros simultaneos: solo uno confirmado');
    checkConcurrent((int) mysqli_fetch_row(mysqli_query($connection,
        'SELECT COUNT(*) FROM pagos WHERE id_pedido=' . $order))[0] === 1,
        'Cobros simultaneos dejan un unico pago');
} finally {
    foreach ($users as $user) {
        $id = (int) $user['id'];
        mysqli_query($connection, 'DELETE sc FROM solicitudes_cobro sc INNER JOIN pedidos p ON p.id_pedido=sc.id_pedido WHERE p.id_usuario=' . $id);
        mysqli_query($connection, 'DELETE FROM pedidos WHERE id_usuario=' . $id);
        mysqli_query($connection, 'DELETE FROM usuarios WHERE id_usuario=' . $id);
    }
    foreach ($tables as $table) {
        $id = (int) $table['id'];
        mysqli_query($connection, 'DELETE FROM reservas WHERE id_mesa=' . $id);
        mysqli_query($connection, 'DELETE FROM mesas_historial WHERE id_mesa=' . $id);
        mysqli_query($connection, 'DELETE FROM mesas WHERE id_mesa=' . $id);
    }
    foreach ($products as $id) mysqli_query($connection, 'DELETE FROM productos WHERE id_producto=' . (int) $id);
}
