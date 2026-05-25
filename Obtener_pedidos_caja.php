<?php
require_once __DIR__ . '/app_bootstrap.php';

$conn = app_db_connect(array(
    'password' => '1234',
    'database' => 'pro',
));
if (!$conn) {
    die('Conexión fallida: ' . mysqli_connect_error());
}

// Obtener el valor 'desde' de la URL
$desde = $_GET['desde'];

// Consulta para obtener los nuevos pedidos
$sql = "SELECT * FROM pedido where estado=3"; 

$result = $conn->query($sql);


$pedidos = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $sqlProducto = "SELECT prod.Nombre, pcp.Cantidad, prod.Precio 
        from Pedido p 
        inner join Pepc pcp
        on p.IDPedido = pcp.IDPedido
        INNER JOIN Producto prod
        on prod.IDProducto = pcp.IDProducto
        WHERE p.IDPedido =".$row['IDPedido'];

$result2 = $conn->query($sqlProducto);
$productos = array();
if ($result2->num_rows > 0) {
while ($row2 =$result2->fetch_assoc()) {
$productos[] = array('Nombre' => $row2['Nombre'], 'Cantidad' => $row2['Cantidad'], 'Precio' => $row2['Precio']);
}
}

        $pedidos[] = array(
            'id' => $row['IDPedido'],
            'descripcion' => $row['Observaciones'],
            'estado' => $row['estado'],
            'Mesa' => $row['Mesa'],
            'productos'=>$productos
        );
    }
}

$conn->close();

// Devolver los nuevos pedidos en formato JSON
header('Content-Type: application/json');
echo json_encode($pedidos);
?>


