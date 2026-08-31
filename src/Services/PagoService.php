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

            if (!$this->repositorio->liberarMesa((int) $pedido['id_mesa'])) {
                throw new \RuntimeException('No se pudo liberar la mesa.');
            }

            mysqli_commit($connection);
            return array('ok' => true, 'codigo' => 'facturado', 'monto' => $monto);
        } catch (\Throwable $exception) {
            mysqli_rollback($connection);
            return array('ok' => false, 'codigo' => 'error');
        }
    }
}