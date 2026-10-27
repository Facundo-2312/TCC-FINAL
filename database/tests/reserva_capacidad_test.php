<?php

require_once __DIR__ . '/../../src/Support/Autoload.php';

class ReservaCapacidadRepositoryTest extends App\Repositories\ReservaRepository
{
    public function __construct()
    {
    }

    public function obtenerCapacidadMesa($idMesa)
    {
        return $idMesa === 1 ? 4 : null;
    }

    public function mesaDisponible($idMesa, $horaInicio, $horaFin)
    {
        return true;
    }

    public function crear(array $datos)
    {
        return 12;
    }
}

function verificarCapacidadReserva($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$servicio = new App\Services\ReservaService(new ReservaCapacidadRepositoryTest());
$inicio = date('Y-m-d H:i:s', time() + 3600);
$fin = date('Y-m-d H:i:s', time() + 9000);
$datos = array(
    'id_mesa' => 1,
    'nombre_cliente' => 'Cliente de prueba',
    'cantidad_personas' => 5,
    'hora_inicio' => $inicio,
    'hora_fin' => $fin
);
$resultado = $servicio->crear($datos);
verificarCapacidadReserva(!$resultado['ok'], 'No debe permitir mas personas que la capacidad.');
verificarCapacidadReserva(strpos($resultado['error'], 'hasta 4 personas') !== false, 'Debe informar la capacidad maxima.');

$datos['cantidad_personas'] = 4;
$resultado = $servicio->crear($datos);
verificarCapacidadReserva($resultado['ok'], 'Debe aceptar una reserva dentro de la capacidad.');

echo "PASS la reserva informa y respeta la capacidad de mesa\n";
