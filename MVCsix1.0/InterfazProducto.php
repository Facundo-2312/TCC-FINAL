<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php');

require_once "Producto.php";

function claveCanonicaProducto($nombreProducto)
{
    $nombre = mb_strtolower(trim((string) $nombreProducto), 'UTF-8');

    if ($nombre === '') {
        return '';
    }

    $map = array(
        'agua' => 'base-agua',
        'coca' => 'base-coca',
        'cola' => 'base-coca',
        'pizza' => 'base-pizza',
        'hamburguesa' => 'base-hamburguesa'
    );

    foreach ($map as $keyword => $clave) {
        if (mb_strpos($nombre, $keyword) !== false) {
            return $clave;
        }
    }

    return 'prod-' . preg_replace('/\s+/', '-', $nombre);
}

function contarProductosUnicos($lista)
{
    $claves = array();

    foreach ((array) $lista as $fila) {
        $nombre = (string) ($fila['nombre'] ?? '');
        $clave = claveCanonicaProducto($nombre);

        if ($clave === '') {
            continue;
        }

        $claves[$clave] = true;
    }

    return count($claves);
}

function existeProductoPorKeywords($lista, $keywords)
{
    foreach ((array) $lista as $fila) {
        $nombre = mb_strtolower((string) ($fila['nombre'] ?? ''), 'UTF-8');

        foreach ((array) $keywords as $keyword) {
            if (mb_strpos($nombre, mb_strtolower((string) $keyword, 'UTF-8')) !== false) {
                return true;
            }
        }
    }

    return false;
}

function asegurarCatalogoMinimo(Producto $producto, $minimo = 8)
{
    $plantillas = array(
        array('nombre' => 'Papas Fritas', 'descripcion' => 'Porcion de papas crocantes', 'precio' => 220, 'stock' => 60, 'img' => 'files/papas.png', 'keywords' => array('papas')),
        array('nombre' => 'Helado Vainilla', 'descripcion' => 'Copa de helado', 'precio' => 190, 'stock' => 35, 'img' => 'files/Helado.jpg', 'keywords' => array('helado')),
        array('nombre' => 'Cheeseburger Doble', 'descripcion' => 'Doble carne y doble queso', 'precio' => 430, 'stock' => 45, 'img' => 'files/ham3.png', 'keywords' => array('cheeseburger')),
        array('nombre' => 'Hamburguesa Bacon', 'descripcion' => 'Con bacon crocante y cheddar', 'precio' => 460, 'stock' => 40, 'img' => 'files/ham5.png', 'keywords' => array('bacon')),
        array('nombre' => 'Brownie con Helado', 'descripcion' => 'Brownie tibio con helado', 'precio' => 260, 'stock' => 30, 'img' => 'img/default_postre_hq.jpg', 'keywords' => array('brownie')),
        array('nombre' => 'Nuggets de Pollo', 'descripcion' => 'Nuggets crujientes con salsa', 'precio' => 280, 'stock' => 45, 'img' => 'img/nuggets_pollo_real.jpg', 'keywords' => array('nuggets')),
        array('nombre' => 'Jugo de Naranja', 'descripcion' => 'Jugo natural exprimido', 'precio' => 170, 'stock' => 50, 'img' => 'img/jugo_naranja_real.jpg', 'keywords' => array('jugo')),
        array('nombre' => 'Ensalada Cesar', 'descripcion' => 'Lechuga, croutones y aderezo', 'precio' => 300, 'stock' => 35, 'img' => 'img/default_comida_hq.jpg', 'keywords' => array('ensalada'))
    );

    $lista = $producto->ListarProductos();
    $totalUnicos = contarProductosUnicos($lista);

    foreach ($plantillas as $template) {
        if ($totalUnicos >= (int) $minimo) {
            break;
        }

        if (existeProductoPorKeywords($lista, $template['keywords'])) {
            continue;
        }

        $ok = $producto->create(
            $template['nombre'],
            $template['descripcion'],
            (float) $template['precio'],
            (int) $template['stock'],
            $template['img']
        );

        if ($ok) {
            $lista[] = array(
                'nombre' => $template['nombre']
            );
            $totalUnicos = contarProductosUnicos($lista);
        }
    }

    return $producto->ListarProductos();
}

function resolverImagenProducto($nombreProducto, $rutaImagen)
{
    $ruta = trim((string) $rutaImagen);

    if ($ruta !== '' && preg_match('/^(https?:)?\\/\\//i', $ruta)) {
        return $ruta;
    }

    $nombre = mb_strtolower((string) $nombreProducto, 'UTF-8');

    $imagenesEspecificas = array(
        'hamburguesa bacon' => 'files/ham2.png',
        'cheeseburger' => 'files/ham5.png',
        'hamburguesa' => 'img/default_hamburguesa_hq.jpg',
        'pizza' => 'img/default_pizza_hq.jpg',
        'coca cola' => 'img/coca-orig.jpeg',
        'coca' => 'img/coca-orig.jpeg',
        'cola' => 'img/coca-orig.jpeg',
        'agua' => 'img/agua_hq.jpg',
        'nuggets' => 'img/nuggets_pollo_real.jpg',
        'jugo de naranja' => 'img/jugo_naranja_real.jpg',
        'jugo' => 'img/jugo_naranja_real.jpg',
        'papas fritas' => 'files/papas.png',
        'papas' => 'files/papas.png',
        'helado vainilla' => 'files/Helado.jpg',
        'helado' => 'files/Helado.jpg'
    );

    foreach ($imagenesEspecificas as $keyword => $rutaEspecifica) {
        if (mb_strpos($nombre, $keyword) !== false && is_file(__DIR__ . '/' . $rutaEspecifica)) {
            return $rutaEspecifica;
        }
    }

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

    // En este proyecto pizza_hq.jpg tiene una imagen incorrecta,
    // por eso forzamos la pizza al recurso validado por negocio.
    if (mb_strpos($nombre, 'pizza') !== false && is_file(__DIR__ . '/img/default_pizza_hq.jpg')) {
        return 'img/default_pizza_hq.jpg';
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
    $lista = $producto->ListarProductos();

    $resultado = array();

    foreach ($lista as $fila) {
        $nombre = $fila['nombre'] ?? '';

        $imgOriginal = $fila['img'] ?? '';
        $imgResuelta = resolverImagenProducto($nombre, $imgOriginal);

        $resultado[] = array(
            'IDProducto' => (int) ($fila['id_producto'] ?? 0),
            'Nombre' => $nombre,
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

    // Verifica el contenido real del archivo (no solo la extensión/nombre) para evitar subir
    // un ejecutable/script disfrazado con extensión de imagen.
    $tiposPermitidos = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $mimeReal = false;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $_FILES[$campo]['tmp_name']);
        finfo_close($finfo);
    }

    if (!$mimeReal || !in_array($mimeReal, $tiposPermitidos, true)) {
        throw new RuntimeException('El archivo no es una imagen valida.');
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
    app_require_login('../Login.php', ['1']);
    csrf_verify_or_die('index.php');
    $productoController = new App\Controllers\ProductoController();
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

            if (!$productoController->crear($nombre, $descripcion, $precio, $stock, $imagen)) {
                throw new RuntimeException('No se pudo crear el producto.');
            }
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

            if (!$productoController->actualizar($idProducto, $nombre, $descripcion, $precio, $stock, $imagen)) {
                throw new RuntimeException('No se pudo actualizar el producto.');
            }
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
