<?php

require_once __DIR__ . '/../app_bootstrap.php';
app_require_login('Login.php');

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

<h2><i class="fas fa-plus"></i></h2>

<input type="text" name="Nombre" id="Pal" placeholder="Nombre" maxlength="100" required>

<input type="text" name="Descripcion" id="Pal" placeholder="Descripción" maxlength="100" required>

<input type="number" step="0.01" name="Precio" id="Pal" placeholder="Precio" min="0.01" required>

<input type="number" name="Stock" id="Pal" placeholder="Stock" min="0" value="0" required>

<div class="custom-file-input">
  <label for="fileTest"><i class="fas fa-file-upload"></i>Subir archivo</label>
  <input type="file" id="fileTest" name="fileTest" accept="image/*" required>
</div>

<p><input class="guardar" type="submit" name="submit" value="Guardar Datos"></p>
<input id="createID" name="crud" type="hidden" value="1">

</form>

</div>

</body>
</html>
