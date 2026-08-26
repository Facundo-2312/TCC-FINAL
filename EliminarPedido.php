<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '4']);

$conexion = app_db_connect();

$id = $_GET['id'];


// =====================================
// ELIMINAR DETALLE
// =====================================

$sqlDetalle = "DELETE FROM detalle_pedido
WHERE id_pedido='$id'";

mysqli_query($conexion,$sqlDetalle);


// =====================================
// ELIMINAR PEDIDO
// =====================================

$sqlPedido = "DELETE FROM pedidos
WHERE id_pedido='$id'";

mysqli_query($conexion,$sqlPedido);


header("Location: Cocina2.php");

?>