<?php

session_start();

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php', ['1']);

require_once "Producto.php";

$IDProducto = filter_input(INPUT_GET, 'ID', FILTER_VALIDATE_INT);
if (!$IDProducto) {
    header('Location: index.php');
    exit();
}

$producto = new Producto();
$producto->delete($IDProducto);

header('Location: index.php');
exit();
