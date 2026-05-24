<?php

session_start();

if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
    header("Location: ../Login.php");
    exit();
}

require_once "Producto.php";

$indexCssVersion = @filemtime(__DIR__ . '/../estilos/index.css') ?: time();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        <tr>
          <td><?php echo h($fila['id_producto']); ?></td>
          <td><?php echo h($fila['nombre']); ?></td>
          <td><?php echo h($fila['descripcion']); ?></td>
          <td><?php echo number_format((float) $fila['precio'], 2, ',', '.'); ?></td>
          <td><?php echo h($fila['stock']); ?></td>
          <td>
            <?php if (!empty($fila['img'])) { ?>
              <img src="<?php echo h($fila['img']); ?>" alt="<?php echo h($fila['nombre']); ?>" style="max-width:80px;max-height:80px;object-fit:cover;">
            <?php } else { ?>
              Sin imagen
            <?php } ?>
          </td>
          <td>
            <a href="update.php?ID=<?php echo h($fila['id_producto']); ?>" class="edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
            <a href="delete.php?ID=<?php echo h($fila['id_producto']); ?>" class="delete" title="Eliminar" onclick="return confirm('¿Eliminar este producto?');"><i class="fas fa-trash"></i></a>
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
