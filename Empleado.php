<?php

require_once __DIR__ . '/app_bootstrap.php';

// Compatibility facade: existing pages keep their public Empleado API during the staged migration.
class Empleado
{
    private $servicio;

    public function __construct()
    {
        $this->servicio = new App\Services\EmpleadoService();
    }

    public function connect_db()
    {
        // The repository connection is initialized by the service constructor.
    }

    public function getLastError()
    {
        return $this->servicio->ultimoError();
    }

    public function create($CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass)
    {
        return $this->servicio->crear($CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass);
    }

    public function ListarEmpleado()
    {
        return $this->servicio->listar();
    }

    public function BuscarUsuario($CI)
    {
        $empleado = $this->servicio->buscar($CI);
        if (!$empleado) {
            return null;
        }

        unset($empleado['Pass']);
        return (object) $empleado;
    }

    public function update($Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass, $CI)
    {
        return $this->servicio->actualizar($Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass, $CI);
    }

    public function delete($CI)
    {
        return $this->servicio->eliminar($CI);
    }

    public function Login($Usuario, $Pass)
    {
        return $this->servicio->autenticar($Usuario, $Pass);
    }
}
