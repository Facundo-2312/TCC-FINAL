<?php

namespace App\Controllers;

use App\Services\PagoService;

class PagoController
{
    private $servicio;

    public function __construct(PagoService $servicio = null)
    {
        $this->servicio = $servicio ?: new PagoService();
    }

    public function facturar($idPedido, $metodoPago, $propina = 0.0)
    {
        return $this->servicio->facturar($idPedido, $metodoPago, $propina);
    }
}