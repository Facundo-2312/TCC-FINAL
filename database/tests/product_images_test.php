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
$sources = json_decode(file_get_contents(__DIR__ . '/../../MVCsix1.0/img/catalogo/fuentes.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($sources) !== 41) {
    throw new RuntimeException('Faltan fuentes del catalogo');
}
foreach ($sources as $key => $source) {
    $file = __DIR__ . '/../../MVCsix1.0/img/catalogo/' . $source['file'];
    $info = getimagesize($file);
    if (!$info || $info[2] !== IMAGETYPE_JPEG || $info[0] < 100 || $info[1] < 100
        || hash_file('sha256', $file) !== $source['sha256']
        || empty($source['source']) || empty($source['license']) || empty($source['author'])) {
        throw new RuntimeException('Imagen o atribucion invalida: ' . $key);
    }
}

foreach ($expected as $name => $filename) {
    $path = App\Support\ProductImage::catalogPath($name);
    if ($path !== 'MVCsix1.0/img/catalogo/' . $filename) {
        throw new RuntimeException('Imagen incorrecta para ' . $name);
    }
    $info = getimagesize(__DIR__ . '/../../' . $path);
    if (!$info || $info[2] !== IMAGETYPE_JPEG) {
        throw new RuntimeException('JPEG invalido para ' . $name);
    }
}
$upload = 'prod_image_regression_' . bin2hex(random_bytes(6)) . '.jpg';
$uploadFile = __DIR__ . '/../../MVCsix1.0/files/' . $upload;
try {
    copy(__DIR__ . '/../../MVCsix1.0/img/catalogo/pizza-muzzarella.jpg', $uploadFile);
    if (App\Support\ProductImage::uploadedPath('files/' . $upload) !== 'MVCsix1.0/files/' . $upload
        || App\Support\ProductImage::uploadedPath('files/prod_inexistente.jpg') !== null
        || App\Support\ProductImage::uploadedPath('img/catalogo/pizza-muzzarella.jpg') !== null) {
        throw new RuntimeException('Fallo al resolver la imagen subida por el administrador');
    }
} finally {
    if (is_file($uploadFile)) {
        unlink($uploadFile);
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
