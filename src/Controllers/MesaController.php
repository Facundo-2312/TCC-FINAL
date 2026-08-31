<?php

namespace App\Controllers;

use App\Services\MesaService;

class MesaController
{
    private $servicio;

    public function __construct(MesaService $servicio = null)
    {
        $this->servicio = $servicio ?: new MesaService();
    }

    public function listar() { return $this->servicio->listarConResumen(); }
    public function cambiarEstado($idMesa, $estado, $usuario) { return $this->servicio->cambiarEstado($idMesa, $estado, $usuario); }
    public function historial() { return $this->servicio->historialReciente(); }
}