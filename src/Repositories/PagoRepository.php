<?php

namespace App\Repositories;

use App\Support\Database;

class PagoRepository
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

    public function buscarPedidoFacturable($idPedido)
    {
        $stmt = mysqli_prepare(
            $this->connection,
            'SELECT id_pedido, id_mesa, total, estado FROM pedidos WHERE id_pedido = ? LIMIT 1 FOR UPDATE'
        );
        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pedido = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $pedido ?: null;
    }

    public function existePago($idPedido)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_pago FROM pagos WHERE id_pedido = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pago = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $pago !== null;
    }

    public function crearPago($idPedido, $metodoPago, $monto, $propina)
    {
        $stmt = mysqli_prepare(
            $this->connection,
            'INSERT INTO pagos (id_pedido, metodo_pago, monto, propina) VALUES (?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'isdd', $idPedido, $metodoPago, $monto, $propina);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    public function marcarPedidoEntregado($idPedido)
    {
        $estado = 'Entregado';
        $stmt = mysqli_prepare($this->connection, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmt, 'si', $estado, $idPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    public function liberarMesa($idMesa)
    {
        if ($idMesa <= 0) {
            return true;
        }

        $estado = 'Libre';
        $stmt = mysqli_prepare($this->connection, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
        mysqli_stmt_bind_param($stmt, 'si', $estado, $idMesa);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}