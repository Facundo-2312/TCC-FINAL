<?php
require_once __DIR__ . '/app_bootstrap.php';

$conexion = app_db_connect();

$id = $_GET['id'];

$estado = $_GET['estado'];

$sql = "UPDATE pedidos
SET estado='$estado'
WHERE id_pedido='$id'";

mysqli_query($conexion,$sql);

header("Location: InterfazObtenerPedidos.php");

?>