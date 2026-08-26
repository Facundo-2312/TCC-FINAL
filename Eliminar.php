<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

if (isset($_GET['ID'])) {
    include('Empleado.php');
    $Empleado = new Empleado();
    $Ci = intval($_GET['ID']);
    $Empleado->delete($Ci);
}

header('Location: EmpleadoI.php');
exit();

?>