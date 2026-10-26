<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

// Deleting is a state change: only POST with a valid CSRF token (old GET links just go back to the list).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('EmpleadoI.php');
}

csrf_verify_or_die('EmpleadoI.php');

$Ci = (int) ($_POST['ID'] ?? 0);
if ($Ci > 0) {
    include('Empleado.php');
    $Empleado = new Empleado();
    $Empleado->delete($Ci);
}

app_redirect('EmpleadoI.php');