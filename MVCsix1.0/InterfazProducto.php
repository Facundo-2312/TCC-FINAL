<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php');

require_once "Producto.php";

function asegurarCatalogoMinimo(Producto $producto, $minimo = 8)
{
    $plantillas = array(
        array('nombre' => 'Hamburguesa Clasica', 'descripcion' => 'Carne, queso y vegetales frescos', 'precio' => 350, 'stock' => 50, 'img' => 'img/hamburguesa_hq.jpg'),
        array('nombre' => 'Pizza Muzzarella', 'descripcion' => 'Pizza tradicional con muzzarella', 'precio' => 400, 'stock' => 40, 'img' => 'img/pizza_hq.jpg'),
        array('nombre' => 'Coca Cola 500ml', 'descripcion' => 'Bebida cola bien fria', 'precio' => 150, 'stock' => 100, 'img' => 'img/coca-orig.jpeg'),
        array('nombre' => 'Agua Mineral 500ml', 'descripcion' => 'Agua sin gas', 'precio' => 120, 'stock' => 80, 'img' => 'img/agua_hq.jpg'),
        array('nombre' => 'Papas Fritas', 'descripcion' => 'Porcion de papas crocantes', 'precio' => 220, 'stock' => 60, 'img' => 'files/papas.png'),
        array('nombre' => 'Helado Vainilla', 'descripcion' => 'Copa de helado', 'precio' => 190, 'stock' => 35, 'img' => 'files/Helado.jpg'),
        array('nombre' => 'Cheeseburger Doble', 'descripcion' => 'Doble carne y doble queso', 'precio' => 430, 'stock' => 45, 'img' => 'files/ham3.png'),
        array('nombre' => 'Hamburguesa Bacon', 'descripcion' => 'Con bacon crocante y cheddar', 'precio' => 460, 'stock' => 40, 'img' => 'files/ham5.png')
    );

    $total = $producto->countAll();

    foreach ($plantillas as $template) {
        if ($total >= (int) $minimo) {
            break;
        }

        if ($producto->existsByName($template['nombre'])) {
            continue;
        }

        $producto->create(
            $template['nombre'],
            $template['descripcion'],
            (float) $template['precio'],
            (int) $template['stock'],
            $template['img']
        );

        $total++;
    }
}

function resolverImagenProducto($nombreProducto, $rutaImagen)
{
    $ruta = trim((string) $rutaImagen);

    if ($ruta !== '' && preg_match('/^(https?:)?\\/\\//i', $ruta)) {
        return $ruta;
    }

    $nombre = mb_strtolower((string) $nombreProducto, 'UTF-8');

    $defaultsPorCategoria = array(
        'hamburguesa' => 'img/default_hamburguesa_hq.jpg',
        'burger' => 'img/default_hamburguesa_hq.jpg',
        'pizza' => 'img/default_pizza_hq.jpg',
        'coca' => 'img/default_bebida_hq.jpg',
        'cola' => 'img/default_bebida_hq.jpg',
        'agua' => 'img/default_bebida_hq.jpg',
        'jugo' => 'img/default_bebida_hq.jpg',
        'gaseosa' => 'img/default_bebida_hq.jpg',
        'refresco' => 'img/default_bebida_hq.jpg',
        'helado' => 'img/default_postre_hq.jpg',
        'postre' => 'img/default_postre_hq.jpg',
        'torta' => 'img/default_postre_hq.jpg'
    );

    $categoriaDefault = 'img/default_comida_hq.jpg';
    foreach ($defaultsPorCategoria as $keyword => $defaultPath) {
        if (mb_strpos($nombre, $keyword) !== false) {
            $categoriaDefault = $defaultPath;
            break;
        }
    }

    $normalizada = str_replace('\\\\', '/', $ruta);
    $normalizada = ltrim($normalizada, '/');
    $base = basename($normalizada);
    $baseSinExt = pathinfo($base, PATHINFO_FILENAME);

    $candidatas = array();

    if ($normalizada !== '') {
        $candidatas[] = $normalizada;
        if ($baseSinExt !== '') {
            $candidatas[] = 'img/' . $baseSinExt . '_hq.jpg';
            $candidatas[] = 'files/' . $baseSinExt . '_hq.jpg';
        }
        $candidatas[] = 'img/' . $base;
        $candidatas[] = 'files/' . $base;
    }

    $candidatas[] = $categoriaDefault;

    foreach ($candidatas as $candidata) {
        if ($candidata !== '' && is_file(__DIR__ . '/' . $candidata)) {
            return $candidata;
        }
    }

    return $categoriaDefault;
}

function ListarProductos()
{
    $producto = new Producto();
    asegurarCatalogoMinimo($producto, 8);
    $lista = $producto->ListarProductos();

    $resultado = array();
    foreach ($lista as $fila) {
        $imgOriginal = $fila['img'] ?? '';
        $imgResuelta = resolverImagenProducto($fila['nombre'] ?? '', $imgOriginal);

        $resultado[] = array(
            'IDProducto' => (int) ($fila['id_producto'] ?? 0),
            'Nombre' => $fila['nombre'] ?? '',
            'Descripcion' => $fila['descripcion'] ?? '',
            'Precio' => (float) ($fila['precio'] ?? 0),
            'Stock' => (int) ($fila['stock'] ?? 0),
            'Img' => $imgOriginal,
            'ImgResolved' => $imgResuelta
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
            app_set_flash('success', 'Producto creado correctamente.');
            app_redirect('index.php');
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
            app_set_flash('success', 'Producto actualizado correctamente.');
            app_redirect('index.php');
        }

        app_set_flash('warning', 'Operacion no valida.');
        app_redirect('index.php');
    } catch (RuntimeException $e) {
        app_set_flash('error', $e->getMessage());

        if ($crud === 1) {
            app_redirect('create.php');
        }

        if ($crud === 2) {
            $idProducto = (int) ($_POST['IDProducto'] ?? 0);
            if ($idProducto > 0) {
                app_redirect('update.php?ID=' . $idProducto);
            }
        }

        app_redirect('index.php');
    }
}
