<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php');

$flash = app_get_flash();

require_once "Producto.php";

$indexCssVersion = @filemtime(__DIR__ . '/../estilos/index.css') ?: time();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolverRutaImagen($ruta, $nombreProducto = '')
{
  $ruta = trim((string) $ruta);
  if (preg_match('/^(https?:)?\\/\\//i', $ruta) || strpos($ruta, 'data:image/') === 0) {
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

  $defaultPath = 'img/default_comida_hq.jpg';
  foreach ($defaultsPorCategoria as $keyword => $pathDefault) {
    if (mb_strpos($nombre, $keyword) !== false) {
      $defaultPath = $pathDefault;
      break;
    }
  }

  if ($ruta === '') {
    return is_file(__DIR__ . '/' . $defaultPath) ? $defaultPath : null;
  }

  $normalizada = str_replace('\\\\', '/', $ruta);
  $normalizada = ltrim($normalizada, '/');
  $base = basename($normalizada);
  $baseSinExt = pathinfo($base, PATHINFO_FILENAME);

  $candidatas = array(
    $normalizada,
    $baseSinExt !== '' ? 'files/' . $baseSinExt . '_hq.jpg' : '',
    $baseSinExt !== '' ? 'img/' . $baseSinExt . '_hq.jpg' : '',
    'files/' . $base,
    'img/' . $base,
    '../img/' . $base,
    $defaultPath
  );

  foreach ($candidatas as $candidata) {
    if (is_file(__DIR__ . '/' . $candidata)) {
      return $candidata;
    }
  }

  return null;
}

$producto = new Producto();
$productos = $producto->ListarProductos();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Listado de productos</title>
<link rel="stylesheet" type="text/css" href="../estilos/index.css?v=<?php echo $indexCssVersion; ?>">

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="/proj/no-popups.js"></script>

</head>
<body>

<header>
<a class="Atras" href="../Principal.php">
  <i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a>

<h2>Listado de Productos</h2>
</header>

<div class="botones-container">
  <div class="bot">
    <a href="create.php" class="boton">Agregar Producto</a>
  </div>
  <div class="bot"></div>
</div>

<div class="contenido">
  <?php if (!empty($flash) && !empty($flash['message'])) { ?>
    <div class="flash flash-<?php echo h($flash['type'] ?? 'info'); ?>"><?php echo h($flash['message']); ?></div>
  <?php } ?>
  <div class="lista-centrada">
    <table class="tabla-decent">
      <thead>
        <tr>
          <th class="encabezado">ID</th>
          <th class="encabezado">Nombre</th>
          <th class="encabezado">Descripción</th>
          <th class="encabezado">Precio</th>
          <th class="encabezado">Stock</th>
          <th class="encabezado">Imagen</th>
          <th class="encabezado">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!empty($productos)) { ?>
        <?php foreach ($productos as $fila) { ?>
        <?php $imgUrl = resolverRutaImagen($fila['img'] ?? '', $fila['nombre'] ?? ''); ?>
        <tr>
          <td><?php echo h($fila['id_producto']); ?></td>
          <td><?php echo h($fila['nombre']); ?></td>
          <td><?php echo h($fila['descripcion']); ?></td>
          <td><?php echo number_format((float) $fila['precio'], 2, ',', '.'); ?></td>
          <td><?php echo h($fila['stock']); ?></td>
          <td>
            <?php if (!empty($imgUrl)) { ?>
              <img class="product-thumb" src="<?php echo h($imgUrl); ?>" alt="<?php echo h($fila['nombre']); ?>">
            <?php } else { ?>
              <span class="no-image">Sin imagen</span>
            <?php } ?>
          </td>
          <td>
            <a href="update.php?ID=<?php echo h($fila['id_producto']); ?>" class="edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
            <a href="delete.php?ID=<?php echo h($fila['id_producto']); ?>" class="delete" title="Eliminar"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php } ?>
      <?php } else { ?>
        <tr>
          <td colspan="7" style="text-align:center; padding:20px;">No hay productos cargados.</td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
