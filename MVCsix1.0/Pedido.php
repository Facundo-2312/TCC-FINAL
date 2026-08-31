<?php

require_once __DIR__ . '/../app_bootstrap.php';

// Compatibility facade for deprecated integrations. All persistence targets ProyectoMagnus.
class Pedido
{
    private $servicio;
    private $lastError = '';
    private $lastInsertId = 0;

    public function __construct()
    {
        $this->servicio = new App\Services\PedidoService();
    }

    public function connect_db() {}

    public function getLastError()
    {
        return $this->lastError;
    }

    public function create($observaciones, $cedula, $mesa)
    {
        $this->lastInsertId = (int) $this->servicio->crearPedidoLegacy($observaciones, $cedula, $mesa);
        if ($this->lastInsertId <= 0) {
            $this->lastError = 'No se pudo guardar el pedido.';
            return false;
        }

        return true;
    }

    public function read()
    {
        $pedidos = array_filter($this->servicio->listarConDetalle(), function ($pedido) {
            return $pedido['estado'] === 'Entregado';
        });

        return json_encode(array_values($pedidos));
    }

    public function TraerID()
    {
        return $this->lastInsertId;
    }

    public function Traercantidad()
    {
        $total = count($this->servicio->listarConDetalle());
        return mysqli_query(app_db_connect(), 'SELECT ' . (int) $total . ' AS cantidad_mesas_atendidas');
    }

    public function update($observacion)
    {
        $this->lastError = 'Metodo update no implementado en esta version.';
        return false;
    }

    public function Facturar($idPedido, $tipoPago, $total)
    {
        $resultado = (new App\Controllers\PagoController())->facturar($idPedido, $tipoPago, 0.0);
        return $resultado['ok'];
    }

    public function delete($idPedido)
    {
        return $this->servicio->eliminar($idPedido);
    }

    public function BuscarPedido($idPedido)
    {
        foreach ($this->servicio->listarConDetalle() as $pedido) {
            if ((int) $pedido['id_pedido'] === (int) $idPedido) {
                return (object) $pedido;
            }
        }

        return null;
    }

    public function actualizarEstado($idPedido, $estado)
    {
        return $this->servicio->cambiarEstado($idPedido, $estado);
    }
}
