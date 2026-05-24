<?php

session_start();

$Usuario = $_SESSION['Usuario'] ?? null;

if(!isset($Usuario)){
	header("location: /proj/Login.php");
	exit();

}
    else{

      
       
    
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agregar Empleado</title>

<link rel="stylesheet" type="text/css" href="estilos/Crear.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

</head>
<body>

                  
</div>    
<header>

<a href="EmpleadoI.php" class="Atras"><i class="fas fa-arrow-circle-left"></i></a>


<h1><b>Ingresar FUNCIONARIO</b></h1>

    </header>  


<div class="contenido">
			
				<form method="post" action ="InterfazE.php" enctype="multipart/form-data">

				<h2><i class="fas fa-user" style="font-size: 40px; margin-right: 5px;"></i></h2>

				
				
					<input type="number" name="CI" id="Pal" placeholder="CI" maxlength="100" required >
					
					<input type="text" name="Nombre" id="Pal" placeholder="Nombre" maxlength="100" required >
					
					<input type="text" name="Apellido" id="Pal" placeholder="Apellido" maxlength="100" required>

                    <input type="text" name="Direccion" id="Pal" placeholder="Direccion" maxlength="100" required>

					<input type="text" name="Rol" id="Pal" placeholder="Rol" maxlength="100" required>

					<input type="text" name="Usuario" id="Pal" placeholder="Usuario" maxlength="100" required>

                    <input type="password" name="Pass" id="Pal" placeholder="Contraseña" maxlength="100" required>
				
				
				
				
			
					
			
					   
					<p><input class="guardar" type="submit" name="submit" value="Guardar Datos"/></p>
					<input id="createID" name="crud" type="hidden" value="1">
					 	    
				
				</form>
			
        
</div>				

</body>
</html>