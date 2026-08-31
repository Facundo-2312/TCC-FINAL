<?php

namespace App\Controllers;

use App\Services\ProductoService;

class ProductoController
{
    private $servicio;

    public function __construct(ProductoService $servicio = null)
    {
        $this->servicio = $servicio ?: new ProductoService();
    }

    public function listar() { return $this->servicio->listar(); }
    public function buscar($idProducto) { return $this->servicio->buscar($idProducto); }
    public function existePorNombre($nombre) { return $this->servicio->existePorNombre($nombre); }
    public function contar() { return $this->servicio->contar(); }
    public function crear($nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null) { return $this->servicio->crear($nombre, $descripcion, $precio, $stock, $imagen, $idCategoria); }
    public function actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null) { return $this->servicio->actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria); }
    public function eliminar($idProducto) { return $this->servicio->eliminar($idProducto); }
}