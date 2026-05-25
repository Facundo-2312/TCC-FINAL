<?php

session_start();

$Usuario = $_SESSION['Usuario'] ?? null;

if(!isset($Usuario)){
	header("location: /proj/Login.php");
	exit();

}
    else{
    
}

if (isset($_GET['ID'])){
	$CI=intval($_GET['ID']);
} else {
	header("location:EmpleadoI.php");
}

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
<script src="/proj/no-popups.js"></script>

</head> 
<body>

<header>
  <a class="Atras" href="EmpleadoI.php">
  <i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a> 
  <h1>Editar funcionario</h1> 

  </header>


    <div class="container">
       
                   
                    
               
			<?php
				// INCLUIMOS LA CAPA DATOS O SUPERCLASE
				include ("Empleado.php");
				
				//CREAMOS LA INSTANCIA DATOS O SUPERCLASE
				$Empleado= new Empleado();
				
				$datos_empleado=$Empleado->BuscarUsuario($CI);
				
			?>
			<div class="row">
				<form method="post" action ="InterfazE.php" enctype="multipart/form-data">

				<h2>Agregar Empleado</h2>
				
<label></label>
<input type="text" name="Nombre" id="Nombre" class='texto'placeholder="Nombre" maxlength="100" required value="<?php echo $datos_empleado->Nombre;?>">
<input type="hidden" name="CI" id="CI" class='texto' maxlength="100" value="<?php echo $datos_empleado->CI; ?>">

<label></label>
<input type="text" name="Apellido" id="Apellido" class='texto' placeholder="Apellido" maxlength="100" required value="<?php echo $datos_empleado->Apellido;?>">

<label></label>
<input type="text" name="Direccion" id="Direccion" class='texto' placeholder="Direccion" maxlength="100" required value="<?php echo $datos_empleado->Direccion;?>">

<label></label>
<input type="text" name="Rol" id="Rol" class='texto' placeholder="Rol" maxlength="100" required value="<?php echo $datos_empleado->Rol;?>">

<label></label>
<input type="text" name="Usuario" id="Usuario" class='texto' placeholder="Usuario" maxlength="100" required value="<?php echo $datos_empleado->Usuario;?>">

<label></label>
<input type="password" name="Pass" id="Pass" class='texto' placeholder="Nueva contraseña (opcional)" maxlength="100" value="">
	
					<p><input class="btn btn-primary mb-2" type="submit" name="submit" value="Actualizar Datos"/></p>
					<input id="createID" name="crud" type="hidden" value="2">
					 	    

				</form>
		
        </div>
    </div>     
</body>
</html>