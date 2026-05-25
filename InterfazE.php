<?php

require_once __DIR__ . '/app_bootstrap.php';
require_once "Empleado.php";

app_require_login('Login.php');

$empleado = new Empleado();

function ListarEmpleado()
{
    $repo = new Empleado();
    return $repo->ListarEmpleado();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crud'])) {
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

        $res = $empleado->create($CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass);

        if ($res) {
            app_redirect('EmpleadoI.php');
        }

        die("Error al insertar.");
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

        $res = $empleado->update($Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass, $CI);

        if ($res) {
            app_redirect('EmpleadoI.php');
        }

        die("Error al actualizar.");
    }

    app_redirect('EmpleadoI.php');
}
