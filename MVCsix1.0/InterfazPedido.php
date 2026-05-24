<?php

include "Pedido.php";
include "Pepc.php";


	//CREAMOS LA INSTANCIA DATOS O SUPERCLASE
	$Pedido= new Pedido();
				
//LISTAMOS LOS PRODUCTOS				
function read()
{	
	$P= new Pedido();
	$json=$P->read();	
	return $json;					
}


function filtrarPedidos($fechaDesde, $fechaHasta, $mozo, $mesa) {
    $P = new Pedido();
    $pedidos = json_decode($P->read());

    // Aquí aplicas los filtros según los parámetros que recibiste
    $pedidosFiltrados = [];
    foreach ($pedidos as $pedido) {
        // Convertir la fecha y hora a solo fecha
        $fechaPedido = new DateTime($pedido->Fecha);
        $pedido->Fecha = $fechaPedido->format('Y-m-d'); // Formato solo fecha

        // Comparar solo la fecha sin la hora
        if (($fechaDesde == '' || $pedido->Fecha >= $fechaDesde) &&
            ($fechaHasta == '' || $pedido->Fecha <= $fechaHasta) &&
			
            ($mozo == '' || $pedido->CI == $mozo) &&
            ($mesa == '' || $pedido->Mesa == $mesa)) {
            $pedidosFiltrados[] = $pedido;
        }
    }
    return json_encode($pedidosFiltrados);
}






function GuardarPedido($Observaciones, $Consumo,$cedula, $Mesa){
	
	$Pedido= new Pedido();				
	$Pepc = new Pepc();
	
	try {
	   
	   //PASO 1 GUARDAR EL PEDIDO
	  
		$res = $Pedido->create($Observaciones, $cedula, $Mesa);
		
			if($res){
				
				//PASO 2 RECUPERAR EL ID DEL PEDIDO

				$IDPedido = intval($Pedido->TraerID());
				
				//PASO 3 GUARDAR LOS ID Y LAS CANTIDADES
				
				foreach($Consumo as $datos){
									
					$IDProducto	= $datos['IDProducto'];
					$Cantidad =$datos['quantity'];
					
					$ResuTotal = $Pepc->create($IDPedido, $IDProducto,$Cantidad);
				}
				echo json_encode("Se guardó el pedido");
				
			}else{
				echo json_encode("No se pudieron insertar los datos");
				
			}
	   
		 
	   
		} catch (Exception $e) {
			echo json_encode('Control de la exception: ',  $e->getMessage(), "\n");
		}
	
		   
	}

	