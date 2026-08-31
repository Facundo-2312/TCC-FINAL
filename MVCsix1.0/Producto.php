<?php

require_once __DIR__ . '/../app_bootstrap.php';

// Compatibility facade: MVCsix1.0 callers retain the historical Producto API.
class Producto
{
    private $servicio;

    public function __construct()
    {
        $this->servicio = new App\Services\ProductoService();
    }

    public function connect_db()
    {
        // The repository connection is initialized by the service constructor.
    }

    public function ListarProductos()
    {
        return $this->all();
    }

    public function all()
    {
        return $this->servicio->listar();
    }

    public function existsByName($nombre)
    {
        return $this->servicio->existePorNombre($nombre);
    }

    public function countAll()
    {
        return $this->servicio->contar();
    }

    public function BuscarProducto($idProducto)
    {
        return $this->find($idProducto);
    }

    public function find($idProducto)
    {
        return $this->servicio->buscar($idProducto);
    }

    public function create($nombre, $descripcion, $precio, $stock, $img, $idCategoria = null)
    {
        return $this->servicio->crear($nombre, $descripcion, $precio, $stock, $img, $idCategoria);
    }

    public function update($idProducto, $nombre, $descripcion, $precio, $stock, $img, $idCategoria = null)
    {
        return $this->servicio->actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $img, $idCategoria);
    }

    public function delete($idProducto)
    {
        return $this->servicio->eliminar($idProducto);
    }
}
