<?php

namespace App\Repositories;

use App\Support\Database;

// Data access for pedidos/detalle_pedido. All queries use prepared statements.
class PedidoRepository
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

    public function todosConDetalle()
    {
        return $this->listarConDetalle();
    }

    public function listarConDetalle($estados = null, $limite = null, $ordenCocina = false, $idUsuario = null)
    {
        $condiciones = array();
        if (is_array($estados) && !empty($estados)) {
            $valores = array_map(function ($estado) { return "'" . mysqli_real_escape_string($this->connection, $estado) . "'"; }, $estados);
            $condiciones[] = 'p.estado IN (' . implode(',', $valores) . ')';
        }
        if ($idUsuario !== null && (int) $idUsuario > 0) {
            $condiciones[] = 'p.id_usuario = ' . (int) $idUsuario;
        }
        $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $orden = $ordenCocina
            ? "ORDER BY FIELD(p.estado,'Pendiente','Preparando','Entregado'), p.fecha ASC"
            : 'ORDER BY p.fecha DESC';
        $sql = 'SELECT p.id_pedido, p.id_mesa, p.id_usuario, p.fecha, p.estado, p.total, dp.cantidad, dp.precio, dp.subtotal, dp.sin_ingredientes, dp.extra_ingredientes, pr.nombre AS producto_nombre
                FROM pedidos p
                LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
                LEFT JOIN productos pr ON pr.id_producto = dp.id_producto' . $where . ' ' . $orden;
        $result = mysqli_query($this->connection, $sql);
        if (!$result) return array();

        $pedidos = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $idPedido = (int) $row['id_pedido'];
            if (!isset($pedidos[$idPedido])) {
                $pedidos[$idPedido] = array(
                    'id_pedido' => $idPedido, 'id_mesa' => $row['id_mesa'], 'id_usuario' => $row['id_usuario'],
                    'fecha' => $row['fecha'], 'estado' => $row['estado'], 'total' => $row['total'], 'productos' => array()
                );
            }
            if ($row['cantidad'] !== null) {
                $pedidos[$idPedido]['productos'][] = array(
                    'cantidad' => $row['cantidad'], 'precio' => $row['precio'], 'subtotal' => $row['subtotal'], 'nombre' => $row['producto_nombre'],
                    'sin_ingredientes' => $row['sin_ingredientes'], 'extra_ingredientes' => $row['extra_ingredientes']
                );
            }
        }
        $pedidos = array_values($pedidos);
        return $limite !== null ? array_slice($pedidos, 0, max(1, (int) $limite)) : $pedidos;
    }

    public function listarDelUsuario($usuario, $limite = 10)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
        mysqli_stmt_close($stmt);
        return $fila ? $this->listarConDetalle(null, $limite, false, (int) $fila['id_usuario']) : array();
    }

    public function detalle($idPedido)
    {
        $idPedido = (int) $idPedido;

        $stmt = mysqli_prepare(
            $this->connection,
            'SELECT dp.cantidad, pr.nombre
             FROM detalle_pedido dp
             JOIN productos pr ON dp.id_producto = pr.id_producto
             WHERE dp.id_pedido = ?'
        );

        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $productos = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $productos[] = $row;
        }
        mysqli_stmt_close($stmt);

        return $productos;
    }

    public function buscarPorId($idPedido)
    {
        $idPedido = (int) $idPedido;

        $stmt = mysqli_prepare($this->connection, 'SELECT * FROM pedidos WHERE id_pedido = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pedido = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $pedido ?: null;
    }

    public function actualizarEstado($idPedido, $estado)
    {
        $idPedido = (int) $idPedido;

        $stmt = mysqli_prepare($this->connection, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmt, 'si', $estado, $idPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    public function buscarIdUsuario($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_usuario'] : 0;
    }

    public function buscarIdUsuarioPorCi($ci)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT u.id_usuario FROM usuarios u INNER JOIN empleado e ON e.Usuario = u.usuario WHERE e.CI = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $ci);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_usuario'] : null;
    }

    public function buscarMesaPorNumeroParaActualizar($numero)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_mesa FROM mesas WHERE numero = ? LIMIT 1 FOR UPDATE');
        mysqli_stmt_bind_param($stmt, 'i', $numero);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_mesa'] : null;
    }

    public function obtenerEstadoMesa($idMesa)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT estado FROM mesas WHERE id_mesa = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idMesa);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (string) $row['estado'] : null;
    }

    public function descontarStockProducto($idProducto, $cantidad)
    {
        $stmt = mysqli_prepare(
            $this->connection,
            'UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND estado = "Activo" AND stock >= ?'
        );
        mysqli_stmt_bind_param($stmt, 'iii', $cantidad, $idProducto, $cantidad);
        $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        return $ok;
    }

    private function reponerStockDePedido($idPedido)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = ? FOR UPDATE');
        mysqli_stmt_bind_param($stmt, 'i', $idPedido);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $detalles = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
        mysqli_stmt_close($stmt);

        foreach ($detalles as $detalle) {
            $idProducto = (int) $detalle['id_producto'];
            $cantidad = (int) $detalle['cantidad'];
            $update = mysqli_prepare($this->connection, 'UPDATE productos SET stock = stock + ? WHERE id_producto = ?');
            mysqli_stmt_bind_param($update, 'ii', $cantidad, $idProducto);
            $ok = mysqli_stmt_execute($update);
            mysqli_stmt_close($update);
            if (!$ok) {
                throw new \RuntimeException('No se pudo reponer el stock del pedido.');
            }
        }
    }

    public function buscarProductoParaPedido($idProducto)
    {
        $stmt = mysqli_prepare($this->connection, "SELECT id_producto, precio FROM productos WHERE id_producto = ? AND estado = 'Activo' LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $idProducto);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function buscarProductoPorNombre($nombre)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_producto FROM productos WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $nombre);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_producto'] : null;
    }

    public function crearProductoManual($nombre, $precio)
    {
        $descripcion = 'Producto agregado manualmente';
        $stock = 100;
        $estado = 'Activo';
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO productos (nombre, descripcion, precio, stock, estado) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ssdis', $nombre, $descripcion, $precio, $stock, $estado);
        mysqli_stmt_execute($stmt);
        $idProducto = (int) mysqli_insert_id($this->connection);
        mysqli_stmt_close($stmt);
        return $idProducto;
    }

    public function crearPedido($idMesa, $idUsuario, $total)
    {
        $estado = 'Pendiente';
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO pedidos (id_mesa, id_usuario, total, estado) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iids', $idMesa, $idUsuario, $total, $estado);
        mysqli_stmt_execute($stmt);
        $idPedido = (int) mysqli_insert_id($this->connection);
        mysqli_stmt_close($stmt);
        return $idPedido;
    }

    public function crearPedidoMozo($idMesa, $idUsuario, $observaciones)
    {
        $estado = 'Pendiente';
        $total = 0.0;
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO pedidos (id_mesa, id_usuario, total, observaciones, estado) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iidss', $idMesa, $idUsuario, $total, $observaciones, $estado);
        mysqli_stmt_execute($stmt);
        $idPedido = (int) mysqli_insert_id($this->connection);
        mysqli_stmt_close($stmt);
        return $idPedido;
    }

    public function ocuparMesa($idMesa)
    {
        $estado = 'Ocupada';
        $stmt = mysqli_prepare($this->connection, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
        mysqli_stmt_bind_param($stmt, 'si', $estado, $idMesa);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function agregarDetalle($idPedido, $idProducto, $cantidad, $precio, $subtotal)
    {
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiidd', $idPedido, $idProducto, $cantidad, $precio, $subtotal);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function agregarDetallePersonalizado($idPedido, $idProducto, $cantidad, $precio, $subtotal, $sinIngredientes, $extraIngredientes)
    {
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal, sin_ingredientes, extra_ingredientes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiiddss', $idPedido, $idProducto, $cantidad, $precio, $subtotal, $sinIngredientes, $extraIngredientes);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function actualizarTotal($idPedido, $total)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE pedidos SET total = ? WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmt, 'di', $total, $idPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function sumarTotalDetalle($idPedido)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE pedidos SET total = (SELECT COALESCE(SUM(subtotal), 0) FROM detalle_pedido WHERE id_pedido = ?) WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $idPedido, $idPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminar($idPedido)
    {
        $idPedido = (int) $idPedido;

        mysqli_begin_transaction($this->connection);

        try {
            // Los pedidos ya cobrados deben conservarse para que la caja mantenga el total diario.
            $stmt = mysqli_prepare($this->connection, 'SELECT id_pago FROM pagos WHERE id_pedido = ? LIMIT 1 FOR UPDATE');
            mysqli_stmt_bind_param($stmt, 'i', $idPedido);
            mysqli_stmt_execute($stmt);
            $resultadoPago = mysqli_stmt_get_result($stmt);
            $tienePago = $resultadoPago && mysqli_num_rows($resultadoPago) > 0;
            mysqli_stmt_close($stmt);

            if ($tienePago) {
                $estado = 'ArchivadoCocina';
                $stmt = mysqli_prepare($this->connection, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
                mysqli_stmt_bind_param($stmt, 'si', $estado, $idPedido);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                if (!$ok) throw new \RuntimeException('No se pudo archivar el pedido cobrado.');
                mysqli_commit($this->connection);
                return true;
            }

            if (getenv('APP_DB_STOCK_TRIGGERS') === '0') {
                $this->reponerStockDePedido($idPedido);
            }

            $stmt = mysqli_prepare($this->connection, 'DELETE FROM detalle_pedido WHERE id_pedido = ?');
            mysqli_stmt_bind_param($stmt, 'i', $idPedido);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($this->connection, 'DELETE FROM pedidos WHERE id_pedido = ?');
            mysqli_stmt_bind_param($stmt, 'i', $idPedido);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($this->connection);
            return true;
        } catch (\Throwable $e) {
            mysqli_rollback($this->connection);
            throw $e;
        }
    }
}
