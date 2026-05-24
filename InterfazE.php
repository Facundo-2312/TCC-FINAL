<?php

require_once "Empleado.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['Usuario'])) {
    header("Location: /proj/Login.php");
    exit();
}

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
            header("Location: EmpleadoI.php");
            exit();
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
            header("Location: EmpleadoI.php");
            exit();
        }

        die("Error al actualizar.");
    }

    header("Location: EmpleadoI.php");
    exit();
}
