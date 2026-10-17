<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este comando solo puede ejecutarse desde la linea de comandos.' . PHP_EOL);
}

require_once __DIR__ . '/../app_bootstrap.php';

$connection = app_db_connect();
if (!$connection) {
    fwrite(STDERR, 'No se pudo conectar a la base de datos.' . PHP_EOL);
    exit(1);
}

$result = mysqli_query($connection, 'SELECT nombre, img FROM productos ORDER BY id_producto');
if (!$result) {
    fwrite(STDERR, 'No se pudieron consultar los productos: ' . mysqli_error($connection) . PHP_EOL);
    exit(1);
}

$productos = mysqli_fetch_all($result, MYSQLI_ASSOC);
$faltantes = array();
$catalogoEncontrado = 0;
foreach ($productos as $producto) {
    $imagen = trim((string) ($producto['img'] ?? ''));
    $normalizada = ltrim(str_replace('\\', '/', $imagen), '/');
    if (strpos($normalizada, 'img/catalogo/') === 0) {
        $catalogoEncontrado++;
    }
    $candidatas = array(
        __DIR__ . '/../' . $normalizada,
        __DIR__ . '/../MVCsix1.0/' . $normalizada,
        __DIR__ . '/../MVCsix1.0/img/' . basename($normalizada),
        __DIR__ . '/../MVCsix1.0/img/catalogo/' . basename($normalizada)
    );

    $encontrada = false;
    foreach ($candidatas as $candidata) {
        if ($normalizada !== '' && strpos($normalizada, '..') === false && is_file($candidata)) {
            $encontrada = true;
            break;
        }
    }

    if (!$encontrada) {
        $faltantes[] = (string) $producto['nombre'] . ' => ' . ($imagen !== '' ? $imagen : '(sin ruta)');
    }
}

echo 'Productos en la base: ' . count($productos) . PHP_EOL;
echo 'Productos del catalogo de 40: ' . $catalogoEncontrado . PHP_EOL;
echo 'Productos con imagen faltante: ' . count($faltantes) . PHP_EOL;
foreach ($faltantes as $faltante) {
    echo $faltante . PHP_EOL;
}

if ($catalogoEncontrado !== 40 || !empty($faltantes)) {
    exit(1);
}
