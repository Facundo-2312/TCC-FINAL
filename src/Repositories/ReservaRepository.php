<?php

namespace App\Repositories;

use App\Support\Database;

class ReservaRepository
{
    private $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Database::connect();
    }

    public function connection()
    {
        return $this->connection;
    }

    /**
     * Crear una nueva reserva
     */
    public function crear(array $datos)
    {
        $stmt = mysqli_prepare($this->connection,
            'INSERT INTO reservas (id_mesa, nombre_cliente, cantidad_personas, hora_inicio, hora_fin, telefono, notas, id_usuario)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        
        $idMesa = (int)$datos['id_mesa'];
        $nombreCliente = (string)$datos['nombre_cliente'];
        $cantidadPersonas = (int)$datos['cantidad_personas'];
        $horaInicio = (string)$datos['hora_inicio'];
        $horaFin = (string)$datos['hora_fin'];
        $telefono = (string)($datos['telefono'] ?? '');
        $notas = (string)($datos['notas'] ?? '');
        $idUsuario = isset($datos['id_usuario']) ? (int)$datos['id_usuario'] : null;

        mysqli_stmt_bind_param($stmt, 'isissssi', $idMesa, $nombreCliente, $cantidadPersonas, $horaInicio, $horaFin, $telefono, $notas, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($this->connection);
        mysqli_stmt_close($stmt);

        return $ok ? $id : false;
    }

    /**
     * Obtener reserva por ID
     */
    public function obtener($idReserva)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT * FROM reservas WHERE id_reserva = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idReserva);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reserva = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $reserva ?: null;
    }

    /**
     * Listar reservas de una mesa en un rango de fecha/hora
     */
    public function listarPorMesa($idMesa, $horaInicio = null, $horaFin = null)
    {
        if ($horaInicio && $horaFin) {
            $stmt = mysqli_prepare($this->connection,
                'SELECT * FROM reservas 
                 WHERE id_mesa = ? AND estado = "Confirmada"
                 AND ((hora_inicio <= ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin >= ?))
                 ORDER BY hora_inicio ASC'
            );
            $idMesa = (int)$idMesa;
            mysqli_stmt_bind_param($stmt, 'issss', $idMesa, $horaFin, $horaInicio, $horaFin, $horaInicio);
        } else {
            $stmt = mysqli_prepare($this->connection,
                'SELECT * FROM reservas 
                 WHERE id_mesa = ? AND estado = "Confirmada"
                 ORDER BY hora_inicio ASC'
            );
            $idMesa = (int)$idMesa;
            mysqli_stmt_bind_param($stmt, 'i', $idMesa);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $reservas;
    }

    /**
     * Listar todas las reservas activas (hoy y futuro)
     */
    public function listarActivas()
    {
        $stmt = mysqli_prepare($this->connection,
            'SELECT r.*, m.numero as mesa_numero FROM reservas r
             INNER JOIN mesas m ON m.id_mesa = r.id_mesa
             WHERE r.estado = "Confirmada" AND r.hora_inicio >= NOW()
             ORDER BY r.hora_inicio ASC'
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $reservas;
    }

    /**
     * Cancelar una reserva
     */
    public function cancelar($idReserva)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE reservas SET estado = "Cancelada" WHERE id_reserva = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idReserva);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Marcar reserva como completada
     */
    public function marcarCompletada($idReserva)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE reservas SET estado = "Completada" WHERE id_reserva = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idReserva);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Verificar si una mesa está disponible en un rango horario
     */
    public function mesaDisponible($idMesa, $horaInicio, $horaFin)
    {
        $stmt = mysqli_prepare($this->connection,
            'SELECT COUNT(*) as conflictos FROM reservas
             WHERE id_mesa = ? AND estado = "Confirmada"
             AND ((hora_inicio < ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin > ?))'
        );
        $idMesa = (int)$idMesa;
        mysqli_stmt_bind_param($stmt, 'issss', $idMesa, $horaFin, $horaInicio, $horaFin, $horaInicio);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return ((int)$row['conflictos'] === 0);
    }
}
