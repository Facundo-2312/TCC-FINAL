<?php

session_start();

if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
    header("Location: ../Login.php");
    exit();
}

$IDProducto = filter_input(INPUT_GET, 'ID', FILTER_VALIDATE_INT);
if (!$IDProducto) {
    header("Location: index.php");
    exit();
}

require_once "Producto.php";
$producto = new Producto();
$datosProducto = $producto->BuscarProducto($IDProducto);

if (!$datosProducto) {
    header("Location: index.php");
    exit();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Actualizar Producto</title>
<link rel="stylesheet" type="text/css" href="../estilos/update.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>
<body>

<header>
<a class="Atras" href="index.php">
  <i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a>

<h1>Editar Producto</h1>

</header>

<div class="contenido">
  <form method="post" action="InterfazProducto.php" enctype="multipart/form-data">

    <label>Nombre</label>
    <input type="text" name="Nombre" id="Nombre" class="form-control" maxlength="100" required value="<?php echo h($datosProducto['nombre']); ?>">
    <input type="hidden" name="IDProducto" id="IDProducto" class="form-control" maxlength="100" value="<?php echo h($datosProducto['id_producto']); ?>">

    <label>Descripción</label>
    <input type="text" name="Descripcion" id="Descripcion" class="form-control" maxlength="100" required value="<?php echo h($datosProducto['descripcion']); ?>">

    <label>Precio</label>
    <input type="number" step="0.01" name="Precio" id="Precio" class="form-control" maxlength="100" required value="<?php echo h($datosProducto['precio']); ?>">

    <label>Stock</label>
    <input type="number" name="Stock" id="Stock" class="form-control" maxlength="100" required value="<?php echo h($datosProducto['stock']); ?>">

    <label>Imagen actual</label>
    <?php if (!empty($datosProducto['img'])) { ?>
      <img width="200" src="<?php echo h($datosProducto['img']); ?>" alt="<?php echo h($datosProducto['nombre']); ?>">
    <?php } else { ?>
      <p>No hay imagen cargada.</p>
    <?php } ?>

    <input type="hidden" name="Img" id="Img" class="form-control" maxlength="100" value="<?php echo h($datosProducto['img']); ?>">

    <label>Nueva imagen</label>
    <input type="file" name="fileTest" id="fileTest" accept="image/*">

    <hr>

    <p><input class="boton" type="submit" name="submit" value="Actualizar Datos"></p>
    <input id="createID" name="crud" type="hidden" value="2">

  </form>
</div>

</body>
</html>
