<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

if (isset($_GET['ID'])){
	$CI=intval($_GET['ID']);
} else {
	app_redirect('EmpleadoI.php');
}

include ("Empleado.php");
$Empleado= new Empleado();
$datos_empleado=$Empleado->BuscarUsuario($CI);
if (!$datos_empleado) {
	app_redirect('EmpleadoI.php');
}

function h($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
$flash = app_get_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Actualizar Funcionario</title>
<link rel="stylesheet" type="text/css" href="estilos/actu.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>

</head> 
<body>

<header>
  <a class="Atras" href="<?php echo h(app_url('EmpleadoI.php')); ?>">
  <i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a> 
  <h1>Editar funcionario</h1> 

  </header>


    <div class="container">
       
                   
                    
               
			<div class="row">
				<form method="post" action ="<?php echo h(app_url('InterfazE.php')); ?>" enctype="multipart/form-data">
				<?php echo csrf_field(); ?>
				<?php if (!empty($flash['message'])) { ?><div class="alert alert-danger" role="alert"><?php echo h($flash['message']); ?></div><?php } ?>

				<h2>Editar funcionario</h2>
				
<label></label>
<input type="text" name="Nombre" id="Nombre" class='texto'placeholder="Nombre" maxlength="100" pattern="[\p{L}\p{M} .'-]+" title="Usa letras, espacios, apostrofes o guiones." required value="<?php echo h($datos_empleado->Nombre);?>">
<input type="hidden" name="CI" id="CI" class='texto' maxlength="100" value="<?php echo h($datos_empleado->CI); ?>">

<label></label>
<input type="text" name="Apellido" id="Apellido" class='texto' placeholder="Apellido" maxlength="100" pattern="[\p{L}\p{M} .'-]+" title="Usa letras, espacios, apostrofes o guiones." required value="<?php echo h($datos_empleado->Apellido);?>">

<label></label>
<input type="text" name="Direccion" id="Direccion" class='texto' placeholder="Direccion" maxlength="100" required value="<?php echo h($datos_empleado->Direccion);?>">

<section class="employee-location" aria-labelledby="employee-location-title">
  <h3 id="employee-location-title">Ubicacion de la direccion (opcional)</h3>
  <p>Haz clic en el mapa para marcar el punto exacto. Puedes arrastrar el marcador para ajustarlo.</p>
  <div id="employee-map" role="application" aria-label="Mapa de OpenStreetMap para elegir la ubicacion"></div>
  <p id="employee-map-status" role="status" aria-live="polite">Cargando mapa...</p>
  <input type="hidden" name="direccion_latitud" id="direccion_latitud" value="<?php echo h($datos_empleado->direccion_latitud ?? ''); ?>">
  <input type="hidden" name="direccion_longitud" id="direccion_longitud" value="<?php echo h($datos_empleado->direccion_longitud ?? ''); ?>">
  <button id="employee-map-clear" type="button">Quitar ubicacion seleccionada</button>
  <small>Mapa © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a></small>
</section>

<label></label>
<select name="Rol" id="Rol" required><?php foreach (array('Administrador', 'Caja', 'Mozo', 'Cocina') as $rol) { ?><option value="<?php echo h($rol); ?>"<?php echo strcasecmp((string) $datos_empleado->Rol, $rol) === 0 ? ' selected' : ''; ?>><?php echo h($rol); ?></option><?php } ?></select>

<label></label>
<input type="text" name="Usuario" id="Usuario" class='texto' placeholder="Usuario" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]{3,50}" required value="<?php echo h($datos_empleado->Usuario);?>">

<label></label>
<input type="password" name="Pass" id="Pass" class='texto' placeholder="Nueva contraseña (opcional, 8 a 72 caracteres)" minlength="8" maxlength="72" value="">
	
					<p><input class="btn btn-primary mb-2" type="submit" name="submit" value="Actualizar Datos"/></p>
					<input id="createID" name="crud" type="hidden" value="2">
					 	    

				</form>
		
        </div>
    </div>     
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="<?php echo h(app_url('empleado-mapa.js')); ?>"></script>
</body>
</html>