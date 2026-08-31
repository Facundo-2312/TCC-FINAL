<?php

namespace App\Services;

use App\Repositories\ProductoRepository;

class ProductoService
{
    private $repositorio;

    public function __construct(ProductoRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new ProductoRepository();
    }

    public function listar() { return $this->repositorio->listar(); }
    public function buscar($idProducto) { return (int) $idProducto > 0 ? $this->repositorio->buscar((int) $idProducto) : null; }
    public function existePorNombre($nombre) { return $this->repositorio->existePorNombre(trim((string) $nombre)); }
    public function contar() { return $this->repositorio->contar(); }

    public function crear($nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null)
    {
        return $this->guardar(null, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria);
    }

    public function actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null)
    {
        return $this->guardar((int) $idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria);
    }

    private function guardar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria)
    {
        $nombre = trim((string) $nombre);
        $descripcion = trim((string) $descripcion);
        $precio = (float) $precio;
        $stock = (int) $stock;
        if ($nombre === '' || $descripcion === '' || $precio <= 0 || $stock < 0 || trim((string) $imagen) === '') return false;
        if ($idCategoria !== null && $idCategoria !== '') $idCategoria = (int) $idCategoria;
        return $idProducto === null
            ? $this->repositorio->crear($nombre, $descripcion, $precio, $stock, $imagen, $idCategoria)
            : $this->repositorio->actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria);
    }

    public function eliminar($idProducto)
    {
        return (int) $idProducto > 0 && $this->repositorio->eliminar((int) $idProducto);
    }
}