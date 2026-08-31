<?php

namespace App\Services;

use App\Repositories\PedidoRepository;

// Business rules for pedidos: valid states and transitions, reused by every entrypoint.
class PedidoService
{
    // Matches the `pedidos.estado` ENUM in SQL/BD.sql plus the ArchivadoCocina status used by CAJA.php.
    const ESTADOS_VALIDOS = array('Pendiente', 'Preparando', 'Entregado', 'Cancelado', 'ArchivadoCocina');

    private $repositorio;

    public function __construct(PedidoRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new PedidoRepository();
    }

    public function listarConDetalle()
    {
        return $this->repositorio->todosConDetalle();
    }

    public function listarCocina()
    {
        $pedidos = $this->repositorio->listarConDetalle(array('Pendiente', 'Preparando', 'Entregado'), null, true);
        $resumen = array('Pendiente' => 0, 'Preparando' => 0, 'Entregado' => 0);
        foreach ($pedidos as $pedido) $resumen[$pedido['estado']]++;
        return array('pedidos' => $pedidos, 'resumen' => $resumen);
    }

    public function listarRecientes($limite = 30)
    {
        return $this->repositorio->listarConDetalle(null, $limite);
    }

    public function cambiarEstado($idPedido, $estado)
    {
        $idPedido = (int) $idPedido;

        if ($idPedido <= 0 || !in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }

        return $this->repositorio->actualizarEstado($idPedido, $estado);
    }

    // Same state machine previously hardcoded in InterfazActualizarPedido.php: Pendiente -> Preparando -> Entregado.
    public function avanzarEstado($idPedido)
    {
        $idPedido = (int) $idPedido;
        $pedido = $this->repositorio->buscarPorId($idPedido);

        if (!$pedido) {
            return null;
        }

        $siguiente = $pedido['estado'] === 'Pendiente' ? 'Preparando' : 'Entregado';
        $this->repositorio->actualizarEstado($idPedido, $siguiente);

        return $siguiente;
    }

    public function eliminar($idPedido)
    {
        $idPedido = (int) $idPedido;

        if ($idPedido <= 0) {
            return false;
        }

        return $this->repositorio->eliminar($idPedido);
    }

    public function ocultarDeCocina($idPedido)
    {
        return $this->cambiarEstado($idPedido, 'ArchivadoCocina');
    }

    public function crearPedidoRapido($mesa, $productoManual, $precio, $cantidad, $usuario)
    {
        $mesa = (int) $mesa;
        $precio = (float) $precio;
        $cantidad = (int) $cantidad;
        $productoManual = trim((string) $productoManual);
        if ($mesa <= 0 || $productoManual === '' || $precio <= 0 || $cantidad <= 0) return false;

        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            $idUsuario = $this->repositorio->buscarIdUsuario($usuario);
            $idProducto = $this->repositorio->buscarProductoPorNombre($productoManual);
            if (!$idProducto) $idProducto = $this->repositorio->crearProductoManual($productoManual, $precio);
            $subtotal = $precio * $cantidad;
            $idPedido = $this->repositorio->crearPedido($mesa, $idUsuario, $subtotal);
            if ($idPedido <= 0 || !$this->repositorio->ocuparMesa($mesa) || !$this->repositorio->agregarDetalle($idPedido, $idProducto, $cantidad, $precio, $subtotal)) throw new \RuntimeException();
            mysqli_commit($connection);
            return $idPedido;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return false;
        }
    }

    public function crearPedidoMozo($numeroMesa, $usuario, $observaciones, array $items)
    {
        $numeroMesa = (int) $numeroMesa;
        $usuario = trim((string) $usuario);
        $observaciones = trim((string) $observaciones);
        if ($numeroMesa <= 0 || $usuario === '' || empty($items) || count($items) > 50) return false;

        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            $idUsuario = $this->repositorio->buscarIdUsuario($usuario);
            if ($idUsuario <= 0) throw new \RuntimeException();
            $idMesa = $this->repositorio->buscarMesaPorNumeroParaActualizar($numeroMesa);
            if (!$idMesa) throw new \RuntimeException();
            $idPedido = $this->repositorio->crearPedidoMozo($idMesa, $idUsuario, substr($observaciones, 0, 500));
            if ($idPedido <= 0 || !$this->repositorio->ocuparMesa($idMesa)) throw new \RuntimeException();

            $total = 0.0;
            foreach ($items as $item) {
                $idProducto = (int) ($item['IDProducto'] ?? 0);
                $cantidad = (int) ($item['quantity'] ?? 0);
                if ($idProducto <= 0 || $cantidad <= 0 || $cantidad > 100) throw new \RuntimeException();
                $producto = $this->repositorio->buscarProductoParaPedido($idProducto);
                if (!$producto) throw new \RuntimeException();
                $precio = (float) $producto['precio'];
                $subtotal = $precio * $cantidad;
                $sin = substr(trim((string) ($item['sinIngredientes'] ?? '')), 0, 255);
                $extra = substr(trim((string) ($item['extraIngredientes'] ?? '')), 0, 255);
                if (!$this->repositorio->agregarDetallePersonalizado($idPedido, $idProducto, $cantidad, $precio, $subtotal, $sin, $extra)) throw new \RuntimeException();
                $total += $subtotal;
            }

            if (!$this->repositorio->actualizarTotal($idPedido, $total)) throw new \RuntimeException();
            mysqli_commit($connection);
            return $idPedido;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return false;
        }
    }

    public function crearPedidoLegacy($observaciones, $ci, $idMesa)
    {
        $ci = (int) $ci;
        $idMesa = (int) $idMesa;
        if ($ci <= 0 || $idMesa <= 0) return false;
        $idUsuario = $this->repositorio->buscarIdUsuarioPorCi($ci);
        if (!$idUsuario) return false;
        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            $idPedido = $this->repositorio->crearPedidoMozo($idMesa, $idUsuario, substr(trim((string) $observaciones), 0, 500));
            if ($idPedido <= 0 || !$this->repositorio->ocuparMesa($idMesa)) throw new \RuntimeException();
            mysqli_commit($connection);
            return $idPedido;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return false;
        }
    }

    public function agregarDetalleLegacy($idPedido, $idProducto, $cantidad, $sinIngredientes = '', $extraIngredientes = '')
    {
        $idPedido = (int) $idPedido;
        $idProducto = (int) $idProducto;
        $cantidad = (int) $cantidad;
        if ($idPedido <= 0 || $idProducto <= 0 || $cantidad <= 0) return false;
        $producto = $this->repositorio->buscarProductoParaPedido($idProducto);
        if (!$producto) return false;
        $precio = (float) $producto['precio'];
        $ok = $this->repositorio->agregarDetallePersonalizado($idPedido, $idProducto, $cantidad, $precio, $precio * $cantidad, substr(trim((string) $sinIngredientes), 0, 255), substr(trim((string) $extraIngredientes), 0, 255));
        return $ok && $this->repositorio->sumarTotalDetalle($idPedido);
    }
}
