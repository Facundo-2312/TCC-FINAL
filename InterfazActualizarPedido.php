<?php

$conexion = mysqli_connect(
    "localhost",
    "root",
    "",
    "ProyectoMagnus"
);


// =============================
// VALIDAR MÉTODO POST
// =============================

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    // Obtener JSON enviado desde JS
    $jsonObject = file_get_contents("php://input");

    // Convertir JSON a array PHP
    $pedido = json_decode($jsonObject, true);


    // =============================
    // DATOS DEL PEDIDO
    // =============================

    $id = $pedido['id'];

    $estadoActual = $pedido['estado'];


    // =============================
    // CAMBIAR ESTADO
    // =============================

    if($estadoActual == "Pendiente"){

        $nuevoEstado = "Preparando";

    }
    else if($estadoActual == "Preparando"){

        $nuevoEstado = "Entregado";

    }
    else{

        $nuevoEstado = "Entregado";

    }


    // =============================
    // UPDATE MYSQL
    // =============================

    $sql = "UPDATE pedidos

    SET estado = '$nuevoEstado'

    WHERE id_pedido = $id";

    $resultado = mysqli_query($conexion,$sql);


    // =============================
    // RESPUESTA
    // =============================

    if($resultado){

        echo json_encode([
            "ok" => true,
            "nuevoEstado" => $nuevoEstado
        ]);

    }
    else{

        echo json_encode([
            "ok" => false
        ]);

    }

}
else{

    http_response_code(405);

    echo json_encode([
        "error" => "Método no permitido"
    ]);

}

?>