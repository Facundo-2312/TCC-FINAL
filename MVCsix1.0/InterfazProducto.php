<?php

session_start();

if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
    header("Location: ../Login.php");
    exit();
}

require_once "Producto.php";

function ListarProductos()
{
    $producto = new Producto();
    $lista = $producto->ListarProductos();

    $resultado = array();
    foreach ($lista as $fila) {
        $resultado[] = array(
            'IDProducto' => (int) ($fila['id_producto'] ?? 0),
            'Nombre' => $fila['nombre'] ?? '',
            'Descripcion' => $fila['descripcion'] ?? '',
            'Precio' => (float) ($fila['precio'] ?? 0),
            'Stock' => (int) ($fila['stock'] ?? 0),
            'Img' => $fila['img'] ?? ''
        );
    }

    return json_encode($resultado, JSON_UNESCAPED_UNICODE);
}

function guardarImagenProducto($campo, $imagenActual = null)
{
    if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
        return $imagenActual;
    }

    if ($_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return $imagenActual;
    }

    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo cargar la imagen.');
    }

    $maximo = 2 * 1024 * 1024;
    if ($_FILES[$campo]['size'] > $maximo) {
        throw new RuntimeException('La imagen supera el tamaño permitido de 2 MB.');
    }

    $extensionesPermitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $extension = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensionesPermitidas, true)) {
        throw new RuntimeException('Solo se permiten imágenes JPG, JPEG, PNG, GIF o WEBP.');
    }

    $directorio = __DIR__ . '/files';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombreArchivo = uniqid('prod_', true) . '.' . $extension;
    $destino = $directorio . '/' . $nombreArchivo;

    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    }

    return 'files/' . $nombreArchivo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud'])) {
    $producto = new Producto();
    $crud = (int) $_POST['crud'];

    try {
        if ($crud === 1) {
            $nombre = trim($_POST['Nombre'] ?? '');
            $descripcion = trim($_POST['Descripcion'] ?? '');
            $precio = (float) ($_POST['Precio'] ?? 0);
            $stock = (int) ($_POST['Stock'] ?? 0);
            $imagen = guardarImagenProducto('fileTest');

            if ($nombre === '' || $descripcion === '' || $precio <= 0 || $imagen === null) {
                throw new RuntimeException('Completa los campos obligatorios del producto.');
            }

            $producto->create($nombre, $descripcion, $precio, $stock, $imagen);
            header('Location: index.php');
            exit();
        }

        if ($crud === 2) {
            $idProducto = (int) ($_POST['IDProducto'] ?? 0);
            $nombre = trim($_POST['Nombre'] ?? '');
            $descripcion = trim($_POST['Descripcion'] ?? '');
            $precio = (float) ($_POST['Precio'] ?? 0);
            $stock = (int) ($_POST['Stock'] ?? 0);
            $imagenActual = $_POST['Img'] ?? '';
            $imagen = guardarImagenProducto('fileTest', $imagenActual);

            if ($idProducto <= 0 || $nombre === '' || $descripcion === '' || $precio <= 0 || $imagen === '') {
                throw new RuntimeException('Completa los campos obligatorios del producto.');
            }

            $producto->update($idProducto, $nombre, $descripcion, $precio, $stock, $imagen);
            header('Location: index.php');
            exit();
        }

        header('Location: index.php');
        exit();
    } catch (RuntimeException $e) {
        die($e->getMessage());
    }
}
