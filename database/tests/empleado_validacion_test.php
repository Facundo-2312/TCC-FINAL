<?php

require_once __DIR__ . '/../../src/Support/Autoload.php';

class EmpleadoValidationRepositoryTest extends App\Repositories\EmpleadoRepository
{
    public function __construct()
    {
    }
}

function verificarValidacionEmpleado($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$servicio = new App\Services\EmpleadoService(new EmpleadoValidationRepositoryTest());
$comun = array(12345678, 'Juan', 'Perez', 'Av. Brasil 123', 'Caja', 'cajero123');

$resultado = $servicio->crear($comun[0], 'Juan123', $comun[2], $comun[3], $comun[4], $comun[5], 'contrasena-valida');
verificarValidacionEmpleado(!$resultado && strpos($servicio->ultimoError(), 'nombre') !== false, 'Debe rechazar numeros en el nombre.');

$resultado = $servicio->crear($comun[0], $comun[1], 'Perez2', $comun[3], $comun[4], $comun[5], 'contrasena-valida');
verificarValidacionEmpleado(!$resultado && strpos($servicio->ultimoError(), 'apellido') !== false, 'Debe rechazar numeros en el apellido.');

$resultado = $servicio->crear($comun[0], $comun[1], $comun[2], $comun[3], $comun[4], $comun[5], 'corta');
verificarValidacionEmpleado(!$resultado && strpos($servicio->ultimoError(), '8 y 72') !== false, 'Debe explicar el minimo de contrasena.');

echo "PASS validacion de nombre, apellido y longitud de contrasena\n";
