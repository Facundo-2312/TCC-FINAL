<?php

namespace App\Services;

use App\Repositories\MesaRepository;

class MesaService
{
    const ESTADOS_VALIDOS = array('Libre', 'Ocupada', 'Limpieza');
    private $repositorio;

    public function __construct(MesaRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new MesaRepository();
    }

    private function conZona(array $mesas)
    {
        foreach ($mesas as &$mesa) {
            $mesa['id_mesa'] = (int) $mesa['id_mesa'];
            $mesa['numero'] = (int) $mesa['numero'];
            $mesa['estado'] = (string) $mesa['estado'];
            $mesa['zona'] = $mesa['numero'] <= 3 ? 'Salón A' : 'Salón B';
        }
        unset($mesa);
        return $mesas;
    }

    public function listarConResumen()
    {
        $mesas = $this->conZona($this->repositorio->listar());
        $stats = array('Libre' => 0, 'Ocupada' => 0, 'Limpieza' => 0);
        foreach ($mesas as $mesa) {
            if (isset($stats[$mesa['estado']])) $stats[$mesa['estado']]++;
        }
        return array('mesas' => $mesas, 'stats' => $stats);
    }

    public function cambiarEstado($idMesa, $estado, $usuario)
    {
        $idMesa = (int) $idMesa;
        $estado = trim((string) $estado);
        if ($idMesa <= 0 || !in_array($estado, self::ESTADOS_VALIDOS, true)) return array('ok' => false, 'codigo' => 'datos_invalidos');

        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            $mesa = $this->repositorio->buscarParaActualizar($idMesa);
            if (!$mesa) {
                mysqli_rollback($connection);
                return array('ok' => false, 'codigo' => 'no_encontrada');
            }

            if ($estado === 'Ocupada' && $mesa['estado'] !== 'Libre') {
                mysqli_rollback($connection);
                return array('ok' => false, 'codigo' => 'occupied');
            }

            $actualizacion = $this->repositorio->actualizarEstado($idMesa, $estado, $estado === 'Ocupada');
            if (!$actualizacion['ok']) throw new \RuntimeException();
            if ($mesa['estado'] !== $estado && !$this->repositorio->registrarHistorial($idMesa, $mesa['estado'], $estado, (string) $usuario)) throw new \RuntimeException();

            mysqli_commit($connection);
            return array('ok' => true, 'codigo' => 'actualizada');
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return array('ok' => false, 'codigo' => 'error');
        }
    }

    public function historialReciente()
    {
        return $this->repositorio->historialReciente();
    }
}