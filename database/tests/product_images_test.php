<?php

require_once __DIR__ . '/../../app_bootstrap.php';

set_exception_handler(function ($error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
});

$expected = array(
    'Hamburguesa' => 'hamburguesa-clasica.jpg',
    'Pizza' => 'pizza-muzzarella.jpg',
    'Coca Cola' => 'coca-cola-600.jpg',
    'Agua' => 'agua-mineral-500.jpg'
);
$fingerprints = array(
    'hamburguesa-clasica.jpg' => '72a1c9d948b2c5608a4490ae3ac246200d328f319b1b8b9f6a373a68939dbf3b',
    'pizza-muzzarella.jpg' => '35a714dc905de21c95c96683036fd877d43034e4b4f1c66c37fbf5aeb9117151',
    'coca-cola-600.jpg' => '0d21e7b289be3011d5f7df469a378c5994a31cde4d94da5384c3002e304c4a0f',
    'agua-mineral-500.jpg' => 'fb4b54df119f0c9a1bc5244bebece9e84c69aa07f54ceb84ae55563444196afe'
);

foreach ($expected as $name => $filename) {
    $path = App\Support\ProductImage::catalogPath($name);
    if ($path !== 'MVCsix1.0/img/catalogo/' . $filename) {
        throw new RuntimeException('Imagen incorrecta para ' . $name);
    }
    $info = getimagesize(__DIR__ . '/../../' . $path);
    if (!$info || $info[2] !== IMAGETYPE_JPEG) {
        throw new RuntimeException('JPEG invalido para ' . $name);
    }
    if (hash_file('sha256', __DIR__ . '/../../' . $path) !== $fingerprints[$filename]) {
        throw new RuntimeException('Se modifico la foto fija de ' . $name);
    }
}
if (App\Support\ProductImage::catalogPath('  CAFÉ   CON LECHE ') !== 'MVCsix1.0/img/catalogo/cafe-leche.jpg'
    || App\Support\ProductImage::catalogPath('Producto personalizado') !== null) {
    throw new RuntimeException('Fallo de normalizacion o producto personalizado');
}

$pages = array(
    'consulta' => 'productos_consulta.php',
    'cliente' => 'cliente.php',
    'admin' => 'MVCsix1.0/index.php'
);
$page = $argv[1] ?? '';
if ($page !== '') {
    if (!isset($pages[$page])) {
        throw new RuntimeException('Pantalla de prueba desconocida');
    }
    App\Support\Auth::startSession();
    $_SESSION['Usuario'] = 'product-image-regression';
    $_SESSION['Rol'] = '1';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    require __DIR__ . '/../../' . $pages[$page];
    $html = ob_get_clean();
    foreach ($expected as $filename) {
        if (!preg_match('~<img\b[^>]*src="[^"]*/catalogo/' . preg_quote($filename, '~') . '\?v=\d+[^"]*"~', $html)) {
            throw new RuntimeException('Falta imagen versionada en ' . $page . ': ' . $filename);
        }
    }
    session_destroy();
}
echo 'OK: imagenes de productos' . ($page !== '' ? ' (' . $page . ')' : '') . PHP_EOL;
