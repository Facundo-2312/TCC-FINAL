<?php

namespace App\Controllers;

use App\Services\EmpleadoService;

class EmpleadoController
{
    private $servicio;

    public function __construct(EmpleadoService $servicio = null)
    {
        $this->servicio = $servicio ?: new EmpleadoService();
    }

    public function listar() { return $this->servicio->listar(); }
    public function crear(array $datos) { return $this->servicio->crear($datos['CI'], $datos['Nombre'], $datos['Apellido'], $datos['Direccion'], $datos['Rol'], $datos['Usuario'], $datos['Pass']); }
    public function actualizar(array $datos) { return $this->servicio->actualizar($datos['Nombre'], $datos['Apellido'], $datos['Direccion'], $datos['Rol'], $datos['Usuario'], $datos['Pass'], $datos['CI']); }
    public function eliminar($ci) { return $this->servicio->eliminar($ci); }
    public function ultimoError() { return $this->servicio->ultimoError(); }
}