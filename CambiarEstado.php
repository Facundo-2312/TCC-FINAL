<?php

$conexion = mysqli_connect("localhost","root","","ProyectoMagnus");

$id = $_GET['id'];

$estado = $_GET['estado'];

$sql = "UPDATE pedidos
SET estado='$estado'
WHERE id_pedido='$id'";

mysqli_query($conexion,$sql);

header("Location: InterfazObtenerPedidos.php");

?>