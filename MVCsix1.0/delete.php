<?php

session_start();

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php', ['1']);

if (!App\Support\Csrf::verify($_GET['_csrf'] ?? '')) {
    App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
    header('Location: index.php');
    exit();
}

$IDProducto = filter_input(INPUT_GET, 'ID', FILTER_VALIDATE_INT);
if (!$IDProducto) {
    header('Location: index.php');
    exit();
}

$resultado = (new App\Controllers\ProductoController())->eliminar($IDProducto);
if ($resultado === 'desactivado') {
    app_set_flash('warning', 'El producto tiene pedidos registrados: se desactivo y ya no aparece en el menu.');
} elseif ($resultado === 'eliminado') {
    app_set_flash('success', 'Producto eliminado correctamente.');
} else {
    app_set_flash('error', 'No se pudo eliminar el producto.');
}

app_redirect('MVCsix1.0/index.php');
