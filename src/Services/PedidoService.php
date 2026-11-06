<?php

namespace App\Services;

use App\Repositories\PedidoRepository;
use App\Repositories\ReservaRepository;
use App\Support\Env;

// Business rules for pedidos: valid states and transitions, reused by every entrypoint.
class PedidoService
{
    const ESTADOS_VALIDOS = array('Pendiente', 'Preparando', 'Pronto para entregar', 'Entregado', 'Cancelado', 'ArchivadoCocina');

    private $repositorio;
    private $lastError = '';

    public function __construct(PedidoRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new PedidoRepository();
    }

    public function ultimoError()
    {
        return $this->lastError;
    }

    public function listarConDetalle()
    {
        return $this->repositorio->todosConDetalle();
    }

    public function listarCocina()
    {
        $pedidos = $this->repositorio->listarConDetalle(array('Pendiente', 'Preparando', 'Pronto para entregar', 'Entregado'), null, true);
        $resumen = array('Pendiente' => 0, 'Preparando' => 0, 'Pronto para entregar' => 0, 'Entregado' => 0);
        foreach ($pedidos as $pedido) $resumen[$pedido['estado']]++;
        return array('pedidos' => $pedidos, 'resumen' => $resumen);
    }

    public function listarRecientes($limite = 30)
    {
        return $this->repositorio->listarConDetalle(null, $limite);
    }

    public function cambiarEstadoCocina($idPedido, $estadoAnterior, $estadoNuevo)
    {
        $siguientes = array(
            'Pendiente' => 'Preparando',
            'Preparando' => 'Pronto para entregar',
            'Pronto para entregar' => 'Entregado',
            'Entregado' => 'Pendiente'
        );
        if ((int) $idPedido <= 0 || !isset($siguientes[$estadoAnterior])
            || ($estadoNuevo !== $siguientes[$estadoAnterior] && $estadoNuevo !== 'ArchivadoCocina')) {
            return false;
        }
        return $this->repositorio->cambiarEstadoCocina((int) $idPedido, $estadoAnterior, $estadoNuevo);
    }

    public function listarDelUsuario($usuario, $limite = 10)
    {
        return $this->repositorio->listarDelUsuario(trim((string) $usuario), $limite);
    }

    public function listarCuentaAbiertaDelUsuario($usuario, $limite = 10)
    {
        return $this->repositorio->listarCuentaAbiertaDelUsuario(trim((string) $usuario), $limite);
    }

    public function cambiarEstado($idPedido, $estado)
    {
        $idPedido = (int) $idPedido;

        if ($idPedido <= 0 || !in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }

        return $this->repositorio->actualizarEstado($idPedido, $estado);
    }

    // Legacy endpoint: advance one step through the kitchen workflow.
    public function avanzarEstado($idPedido)
    {
        $idPedido = (int) $idPedido;
        $pedido = $this->repositorio->buscarPorId($idPedido);

        if (!$pedido) {
            return null;
        }

        $siguientes = array(
            'Pendiente' => 'Preparando',
            'Preparando' => 'Pronto para entregar',
            'Pronto para entregar' => 'Entregado'
        );
        if (!isset($siguientes[$pedido['estado']])) return null;
        $siguiente = $siguientes[$pedido['estado']];
        if (!$this->repositorio->cambiarEstadoCocina($idPedido, $pedido['estado'], $siguiente)) return null;

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

    private function descontarStockSiCorresponde($idProducto, $cantidad)
    {
        return Env::get('APP_DB_STOCK_TRIGGERS') !== '0'
            || $this->repositorio->descontarStockProducto($idProducto, $cantidad);
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
            if ($idUsuario <= 0) throw new \RuntimeException();
            $idProducto = $this->repositorio->buscarProductoPorNombre($productoManual);
            if (!$idProducto) $idProducto = $this->repositorio->crearProductoManual($productoManual, $precio);
            $subtotal = $precio * $cantidad;
            $idPedido = $this->repositorio->crearPedido($mesa, $idUsuario, $subtotal);
            if ($idPedido <= 0 || !$this->repositorio->ocuparMesa($mesa)
                || !$this->descontarStockSiCorresponde($idProducto, $cantidad)
                || !$this->repositorio->agregarDetalle($idPedido, $idProducto, $cantidad, $precio, $subtotal)) throw new \RuntimeException();
            mysqli_commit($connection);
            return $idPedido;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return false;
        }
    }

    public function crearPedidoMozo($numeroMesa, $usuario, $observaciones, array $items)
    {
        return $this->procesarPedidoMozo($numeroMesa, $usuario, $observaciones, $items, null);
    }

    public function crearPedidoCliente($numeroMesa, $usuario, $observaciones, array $items, $idUsuarioCliente)
    {
        $autorizacionCliente = array(
            'id_usuario' => (int) $idUsuarioCliente
        );
        if ($autorizacionCliente['id_usuario'] <= 0) return false;
        return $this->procesarPedidoMozo($numeroMesa, $usuario, $observaciones, $items, $autorizacionCliente);
    }

    private function procesarPedidoMozo($numeroMesa, $usuario, $observaciones, array $items, $autorizacionCliente)
    {
        $this->lastError = '';
        $numeroMesa = (int) $numeroMesa;
        $usuario = trim((string) $usuario);
        $observaciones = trim((string) $observaciones);
        if ($numeroMesa <= 0) {
            $this->lastError = 'Selecciona un numero de mesa valido.';
            return false;
        }
        if ($usuario === '' || empty($items) || count($items) > 50) {
            $this->lastError = 'El pedido no contiene datos validos. Recarga la pagina e intenta nuevamente.';
            return false;
        }

        $connection = $this->repositorio->connection();
        // Avoid gap locks on absent accounts; the user row serializes that customer's additions.
        mysqli_query($connection, 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
        mysqli_begin_transaction($connection);
        try {
            $idUsuario = $this->repositorio->buscarIdUsuario($usuario, $autorizacionCliente !== null);
            if ($idUsuario <= 0) throw new \RuntimeException('No se pudo identificar al usuario del pedido.');
            if ($autorizacionCliente !== null && $idUsuario !== $autorizacionCliente['id_usuario']) {
                throw new \RuntimeException('La cuenta no coincide con la sesion actual.');
            }
            $cuentaAbierta = null;
            if ($autorizacionCliente !== null) {
                $cuentaAbierta = $this->repositorio->buscarCuentaAbiertaCliente($autorizacionCliente['id_usuario']);
                if ($cuentaAbierta && $cuentaAbierta['solicitud_cobro'] !== null) {
                    throw new \RuntimeException('Ya solicitaste el cobro de esta cuenta. Espera a Caja antes de agregar productos.');
                }
            }
            $idMesa = $this->repositorio->buscarMesaPorNumeroParaActualizar($numeroMesa);
            if (!$idMesa) throw new \RuntimeException('La mesa ' . $numeroMesa . ' no existe. Selecciona una mesa valida.');
            if ($cuentaAbierta && (int) $cuentaAbierta['id_mesa'] !== $idMesa) {
                throw new \RuntimeException('Termina el pago de tu cuenta abierta antes de cambiar de mesa.');
            }
            if ($autorizacionCliente !== null) {
                $estadoMesa = $this->repositorio->obtenerEstadoMesa($idMesa);
                if ($estadoMesa !== 'Libre') {
                    if ($estadoMesa === 'Ocupada' && $cuentaAbierta && (int) $cuentaAbierta['id_mesa'] === $idMesa) {
                        // The customer's own open account keeps the table occupied.
                    } elseif (!in_array($estadoMesa, array('Reservada', 'Ocupada'), true)) {
                        throw new \RuntimeException('La mesa ' . $numeroMesa . ' no esta disponible para pedidos.');
                    } else {
                        $reservas = new ReservaRepository($connection);
                        if (!$reservas->bloquearReservaActivaDelCliente(
                            $idMesa,
                            $autorizacionCliente['id_usuario']
                        )) {
                            throw new \RuntimeException('La mesa ' . $numeroMesa . ' esta reservada para otra cuenta.');
                        }
                    }
                }
            }
            if ($autorizacionCliente !== null && $cuentaAbierta) {
                $idPedido = (int) $cuentaAbierta['id_pedido'];
                if (!$this->repositorio->prepararPedidoParaAdicion($idPedido)
                    || !$this->repositorio->anexarObservaciones($idPedido, substr($observaciones, 0, 500))) {
                    throw new \RuntimeException('No se pudo actualizar la cuenta abierta.');
                }
            } else {
                $idPedido = $this->repositorio->crearPedidoMozo($idMesa, $idUsuario, substr($observaciones, 0, 500));
                if ($idPedido <= 0 || !$this->repositorio->ocuparMesa($idMesa)) {
                    throw new \RuntimeException('No se pudo asignar el pedido a la mesa ' . $numeroMesa . '.');
                }
            }

            foreach ($items as $item) {
                $idProducto = (int) ($item['IDProducto'] ?? 0);
                $cantidad = (int) ($item['quantity'] ?? 0);
                if ($idProducto <= 0 || $cantidad <= 0 || $cantidad > 100) {
                    throw new \RuntimeException('Hay un producto con una cantidad invalida. Revisa el pedido.');
                }
                $producto = $this->repositorio->buscarProductoParaPedido($idProducto);
                if (!$producto) {
                    throw new \RuntimeException('Uno de los productos ya no esta disponible. Actualiza el menu e intenta nuevamente.');
                }
                if ((int) $producto['stock'] < $cantidad) {
                    throw new \RuntimeException('Stock insuficiente para ' . $producto['nombre'] . '. Disponible: ' . (int) $producto['stock'] . '.');
                }
                $precio = (float) $producto['precio'];
                $subtotal = $precio * $cantidad;
                $sin = substr(trim((string) ($item['sinIngredientes'] ?? '')), 0, 255);
                $extra = substr(trim((string) ($item['extraIngredientes'] ?? '')), 0, 255);
                if (!$this->descontarStockSiCorresponde($idProducto, $cantidad)
                    || !$this->repositorio->agregarDetallePersonalizado($idPedido, $idProducto, $cantidad, $precio, $subtotal, $sin, $extra)) {
                    throw new \RuntimeException('No se pudo reservar el stock de ' . $producto['nombre'] . '. Vuelve a revisar su disponibilidad.');
                }
            }

            if (!$this->repositorio->sumarTotalDetalle($idPedido)) {
                throw new \RuntimeException('No se pudo calcular el total del pedido.');
            }
            mysqli_commit($connection);
            return $idPedido;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            $this->lastError = $exception instanceof \mysqli_sql_exception ? '' : $exception->getMessage();
            if ($this->lastError === '') {
                $this->lastError = 'No se pudo registrar el pedido. Intenta nuevamente.';
                \App\Support\ErrorHandler::log($exception);
            }
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
        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            if (!$this->descontarStockSiCorresponde($idProducto, $cantidad)
                || !$this->repositorio->agregarDetallePersonalizado($idPedido, $idProducto, $cantidad, $precio, $precio * $cantidad, substr(trim((string) $sinIngredientes), 0, 255), substr(trim((string) $extraIngredientes), 0, 255))
                || !$this->repositorio->sumarTotalDetalle($idPedido)) {
                throw new \RuntimeException('No se pudo agregar el detalle del pedido.');
            }
            mysqli_commit($connection);
            return true;
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return false;
        }
    }
}
