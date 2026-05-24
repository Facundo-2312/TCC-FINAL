<?php

session_start();

if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
    header("Location: ../Login.php");
    exit();
}

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
