<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('../Login.php', ['1']);

$flash = app_get_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agregar producto</title>

<link rel="stylesheet" type="text/css" href="../estilos/Create.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>

</head>
<body>

<header>

<a class="Atras" href="index.php">
  <i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a>
<h1>Ingresar Producto</h1>

</header>

<div class="contenido">

<?php if (!empty($flash) && !empty($flash['message'])) { ?>
  <div class="flash flash-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php } ?>

<form method="post" action="InterfazProducto.php" enctype="multipart/form-data">
<?php echo csrf_field(); ?>

<h2><i class="fas fa-plus-circle" aria-hidden="true"></i> Nuevo producto</h2>
<p class="form-intro">Completa los datos del producto y selecciona una imagen. Los campos marcados son obligatorios.</p>

<label for="Nombre">Nombre del producto</label>
<input type="text" name="Nombre" id="Nombre" placeholder="Ej.: Ensalada de frutas" maxlength="100" required>

<label for="Descripcion">Descripcion</label>
<input type="text" name="Descripcion" id="Descripcion" placeholder="Describe brevemente el producto" maxlength="100" required>

<label for="Precio">Precio</label>
<input type="number" step="0.01" name="Precio" id="Precio" placeholder="0,00" min="0.01" required>

<label for="Stock">Stock disponible</label>
<input type="number" name="Stock" id="Stock" placeholder="Cantidad" min="0" max="999999" value="0" required>

<div class="custom-file-input">
  <label for="fileTest"><i class="fas fa-file-upload"></i>Seleccionar imagen del producto</label>
  <input type="file" id="fileTest" name="fileTest" accept="image/*" required>
  <span id="file-name" aria-live="polite">Ningun archivo seleccionado</span>
</div>

<p><input class="guardar" type="submit" name="submit" value="Guardar producto"></p>
<input id="createID" name="crud" type="hidden" value="1">

</form>

</div>

<script>
document.getElementById('fileTest').addEventListener('change', function () {
  document.getElementById('file-name').textContent = this.files.length ? this.files[0].name : 'Ningun archivo seleccionado';
});
</script>

</body>
</html>
