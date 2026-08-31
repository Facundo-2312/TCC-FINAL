<?php

namespace App\Services;

use App\Repositories\ReporteRepository;

class ReporteService
{
    private $repositorio;

    public function __construct(ReporteRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new ReporteRepository();
    }

    public function resumenPagosHoy()
    {
        $resumen = $this->repositorio->resumenPagosHoy();
        if (!$resumen) return null;
        return array('recaudado' => (float) $resumen['recaudado'], 'propina' => (float) $resumen['propina'], 'pagos' => (int) $resumen['pagos']);
    }
}