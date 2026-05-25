<?php
require_once __DIR__ . '/app_bootstrap.php';

$conexion = app_db_connect();

$sql = "SELECT *
FROM pedidos
ORDER BY fecha DESC";

$resultado = mysqli_query($conexion,$sql);

$pedidos = [];

while($pedido = mysqli_fetch_assoc($resultado)){

    $idPedido = $pedido['id_pedido'];

    $sqlProductos = "SELECT dp.cantidad,
    pr.nombre

    FROM detalle_pedido dp

    JOIN productos pr
    ON dp.id_producto = pr.id_producto

    WHERE dp.id_pedido = $idPedido";

    $resProductos = mysqli_query($conexion,$sqlProductos);

    $productos = [];

    while($prod = mysqli_fetch_assoc($resProductos)){

        $productos[] = $prod;

    }

    $pedido['productos'] = $productos;

    $pedidos[] = $pedido;
}

header('Content-Type: application/json');

echo json_encode($pedidos);

?>