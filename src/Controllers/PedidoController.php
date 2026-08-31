<?php

namespace App\Controllers;

use App\Services\PedidoService;
use App\Support\Url;

// Thin controller: translates HTTP input into PedidoService calls and formats the response.
class PedidoController
{
    private $servicio;

    public function __construct(PedidoService $servicio = null)
    {
        $this->servicio = $servicio ?: new PedidoService();
    }

    public function cambiarEstadoDesdeQuery($idPedido, $estado, $redirectTo)
    {
        $this->servicio->cambiarEstado($idPedido, $estado);
        Url::redirect($redirectTo);
    }

    public function cambiarEstado($idPedido, $estado)
    {
        return $this->servicio->cambiarEstado($idPedido, $estado);
    }

    public function eliminarDesdeQuery($idPedido, $redirectTo)
    {
        $this->servicio->eliminar($idPedido);
        Url::redirect($redirectTo);
    }

    public function avanzarEstadoJson($idPedido)
    {
        header('Content-Type: application/json');

        $nuevoEstado = $this->servicio->avanzarEstado($idPedido);

        if ($nuevoEstado === null) {
            echo json_encode(array('ok' => false));
            return;
        }

        echo json_encode(array('ok' => true, 'nuevoEstado' => $nuevoEstado));
    }

    public function listarConDetalleJson()
    {
        header('Content-Type: application/json');
        echo json_encode($this->servicio->listarConDetalle());
    }

    public function listarCocina() { return $this->servicio->listarCocina(); }
    public function listarRecientes($limite = 30) { return $this->servicio->listarRecientes($limite); }
    public function ocultarDeCocina($idPedido) { return $this->servicio->ocultarDeCocina($idPedido); }
    public function crearPedidoRapido($mesa, $producto, $precio, $cantidad, $usuario) { return $this->servicio->crearPedidoRapido($mesa, $producto, $precio, $cantidad, $usuario); }
    public function crearPedidoMozo($numeroMesa, $usuario, $observaciones, array $items) { return $this->servicio->crearPedidoMozo($numeroMesa, $usuario, $observaciones, $items); }
    
        public function listarParaCajaLegacy()
        {
            $salida = array();
            foreach ($this->servicio->listarConDetalle() as $pedido) {
                if (!in_array($pedido['estado'], array('Entregado', 'ArchivadoCocina'), true)) continue;
                $productos = array();
                foreach ($pedido['productos'] as $producto) {
                    $productos[] = array('Nombre' => $producto['nombre'], 'Cantidad' => (int) $producto['cantidad'], 'Precio' => (float) $producto['precio']);
                }
                $salida[] = array('id' => (int) $pedido['id_pedido'], 'descripcion' => $pedido['observaciones'] ?? '', 'estado' => $pedido['estado'], 'Mesa' => $pedido['id_mesa'], 'productos' => $productos);
            }
            return $salida;
        }
}
