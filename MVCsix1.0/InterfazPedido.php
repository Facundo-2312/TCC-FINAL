<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_start_session();

include "Pedido.php";
include "Pepc.php";

function resolverCedulaMozo($cedulaRecibida)
{
	$cedula = (int) $cedulaRecibida;
	if ($cedula > 0) {
		return $cedula;
	}

	$usuario = trim((string) ($_SESSION['Usuario'] ?? ''));
	if ($usuario === '') {
		return 0;
	}

	$con = app_db_connect();
	if (!$con) {
		return 0;
	}

	$stmt = mysqli_prepare($con, "SELECT CI FROM empleado WHERE Usuario = ? LIMIT 1");
	if (!$stmt) {
		mysqli_close($con);
		return 0;
	}

	mysqli_stmt_bind_param($stmt, 's', $usuario);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	$row = $res ? mysqli_fetch_assoc($res) : null;
	mysqli_stmt_close($stmt);
	mysqli_close($con);

	return (int) ($row['CI'] ?? 0);
}


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






function GuardarPedido($Observaciones, $Consumo, $cedula, $Mesa, $Moneda = 'UYU', $Cotizacion = 1){
	
	$Pedido= new Pedido();				
	$Pepc = new Pepc();
	$Mesa = (int) $Mesa;
	$cedula = resolverCedulaMozo($cedula);

	if ($Mesa <= 0) {
		echo json_encode('La mesa es obligatoria y debe ser mayor a 0.');
		return;
	}

	if ($cedula <= 0) {
		echo json_encode('No se pudo identificar el mozo. Cierra sesion e inicia nuevamente.');
		return;
	}

	$Moneda = strtoupper(trim((string) $Moneda));
	if ($Moneda !== 'BRL') {
		$Moneda = 'UYU';
	}

	$Cotizacion = (float) $Cotizacion;
	if ($Cotizacion <= 0) {
		$Cotizacion = ($Moneda === 'BRL') ? 9 : 1;
	}

	$Observaciones = trim((string) $Observaciones);
	$metadataMoneda = '[Moneda: ' . $Moneda . ' | Tasa: 1 BRL = ' . rtrim(rtrim(number_format($Cotizacion, 2, '.', ''), '0'), '.') . ' UYU]';

	if ($Observaciones === '') {
		$Observaciones = $metadataMoneda;
	} else {
		$Observaciones .= ' ' . $metadataMoneda;
	}
	
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
					$sinIngredientes = trim((string) ($datos['sinIngredientes'] ?? ''));
					$extraIngredientes = trim((string) ($datos['extraIngredientes'] ?? ''));
					
					$ResuTotal = $Pepc->create($IDPedido, $IDProducto, $Cantidad, $sinIngredientes, $extraIngredientes);
				}
				echo json_encode("Se guardó el pedido");
				
			}else{
				$detalle = trim((string) $Pedido->getLastError());
				echo json_encode($detalle !== '' ? $detalle : "No se pudieron insertar los datos");
				
			}
	   
		 
	   
		} catch (Exception $e) {
			echo json_encode('Control de la exception: ',  $e->getMessage(), "\n");
		}
	
		   
	}

	