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

$pantallas = array(
    'mesas' => array('mesas.php', '<title>Mesas en Tiempo Real</title>'),
    'reservas' => array('centro_reservas.php', '<title>Centro de Reservas</title>'),
    'cliente' => array('cliente.php', '<title>Pedido del cliente</title>'),
    'reserva-cliente' => array('cliente.php', 'id="reservar"')
);
$pantalla = $argv[1] ?? '';
$modo = $argv[2] ?? '';
if (!isset($pantallas[$pantalla]) || !in_array($modo, array('', '--http'), true)) {
    fwrite(STDERR, 'Uso: php database/tests/principal_modulos_test.php [mesas|reservas|cliente|reserva-cliente] [--http]' . PHP_EOL);
    exit(1);
}

$connection = app_db_connect();
$result = mysqli_query($connection, 'SELECT usuario FROM usuarios WHERE id_rol = 1 ORDER BY id_usuario LIMIT 1');
$administrador = mysqli_fetch_assoc($result);
if (!$administrador) {
    throw new RuntimeException('La prueba necesita una cuenta administradora existente; no crea usuarios.');
}

ob_start();
$_SERVER['HTTP_USER_AGENT'] = 'principal-modulos-test';
app_start_session();
$_SESSION['Usuario'] = $administrador['usuario'];
$_SESSION['Rol'] = '1';
$_SERVER['REQUEST_METHOD'] = 'GET';
try {
    if ($modo === '--http') {
        $cookie = session_name() . '=' . session_id();
        session_write_close();
        $context = stream_context_create(array('http' => array(
            'header' => "Cookie: $cookie\r\nUser-Agent: principal-modulos-test\r\n",
            'follow_location' => 0,
            'ignore_errors' => true,
            'timeout' => 15
        )));
        $html = file_get_contents('http://127.0.0.1/proj/' . $pantallas[$pantalla][0], false, $context);
        if ($html === false || !preg_match('/^HTTP\/\S+ 200\b/', $http_response_header[0] ?? '')) {
            throw new RuntimeException('La pantalla no respondio HTTP 200: ' . $pantalla);
        }
    } else {
        require __DIR__ . '/../../' . $pantallas[$pantalla][0];
        $html = ob_get_contents();
    }
} finally {
    ob_end_clean();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_destroy();
}

if (http_response_code() >= 400 || strpos($html, $pantallas[$pantalla][1]) === false
    || strpos($html, '</html>') === false) {
    throw new RuntimeException('No se completo la pantalla: ' . $pantalla);
}

echo 'OK: ' . $pantalla . ($modo === '--http' ? ' por HTTP 200' : '') . ' carga con una sesion nueva y el esquema persistente.' . PHP_EOL;
