<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Error: Método no permitido.');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !App\Support\Csrf::verify($data['_csrf'] ?? '')) {
    App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
    http_response_code(403);
    echo json_encode('Error: Solicitud inválida.');
    exit;
}

if (!isset($data['pedidoId'], $data['metodo'])) {
    http_response_code(400);
    echo json_encode('Error: Datos incompletos o incorrectos.');
    exit;
}

$resultado = (new App\Controllers\PagoController())->facturar((int) $data['pedidoId'], (string) $data['metodo'], 0.0);
if (!$resultado['ok']) {
    http_response_code(400);
    echo json_encode('Error: No se pudo facturar el pedido.');
    exit;
}

echo json_encode(true);<?php

include $_SERVER['DOCUMENT_ROOT']."\ProyectoMagnus1.11\MVCsix 1.0\Pedido.php";

//ARMO EL CONSUMO

 // Verificar si se recibieron datos mediante el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Obtener el contenido enviado desde JavaScript
   $jsonObject = file_get_contents("php://input");
   // Decodificar el JSON y convertirlo a un array asociativo de PHP
   $pedido = json_decode($jsonObject, true);
   
   // Verificar si se recibieron los datos esperados
   if (isset($pedido['pedidoId']) && isset($pedido['metodo'])) {
       
        // LA FUNCION DE LA MAGIA
        $id= (int) $pedido['pedidoId'];
        $metodo=$pedido['metodo'];
        $total=$pedido['total'];
        $ped = new Pedido();     
        $res = $ped->Facturar($id, $metodo, $total);  
        echo $res;


   } else {
       // Si no se recibieron los datos esperados, enviar un mensaje de error
       http_response_code(400); // Código de error 400 Bad Request
       $response = "Error: Datos incompletos o incorrectos.";
       echo json_encode($response);
   }
} else {
   // Si no se recibió una solicitud POST, enviar un mensaje de error
   http_response_code(405); // Código de error 405 Method Not Allowed
   $response = "Error: Método no permitido.";
   echo json_encode($response);
}

?>


