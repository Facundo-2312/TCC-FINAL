<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);

$empleadoController = new App\Controllers\EmpleadoController();

function ListarEmpleado()
{
    $controller = new App\Controllers\EmpleadoController();
    return $controller->listar();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud'])) {
    csrf_verify_or_die('EmpleadoI.php');

    $crud = (int) $_POST['crud'];

    if ($crud === 1) {
        $CI = (int) ($_POST['CI'] ?? 0);
        $Nombre = trim($_POST['Nombre'] ?? '');
        $Apellido = trim($_POST['Apellido'] ?? '');
        $Direccion = trim($_POST['Direccion'] ?? '');
        $Rol = trim($_POST['Rol'] ?? '');
        $Usuario = trim($_POST['Usuario'] ?? '');
        $Pass = (string) ($_POST['Pass'] ?? '');

        if ($CI <= 0 || $Nombre === '' || $Apellido === '' || $Direccion === '' || $Rol === '' || $Usuario === '' || $Pass === '') {
            die("Datos incompletos para crear funcionario.");
        }

        $res = $empleadoController->crear(array(
            'CI' => $CI, 'Nombre' => $Nombre, 'Apellido' => $Apellido,
            'Direccion' => $Direccion, 'Rol' => $Rol, 'Usuario' => $Usuario, 'Pass' => $Pass
        ));

        if ($res) {
            app_redirect('EmpleadoI.php');
        }

        $detalle = trim((string) $empleadoController->ultimoError());
        die($detalle !== '' ? $detalle : "Error al insertar.");
    }

    if ($crud === 2) {
        $CI = (int) ($_POST['CI'] ?? 0);
        $Nombre = trim($_POST['Nombre'] ?? '');
        $Apellido = trim($_POST['Apellido'] ?? '');
        $Direccion = trim($_POST['Direccion'] ?? '');
        $Rol = trim($_POST['Rol'] ?? ($_POST['IDRol'] ?? ''));
        $Usuario = trim($_POST['Usuario'] ?? '');
        $Pass = (string) ($_POST['Pass'] ?? '');

        if ($CI <= 0 || $Nombre === '' || $Apellido === '' || $Direccion === '' || $Rol === '' || $Usuario === '') {
            die("Datos incompletos para actualizar funcionario.");
        }

        $res = $empleadoController->actualizar(array(
            'CI' => $CI, 'Nombre' => $Nombre, 'Apellido' => $Apellido,
            'Direccion' => $Direccion, 'Rol' => $Rol, 'Usuario' => $Usuario, 'Pass' => $Pass
        ));

        if ($res) {
            app_redirect('EmpleadoI.php');
        }

        $detalle = trim((string) $empleadoController->ultimoError());
        die($detalle !== '' ? $detalle : "Error al actualizar.");
    }

    app_redirect('EmpleadoI.php');
}
