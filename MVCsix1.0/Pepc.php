<?php

require_once __DIR__ . '/../app_bootstrap.php';

// Compatibility facade for deprecated item-detail integrations. It no longer creates legacy tables.
class Pepc
{
    private $servicio;

    public function __construct()
    {
        $this->servicio = new App\Services\PedidoService();
    }

    public function connect_db() {}

    public function create($idPedido, $idProducto, $cantidad, $sinIngredientes = '', $extraIngredientes = '')
    {
        return $this->servicio->agregarDetalleLegacy($idPedido, $idProducto, $cantidad, $sinIngredientes, $extraIngredientes);
    }

    public function obtenerPedidos()
    {
        $salida = array();
        foreach ($this->servicio->listarCocina()['pedidos'] as $pedido) {
            $productos = array();
            foreach ($pedido['productos'] as $producto) {
                $productos[] = array(
                    'Nombre' => $producto['nombre'],
                    'Cantidad' => $producto['cantidad'],
                    'SinIngredientes' => $producto['sin_ingredientes'] ?? '',
                    'ExtraIngredientes' => $producto['extra_ingredientes'] ?? ''
                );
            }
            $salida[] = array(
                'IDPedido' => (int) $pedido['id_pedido'],
                'Mesa' => $pedido['id_mesa'],
                'CI' => null,
                'Estado' => $pedido['estado'],
                'Fecha' => $pedido['fecha'],
                'Productos' => $productos
            );
        }

        return $salida;
    }
}
