<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

// Deleting is a state change: only POST with a valid CSRF token (old GET links just go back to the list).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_redirect('EmpleadoI.php');
}

csrf_verify_or_die('EmpleadoI.php');

$Ci = (int) ($_POST['ID'] ?? 0);
if ($Ci <= 0) {
    app_set_flash('error', 'Selecciona un funcionario valido.');
    app_redirect('EmpleadoI.php');
}

require_once __DIR__ . '/Empleado.php';
$Empleado = new Empleado();
if ($Empleado->delete($Ci)) {
    app_set_flash('success', 'Funcionario eliminado correctamente.');
} else {
    $detalle = trim((string) $Empleado->getLastError());
    app_set_flash('error', $detalle !== '' ? $detalle : 'No se pudo eliminar el funcionario.');
}
app_redirect('EmpleadoI.php');