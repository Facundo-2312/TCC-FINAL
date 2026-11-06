<?php
require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php', ['1']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('MVCsix1.0/index.php');
}

csrf_verify_or_die('MVCsix1.0/index.php');

$IDProducto = filter_input(INPUT_POST, 'ID', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
if (!$IDProducto) {
    app_set_flash('error', 'Selecciona un producto valido.');
    app_redirect('MVCsix1.0/index.php');
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
