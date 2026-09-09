<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este comando solo puede ejecutarse desde la linea de comandos.' . PHP_EOL);
}

require_once __DIR__ . '/../app_bootstrap.php';

$conexion = app_db_connect();
if (!$conexion) {
    fwrite(STDERR, 'No se pudo conectar a la base de datos.' . PHP_EOL);
    exit(1);
}

$catalogo = array(
    array('Hamburguesa Clasica', 'Hamburguesa de carne, queso y vegetales', 350, 50, 'Comidas', 'hamburguesa-clasica.jpg', array('Hamburguesa')),
    array('Pasta al Pesto', 'Pasta con salsa pesto casera', 400, 40, 'Comidas', 'pasta-pesto.jpg', array('Pizza')),
    array('Pasta Bolognesa', 'Pasta con salsa bolognesa', 420, 35, 'Comidas', 'pasta-bolognesa.jpg', array()),
    array('Lasagna de Carne', 'Lasagna de carne y queso', 460, 30, 'Comidas', 'lasagna-carne.jpg', array()),
    array('Milanesa Completa', 'Milanesa con guarnicion', 480, 30, 'Comidas', 'milanesa-completa.jpg', array()),
    array('Chivito al Plato', 'Chivito completo al plato', 520, 25, 'Comidas', 'chivito-plato.jpg', array()),
    array('Bife de Lomo', 'Bife de lomo a la parrilla', 590, 25, 'Comidas', 'bife-lomo.jpg', array()),
    array('Pollo a la Parrilla', 'Pollo grillado con guarnicion', 440, 30, 'Comidas', 'pollo-parrilla.jpg', array()),
    array('Pescado del Dia', 'Pescado fresco con acompanamiento', 490, 20, 'Comidas', 'pescado-dia.jpg', array()),
    array('Risotto de Pollo', 'Risotto cremoso de pollo', 430, 30, 'Comidas', 'risotto-pollo.jpg', array()),
    array('Coca-Cola 600 ml', 'Bebida Coca-Cola 600 ml', 150, 100, 'Bebidas', 'coca-cola-600.jpg', array('Coca Cola')),
    array('Coca-Cola Zero 600 ml', 'Bebida sin azucar 600 ml', 150, 80, 'Bebidas', 'coca-cola-zero-600.jpg', array()),
    array('Sprite 600 ml', 'Bebida lima limon 600 ml', 150, 80, 'Bebidas', 'sprite-600.jpg', array()),
    array('Fanta Naranja 600 ml', 'Bebida sabor naranja 600 ml', 150, 80, 'Bebidas', 'fanta-naranja-600.jpg', array()),
    array('Agua Mineral 500 ml', 'Agua mineral sin gas 500 ml', 120, 80, 'Bebidas', 'agua-mineral-500.jpg', array('Agua')),
    array('Agua con Gas 500 ml', 'Agua mineral con gas 500 ml', 120, 60, 'Bebidas', 'agua-gas-500.jpg', array()),
    array('Limonada Natural', 'Limonada natural con menta', 180, 50, 'Bebidas', 'limonada-natural.jpg', array()),
    array('Jugo de Naranja', 'Jugo natural exprimido', 170, 50, 'Bebidas', 'jugo-naranja.jpg', array()),
    array('Cafe Espresso', 'Cafe espresso', 130, 60, 'Bebidas', 'cafe-espresso.jpg', array()),
    array('Cafe con Leche', 'Cafe con leche', 160, 60, 'Bebidas', 'cafe-leche.jpg', array()),
    array('Papas Fritas', 'Papas fritas crocantes', 220, 60, 'Acompanamientos', 'papas-fritas.png', array()),
    array('Papas Rusticas', 'Papas rusticas condimentadas', 240, 50, 'Acompanamientos', 'papas-rusticas.jpg', array()),
    array('Pure de Papas', 'Pure de papas cremoso', 180, 45, 'Acompanamientos', 'pure-papas.jpg', array()),
    array('Pure de Calabaza', 'Pure de calabaza', 180, 45, 'Acompanamientos', 'pure-calabaza.jpg', array()),
    array('Arroz Blanco', 'Arroz blanco', 150, 50, 'Acompanamientos', 'arroz-blanco.jpg', array()),
    array('Verduras Salteadas', 'Verduras salteadas', 210, 40, 'Acompanamientos', 'verduras-salteadas.jpg', array()),
    array('Ensalada Verde', 'Ensalada verde fresca', 220, 40, 'Acompanamientos', 'ensalada-verde.jpg', array()),
    array('Ensalada Mixta', 'Ensalada mixta', 240, 40, 'Acompanamientos', 'ensalada-mixta.jpg', array()),
    array('Empanadas', 'Empanadas caseras', 200, 45, 'Acompanamientos', 'empanadas.jpg', array()),
    array('Bastones de Mozzarella', 'Bastones de mozzarella', 260, 40, 'Acompanamientos', 'bastones-mozzarella.jpg', array()),
    array('Tiramisu', 'Postre italiano clasico', 260, 30, 'Postres', 'tiramisu.jpg', array()),
    array('Torta de Chocolate', 'Torta de chocolate', 250, 30, 'Postres', 'torta-chocolate.jpg', array()),
    array('Mousse de Chocolate', 'Mousse de chocolate', 230, 30, 'Postres', 'mousse-chocolate.jpg', array()),
    array('Helado', 'Copa de helado', 190, 35, 'Postres', 'helado.jpg', array()),
    array('Flan Casero', 'Flan con dulce de leche', 210, 30, 'Postres', 'flan-casero.jpg', array()),
    array('Copa de Dulce de Leche', 'Copa de dulce de leche', 240, 30, 'Postres', 'copa-dulce-leche.jpg', array()),
    array('Chocotorta', 'Chocotorta casera', 250, 30, 'Postres', 'chocotorta.jpg', array()),
    array('Cheesecake', 'Cheesecake cremoso', 260, 30, 'Postres', 'cheesecake.jpg', array()),
    array('Brownie con Helado', 'Brownie tibio con helado', 260, 30, 'Postres', 'brownie-helado.jpg', array()),
    array('Ensalada de Frutas', 'Ensalada de frutas frescas', 200, 30, 'Postres', 'ensalada-frutas.jpg', array()),
);

function obtenerCategoria($conexion, $nombre)
{
    $stmt = mysqli_prepare($conexion, 'SELECT id_categoria FROM categorias WHERE nombre = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $nombre);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);

    if ($fila) {
        return (int) $fila['id_categoria'];
    }

    $stmt = mysqli_prepare($conexion, 'INSERT INTO categorias (nombre) VALUES (?)');
    mysqli_stmt_bind_param($stmt, 's', $nombre);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return (int) mysqli_insert_id($conexion);
}

function productoExiste($conexion, $nombre)
{
    $stmt = mysqli_prepare($conexion, 'SELECT id_producto FROM productos WHERE nombre = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $nombre);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    return $fila ? (int) $fila['id_producto'] : 0;
}

foreach ($catalogo as $producto) {
    list($nombre, $descripcion, $precio, $stock, $categoria, $archivoImagen, $nombresAnteriores) = $producto;
    $idCategoria = obtenerCategoria($conexion, $categoria);
    $imagen = 'img/catalogo/' . $archivoImagen;
    $idProducto = productoExiste($conexion, $nombre);

    foreach ($nombresAnteriores as $nombreAnterior) {
        if ($idProducto === 0) {
            $idProducto = productoExiste($conexion, $nombreAnterior);
        }
    }

    if ($idProducto > 0) {
        $stmt = mysqli_prepare($conexion, 'UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, img = ?, id_categoria = ?, estado = "Activo" WHERE id_producto = ?');
        mysqli_stmt_bind_param($stmt, 'ssdisii', $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria, $idProducto);
    } else {
        $stmt = mysqli_prepare($conexion, 'INSERT INTO productos (nombre, descripcion, precio, stock, img, id_categoria, estado) VALUES (?, ?, ?, ?, ?, ?, "Activo")');
        mysqli_stmt_bind_param($stmt, 'ssdisi', $nombre, $descripcion, $precio, $stock, $imagen, $idCategoria);
    }

    if (!mysqli_stmt_execute($stmt)) {
        fwrite(STDERR, 'No se pudo guardar ' . $nombre . ': ' . mysqli_stmt_error($stmt) . PHP_EOL);
        mysqli_stmt_close($stmt);
        exit(1);
    }

    mysqli_stmt_close($stmt);
}

echo 'Catalogo sincronizado: ' . count($catalogo) . ' productos.' . PHP_EOL;