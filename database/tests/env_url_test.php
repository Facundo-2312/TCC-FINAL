<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../src/Support/Autoload.php';

use App\Support\Database;
use App\Support\Env;
use App\Support\Url;

function checkConfig($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$path = tempnam(sys_get_temp_dir(), 'restaurante-env-');
if ($path === false) {
    throw new RuntimeException('No se pudo crear el archivo temporal.');
}

try {
    $expectedHost = 'fixture.example';
    if (function_exists('putenv')) {
        putenv('APP_DB_HOST=os.example');
        $expectedHost = 'os.example';
    }
    $_ENV['ENV_EXISTING_TEST'] = 'existing';
    $fixture = "APP_DB_HOST=fixture.example\nAPP_DB_USER=fixture_user\n"
        . "APP_DB_PASSWORD='fixture_password'\nAPP_DB_NAME=fixture_db\nAPP_DB_PORT=3307\n"
        . "APP_DB_STOCK_TRIGGERS=0\nAPP_BASE_PATH=/restaurante\n"
        . "ENV_EMPTY_TEST=\nENV_EXISTING_TEST=replaced\n";
    checkConfig(file_put_contents($path, $fixture) !== false, 'No se pudo escribir la configuracion de prueba.');
    Env::load($path);

    $config = Database::config();
    checkConfig($config['host'] === $expectedHost, 'No se respeta el host o la prioridad del entorno.');
    checkConfig($config['user'] === 'fixture_user', 'No se lee el usuario desde .env.');
    checkConfig($config['password'] === 'fixture_password', 'No se lee la contrasena desde .env.');
    checkConfig($config['database'] === 'fixture_db' && $config['port'] === 3307, 'Base o puerto incorrectos.');
    checkConfig(Env::get('APP_DB_STOCK_TRIGGERS') === '0', 'Se pierde el valor cero del manejo de stock.');
    checkConfig(Env::get('ENV_EMPTY_TEST') === '', 'Se pierde un valor vacio.');
    checkConfig(Env::get('ENV_EXISTING_TEST') === 'existing', 'Se sobreescribe una variable ya definida.');
    checkConfig(Env::get('ENV_MISSING_TEST') === false, 'Una variable inexistente no devuelve false.');

    session_start();
    $context = str_repeat('a', 64);
    $_SESSION['_active_context'] = $context;
    checkConfig(Url::to('cliente.php#reservar') === '/restaurante/cliente.php?ctx=' . $context . '#reservar',
        'El contexto debe ir antes del fragmento.');
    checkConfig(Url::to('cliente.php?mesa=2#reservar') === '/restaurante/cliente.php?mesa=2&ctx=' . $context . '#reservar',
        'No se conserva la consulta y el fragmento.');
    checkConfig(Url::to('cliente.php?ctx=' . $context . '#reservar') === '/restaurante/cliente.php?ctx=' . $context . '#reservar',
        'Se duplica el contexto.');
    checkConfig(Url::to('https://example.com/') === 'https://example.com/', 'Se modifica una URL externa.');
    checkConfig(Url::to('/Login.php') === '/Login.php', 'Se modifica una ruta absoluta.');
    session_destroy();

    echo 'OK: configuracion y enlaces con ' . (function_exists('putenv') ? 'putenv disponible' : 'putenv deshabilitado') . '.' . PHP_EOL;
} finally {
    unlink($path);
}
