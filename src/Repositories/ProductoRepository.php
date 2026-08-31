<?php

namespace App\Repositories;

use App\Support\Database;

class ProductoRepository
{
    private $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Database::connect();
    }

    public function listar()
    {
        $result = mysqli_query($this->connection, 'SELECT id_producto, nombre, descripcion, precio, stock, img, id_categoria, estado FROM productos ORDER BY id_producto DESC');
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
    }

    public function buscar($idProducto)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_producto, nombre, descripcion, precio, stock, img, id_categoria, estado FROM productos WHERE id_producto = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $idProducto);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $producto = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $producto ?: null;
    }

    public function existePorNombre($nombre)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_producto FROM productos WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $nombre);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existe = $result && mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($stmt);
        return $existe;
    }

    public function contar()
    {
        $result = mysqli_query($this->connection, 'SELECT COUNT(*) AS total FROM productos');
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (int) ($row['total'] ?? 0);
    }

    public function crear($nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null)
    {
        if ($idCategoria !== null && $idCategoria !== '') {
            $stmt = mysqli_prepare($this->connection, 'INSERT INTO productos (nombre, descripcion, precio, stock, img, id_categoria) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssdisi', $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria);
        } else {
            $stmt = mysqli_prepare($this->connection, 'INSERT INTO productos (nombre, descripcion, precio, stock, img) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssdis', $nombre, $descripcion, $precio, $stock, $imagen);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria = null)
    {
        if ($idCategoria !== null && $idCategoria !== '') {
            $stmt = mysqli_prepare($this->connection, 'UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, img=?, id_categoria=? WHERE id_producto=?');
            mysqli_stmt_bind_param($stmt, 'ssdissi', $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria, $idProducto);
        } else {
            $stmt = mysqli_prepare($this->connection, 'UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, img=? WHERE id_producto=?');
            mysqli_stmt_bind_param($stmt, 'ssdisi', $nombre, $descripcion, $precio, $stock, $imagen, $idProducto);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminar($idProducto)
    {
        $stmt = mysqli_prepare($this->connection, 'DELETE FROM productos WHERE id_producto = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idProducto);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}