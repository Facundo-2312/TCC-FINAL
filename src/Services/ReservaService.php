<?php

namespace App\Services;

use App\Repositories\ReservaRepository;

class ReservaService
{
    private $repositorio;

    public function __construct(ReservaRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new ReservaRepository();
    }

    /**
     * Crear reserva con validaciones
     */
    public function crear(array $datos)
    {
        // Validaciones básicas
        if (empty($datos['nombre_cliente']) || strlen($datos['nombre_cliente']) > 100) {
            return array('ok' => false, 'error' => 'Nombre de cliente inválido');
        }

        $cantidad = (int)($datos['cantidad_personas'] ?? 0);
        if ($cantidad <= 0 || $cantidad > 50) {
            return array('ok' => false, 'error' => 'Cantidad de personas inválida (1-50)');
        }

        $horaInicio = (string)($datos['hora_inicio'] ?? '');
        $horaFin = (string)($datos['hora_fin'] ?? '');

        if (!strtotime($horaInicio) || !strtotime($horaFin)) {
            return array('ok' => false, 'error' => 'Horarios inválidos');
        }

        if (strtotime($horaFin) <= strtotime($horaInicio)) {
            return array('ok' => false, 'error' => 'La hora fin debe ser después de la hora inicio');
        }

        // Verificar que no sea en el pasado
        if (strtotime($horaInicio) < time()) {
            return array('ok' => false, 'error' => 'No se puede reservar en horarios pasados');
        }

        $idMesa = (int)($datos['id_mesa'] ?? 0);

        // Verificar disponibilidad
        if (!$this->repositorio->mesaDisponible($idMesa, $horaInicio, $horaFin)) {
            return array('ok' => false, 'error' => 'La mesa no está disponible en ese horario');
        }

        // Crear reserva
        $id = $this->repositorio->crear($datos);
        if (!$id) {
            return array('ok' => false, 'error' => 'Error al crear la reserva');
        }

        return array('ok' => true, 'id' => $id, 'mensaje' => 'Reserva creada exitosamente');
    }

    /**
     * Obtener reserva
     */
    public function obtener($idReserva)
    {
        return $this->repositorio->obtener($idReserva);
    }

    /**
     * Listar reservas activas
     */
    public function listarActivas()
    {
        return $this->repositorio->listarActivas();
    }

    /**
     * Cancelar reserva
     */
    public function cancelar($idReserva)
    {
        $reserva = $this->repositorio->obtener($idReserva);
        if (!$reserva) {
            return array('ok' => false, 'error' => 'Reserva no encontrada');
        }

        if ($reserva['estado'] !== 'Confirmada') {
            return array('ok' => false, 'error' => 'La reserva no puede ser cancelada');
        }

        if ($this->repositorio->cancelar($idReserva)) {
            return array('ok' => true, 'mensaje' => 'Reserva cancelada');
        }

        return array('ok' => false, 'error' => 'Error al cancelar reserva');
    }

    /**
     * Verificar disponibilidad de una mesa en un rango horario
     */
    public function disponible($idMesa, $horaInicio, $horaFin)
    {
        return $this->repositorio->mesaDisponible($idMesa, $horaInicio, $horaFin);
    }

    /**
     * Marcar como completadas todas las reservas activas de una mesa
     * (se usa al liberar la mesa, ya sea por pago o por retiro del cliente)
     */
    public function completarPorMesa($idMesa)
    {
        $reservas = $this->repositorio->listarPorMesa($idMesa);
        foreach ($reservas as $reserva) {
            $this->repositorio->marcarCompletada((int) $reserva['id_reserva']);
        }
    }
}
