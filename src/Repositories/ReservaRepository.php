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

    public function obtenerCapacidadMesa($idMesa)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT capacidad FROM mesas WHERE id_mesa = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idMesa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? max(1, (int) $row['capacidad']) : null;
    }

    /**
     * Crear una nueva reserva
     */
    public function crear(array $datos)
    {
        $idMesa = (int)$datos['id_mesa'];
        $nombreCliente = (string)$datos['nombre_cliente'];
        $cantidadPersonas = (int)$datos['cantidad_personas'];
        $horaInicio = (string)$datos['hora_inicio'];
        $horaFin = (string)$datos['hora_fin'];
        $telefono = (string)($datos['telefono'] ?? '');
        $notas = (string)($datos['notas'] ?? '');
        $idUsuario = isset($datos['id_usuario']) ? (int)$datos['id_usuario'] : null;

        mysqli_begin_transaction($this->connection);
        try {
            $stmt = mysqli_prepare($this->connection, 'SELECT estado, capacidad FROM mesas WHERE id_mesa = ? FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'i', $idMesa);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $mesa = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            $capacidad = $mesa ? max(1, (int)($mesa['capacidad'] ?? 4)) : 0;
            if (!$mesa || !in_array($mesa['estado'], array('Libre', 'Reservada'), true) || $cantidadPersonas > $capacidad) {
                mysqli_rollback($this->connection);
                return false;
            }

            $stmt = mysqli_prepare($this->connection,
                'SELECT COUNT(*) AS conflictos FROM reservas
                 WHERE id_mesa = ? AND estado = "Confirmada"
                 AND hora_inicio < ? AND hora_fin > ?'
            );
            mysqli_stmt_bind_param($stmt, 'iss', $idMesa, $horaFin, $horaInicio);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $conflictos = $result ? (int)mysqli_fetch_assoc($result)['conflictos'] : 1;
            mysqli_stmt_close($stmt);

            if ($conflictos > 0) {
                mysqli_rollback($this->connection);
                return false;
            }

            $stmt = mysqli_prepare($this->connection,
                'INSERT INTO reservas (id_mesa, nombre_cliente, cantidad_personas, hora_inicio, hora_fin, telefono, notas, id_usuario)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($stmt, 'isissssi', $idMesa, $nombreCliente, $cantidadPersonas, $horaInicio, $horaFin, $telefono, $notas, $idUsuario);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new \RuntimeException('No se pudo guardar la reserva.');
            }
            $id = mysqli_insert_id($this->connection);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($this->connection, 'UPDATE mesas SET estado = "Reservada" WHERE id_mesa = ?');
            mysqli_stmt_bind_param($stmt, 'i', $idMesa);
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new \RuntimeException('No se pudo actualizar el estado de la mesa.');
            }
            mysqli_stmt_close($stmt);

            if ($mesa['estado'] !== 'Reservada') {
                $estadoAnterior = (string)$mesa['estado'];
                $estadoNuevo = 'Reservada';
                $usuario = (string)($datos['usuario'] ?? 'sistema');
                $stmt = mysqli_prepare($this->connection,
                    'INSERT INTO mesas_historial (id_mesa, estado_anterior, estado_nuevo, usuario) VALUES (?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($stmt, 'isss', $idMesa, $estadoAnterior, $estadoNuevo, $usuario);
                if (!mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    throw new \RuntimeException('No se pudo guardar el historial de la mesa.');
                }
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($this->connection);
        } catch (\Throwable $exception) {
            mysqli_rollback($this->connection);
            throw $exception;
        }

        return $id;
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
             WHERE r.estado = "Confirmada" AND r.hora_fin > NOW()
             ORDER BY r.hora_inicio ASC'
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $reservas;
    }

    public function mesasParaPedidoCliente($idUsuario)
    {
        $stmt = mysqli_prepare($this->connection,
            'SELECT m.id_mesa, m.numero, m.estado, m.capacidad,
                    (
                        SELECT MIN(r.hora_inicio)
                        FROM reservas r
                        WHERE r.id_mesa = m.id_mesa AND r.estado = "Confirmada"
                          AND r.id_usuario = ?
                    ) AS reserva_inicio
             FROM mesas m
             WHERE m.estado = "Libre"
                OR (m.estado IN ("Reservada", "Ocupada") AND EXISTS (
                    SELECT 1 FROM reservas r
                    WHERE r.id_mesa = m.id_mesa
                      AND r.estado = "Confirmada"
                      AND r.id_usuario = ?
                ))
             ORDER BY m.numero ASC'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idUsuario, $idUsuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mesas = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $mesas;
    }

    public function clientePuedePedirEnMesa($numeroMesa, $idUsuario)
    {
        $stmt = mysqli_prepare($this->connection,
            'SELECT m.estado,
                    EXISTS (
                        SELECT 1 FROM reservas r
                        WHERE r.id_mesa = m.id_mesa
                          AND r.estado = "Confirmada"
                          AND r.id_usuario = ?
                    ) AS tiene_reserva
             FROM mesas m
             WHERE m.numero = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idUsuario, $numeroMesa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mesa = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $mesa && ($mesa['estado'] === 'Libre'
            || (in_array($mesa['estado'], array('Reservada', 'Ocupada'), true) && (int)$mesa['tiene_reserva'] === 1));
    }

    public function bloquearReservaActivaDelCliente($idMesa, $idUsuario)
    {
        $stmt = mysqli_prepare($this->connection,
            'SELECT id_reserva
             FROM reservas
             WHERE id_mesa = ? AND estado = "Confirmada"
               AND id_usuario = ?
             ORDER BY hora_inicio ASC
             LIMIT 1 FOR UPDATE'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idMesa, $idUsuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reserva = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $reserva !== null;
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

    public function completarEnCursoPorMesa($idMesa)
    {
        $stmt = mysqli_prepare($this->connection,
            'UPDATE reservas SET estado = "Completada"
             WHERE id_mesa = ? AND estado = "Confirmada" AND hora_inicio <= NOW() AND hora_fin > NOW()'
        );
        mysqli_stmt_bind_param($stmt, 'i', $idMesa);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function sincronizarEstadoMesa($idMesa)
    {
        $stmt = mysqli_prepare($this->connection,
            'UPDATE mesas SET estado = CASE
                WHEN estado IN ("Ocupada", "Limpieza") THEN estado
                WHEN EXISTS (
                    SELECT 1 FROM reservas
                    WHERE reservas.id_mesa = mesas.id_mesa
                      AND reservas.estado = "Confirmada"
                      AND reservas.hora_fin > NOW()
                ) THEN "Reservada"
                ELSE "Libre"
             END
             WHERE id_mesa = ?'
        );
        mysqli_stmt_bind_param($stmt, 'i', $idMesa);
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
             AND hora_inicio < ? AND hora_fin > ?'
        );
        $idMesa = (int)$idMesa;
        mysqli_stmt_bind_param($stmt, 'iss', $idMesa, $horaFin, $horaInicio);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return ((int)$row['conflictos'] === 0);
    }
}
