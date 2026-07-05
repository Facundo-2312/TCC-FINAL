<?php

class Producto
{
    private $con;
    private $dbhost = "localhost";
    private $dbuser = "root";
    private $dbpass = "";
    private $dbname = "ProyectoMagnus";

    public function __construct()
    {
        $this->connect_db();
    }

    public function connect_db()
    {
        $this->con = mysqli_connect(
            $this->dbhost,
            $this->dbuser,
            $this->dbpass,
            $this->dbname
        );

        if (mysqli_connect_error()) {
            die("Error de conexión: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->con, 'utf8mb4');
    }

    public function ListarProductos()
    {
        return $this->all();
    }

    public function all()
    {
        $sql = "SELECT id_producto, nombre, descripcion, precio, stock, img, id_categoria, estado
                FROM productos
                ORDER BY id_producto DESC";

        $res = mysqli_query($this->con, $sql);

        if (!$res) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    }

    public function existsByName($nombre)
    {
        $stmt = mysqli_prepare(
            $this->con,
            "SELECT id_producto FROM productos WHERE LOWER(nombre) = LOWER(?) LIMIT 1"
        );

        if (!$stmt) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmt, 's', $nombre);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row !== null;
    }

    public function countAll()
    {
        $sql = "SELECT COUNT(*) AS total FROM productos";
        $res = mysqli_query($this->con, $sql);

        if (!$res) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        $row = mysqli_fetch_assoc($res);
        return (int) ($row['total'] ?? 0);
    }

    public function BuscarProducto($idProducto)
    {
        return $this->find((int) $idProducto);
    }

    public function find($idProducto)
    {
        $stmt = mysqli_prepare(
            $this->con,
            "SELECT id_producto, nombre, descripcion, precio, stock, img, id_categoria, estado
             FROM productos
             WHERE id_producto = ?"
        );

        if (!$stmt) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmt, 'i', $idProducto);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;

        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    public function create($nombre, $descripcion, $precio, $stock, $img, $idCategoria = null)
    {
        if ($idCategoria !== null && $idCategoria !== '') {
            $stmt = mysqli_prepare(
                $this->con,
                "INSERT INTO productos (nombre, descripcion, precio, stock, img, id_categoria)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                die("Error SQL: " . mysqli_error($this->con));
            }

            $idCategoria = (int) $idCategoria;
            $precio = (float) $precio;
            $stock = (int) $stock;
            mysqli_stmt_bind_param($stmt, 'ssdisi', $nombre, $descripcion, $precio, $stock, $img, $idCategoria);
        } else {
            $stmt = mysqli_prepare(
                $this->con,
                "INSERT INTO productos (nombre, descripcion, precio, stock, img)
                 VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                die("Error SQL: " . mysqli_error($this->con));
            }

            $precio = (float) $precio;
            $stock = (int) $stock;
            mysqli_stmt_bind_param($stmt, 'ssdis', $nombre, $descripcion, $precio, $stock, $img);
        }

        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $res;
    }

    public function update($idProducto, $nombre, $descripcion, $precio, $stock, $img, $idCategoria = null)
    {
        $precio = (float) $precio;
        $stock = (int) $stock;
        $idProducto = (int) $idProducto;

        if ($idCategoria !== null && $idCategoria !== '') {
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE productos
                 SET nombre = ?, descripcion = ?, precio = ?, stock = ?, img = ?, id_categoria = ?
                 WHERE id_producto = ?"
            );

            if (!$stmt) {
                die("Error SQL: " . mysqli_error($this->con));
            }

            $idCategoria = (int) $idCategoria;
            mysqli_stmt_bind_param($stmt, 'ssdissi', $nombre, $descripcion, $precio, $stock, $img, $idCategoria, $idProducto);
        } else {
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE productos
                 SET nombre = ?, descripcion = ?, precio = ?, stock = ?, img = ?
                 WHERE id_producto = ?"
            );

            if (!$stmt) {
                die("Error SQL: " . mysqli_error($this->con));
            }

            mysqli_stmt_bind_param($stmt, 'ssdisi', $nombre, $descripcion, $precio, $stock, $img, $idProducto);
        }

        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $res;
    }

    public function delete($idProducto)
    {
        $stmt = mysqli_prepare(
            $this->con,
            "DELETE FROM productos WHERE id_producto = ?"
        );

        if (!$stmt) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmt, 'i', $idProducto);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $res;
    }
}

?>