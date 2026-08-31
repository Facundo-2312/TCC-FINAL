<?php

namespace App\Repositories;

use App\Support\Database;

class MesaRepository
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

    public function listar()
    {
        $result = mysqli_query($this->connection, 'SELECT id_mesa, numero, estado FROM mesas ORDER BY numero ASC');
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
    }

    public function buscarParaActualizar($idMesa)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_mesa, estado FROM mesas WHERE id_mesa = ? LIMIT 1 FOR UPDATE');
        mysqli_stmt_bind_param($stmt, 'i', $idMesa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mesa = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $mesa ?: null;
    }

    public function actualizarEstado($idMesa, $estado, $soloSiLibre = false)
    {
        if ($soloSiLibre) {
            $libre = 'Libre';
            $stmt = mysqli_prepare($this->connection, 'UPDATE mesas SET estado = ? WHERE id_mesa = ? AND estado = ?');
            mysqli_stmt_bind_param($stmt, 'sis', $estado, $idMesa, $libre);
        } else {
            $stmt = mysqli_prepare($this->connection, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
            mysqli_stmt_bind_param($stmt, 'si', $estado, $idMesa);
        }
        $ok = mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return array('ok' => $ok, 'affected' => $affected);
    }

    public function registrarHistorial($idMesa, $estadoAnterior, $estadoNuevo, $usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO mesas_historial (id_mesa, estado_anterior, estado_nuevo, usuario) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'isss', $idMesa, $estadoAnterior, $estadoNuevo, $usuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function historialReciente($limite = 12)
    {
        $stmt = mysqli_prepare($this->connection, "SELECT h.id_historial, h.id_mesa, m.numero, h.estado_anterior, h.estado_nuevo, h.usuario, DATE_FORMAT(h.fecha, '%Y-%m-%d %H:%i:%s') AS fecha FROM mesas_historial h INNER JOIN mesas m ON m.id_mesa = h.id_mesa ORDER BY h.fecha DESC LIMIT ?");
        mysqli_stmt_bind_param($stmt, 'i', $limite);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $historial = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);
        return $historial;
    }
}