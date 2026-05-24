<?php 
<?php

session_start();

if(!isset($_SESSION['Usuario'])){
    header("Location: /proj/Login.php");
    exit();
}

if (isset($_GET['ID'])) {
    include('Empleado.php');
    $Empleado = new Empleado();
    $Ci = intval($_GET['ID']);
    $Empleado->delete($Ci);
}

header('Location: EmpleadoI.php');
exit();

?>