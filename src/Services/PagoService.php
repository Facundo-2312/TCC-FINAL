<?php

namespace App\Services;

use App\Repositories\PagoRepository;

class PagoService
{
    const METODOS_PERMITIDOS = array('Efectivo', 'Tarjeta', 'Transferencia');

    private $repositorio;

    public function __construct(PagoRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new PagoRepository();
    }

    public function facturar($idPedido, $metodoPago, $propina = 0.0)
    {
        $idPedido = (int) $idPedido;
        $metodoPago = trim((string) $metodoPago);
        $propina = max(0.0, (float) $propina);

        if ($idPedido <= 0 || !in_array($metodoPago, self::METODOS_PERMITIDOS, true)) {
            return array('ok' => false, 'codigo' => 'datos_invalidos');
        }

        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);

        try {
            $pedido = $this->repositorio->buscarPedidoFacturable($idPedido);
            if (!$pedido) {
                mysqli_rollback($connection);
                return array('ok' => false, 'codigo' => 'pedido_no_encontrado');
            }

            if ($this->repositorio->existePago($idPedido)) {
                mysqli_rollback($connection);
                return array('ok' => false, 'codigo' => 'pago_duplicado');
            }

            // The order total from the database is the only authoritative payment amount.
            $monto = (float) $pedido['total'];
            if (!$this->repositorio->crearPago($idPedido, $metodoPago, $monto, $propina)) {
                throw new \RuntimeException('No se pudo registrar el pago.');
            }

            if (!$this->repositorio->marcarPedidoEntregado($idPedido)) {
                throw new \RuntimeException('No se pudo actualizar el estado del pedido.');
            }

            if (!$this->repositorio->liberarMesaSiNoQuedanPedidosAbiertos((int) $pedido['id_mesa'], $idPedido)) {
                throw new \RuntimeException('No se pudo liberar la mesa.');
            }

            mysqli_commit($connection);
            return array('ok' => true, 'codigo' => 'facturado', 'monto' => $monto);
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            \App\Support\ErrorHandler::log($exception);
            return array('ok' => false, 'codigo' => 'error');
        }
    }

    public function solicitarCobro($idPedido, $idUsuario, $metodoPago)
    {
        $idPedido = (int) $idPedido;
        $idUsuario = (int) $idUsuario;
        $metodoPago = trim((string) $metodoPago);
        if ($idPedido <= 0 || $idUsuario <= 0 || !in_array($metodoPago, self::METODOS_PERMITIDOS, true)) {
            return array('ok' => false, 'error' => 'Selecciona un pedido y un medio de pago valido.');
        }

        $connection = $this->repositorio->connection();
        mysqli_begin_transaction($connection);
        try {
            // Uses the same order lock as payment confirmation to prevent a request racing with a payment.
            $pedido = $this->repositorio->buscarPedidoFacturable($idPedido);
            if (!$pedido || (int) $pedido['id_usuario'] !== $idUsuario) {
                mysqli_rollback($connection);
                return array('ok' => false, 'error' => 'El pedido no pertenece a tu cuenta.');
            }
            if ($this->repositorio->existePago($idPedido)) {
                mysqli_rollback($connection);
                return array('ok' => false, 'error' => 'Este pedido ya esta pagado.');
            }
            if (!in_array($pedido['estado'], array('Entregado', 'ArchivadoCocina'), true)) {
                mysqli_rollback($connection);
                return array('ok' => false, 'error' => 'Puedes solicitar el cobro cuando el pedido este entregado.');
            }
            if (!$this->repositorio->solicitarCobro($idPedido, $metodoPago)) {
                throw new \RuntimeException('No se pudo guardar la solicitud de cobro.');
            }
            mysqli_commit($connection);
            return array('ok' => true);
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            \App\Support\ErrorHandler::log($exception);
            return array('ok' => false, 'error' => 'No se pudo solicitar el cobro. Intenta nuevamente.');
        }
    }
}