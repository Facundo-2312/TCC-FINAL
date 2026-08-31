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

(new App\Controllers\ProductoController())->eliminar($IDProducto);

header('Location: index.php');
exit();
