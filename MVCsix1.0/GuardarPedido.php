<?php

include "InterfazPedido.php";

//ARMO EL CONSUMO

 // Verificar si se recibieron datos mediante el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Obtener el contenido enviado desde JavaScript
   $jsonObject = file_get_contents("php://input");
   // Decodificar el JSON y convertirlo a un array asociativo de PHP
   $pedido = json_decode($jsonObject, true);
   
   // Verificar si se recibieron los datos esperados
   if (isset($pedido['items']) && isset($pedido['obs'])) {
       // Acceder a los datos recibidos
       $Consumo = $pedido['items'];
       $Observaciones = $pedido['obs'];
       $cedula = $pedido['CI'];
       $Mesa = $pedido['Mesa']; 
      
      

       // LA FUNCION DE LA MAGIA
      GuardarPedido($Observaciones, $Consumo, $cedula, $Mesa);

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


