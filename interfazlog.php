<?php

// INCLUIMOS LA CLASE
include ("Empleado.php");

session_start(); 

$F = new Empleado();

// Validar que llegan datos
if (!isset($_POST['Usuario']) || !isset($_POST['Pass'])) {
    header("Location: Login.php");
    exit();
}

$Usuario = trim($_POST['Usuario']);
$Pass = (string) $_POST['Pass'];

// LOGIN
$res = $F->Login($Usuario, $Pass);

if ($res !== null) {

    session_regenerate_id(true);

    // ✅ GUARDAR SESIÓN SOLO SI LOGIN ES CORRECTO
    $_SESSION["Usuario"] = $Usuario;
    $_SESSION["Rol"] = $res->id_rol;
    $_SESSION["CI"] = $res->CI ?? null;

    $Rol = $res->id_rol;

    // 🔀 REDIRECCIÓN SEGÚN ROL
    if ($Rol == 1) {
        header("Location: Principal.php"); // Admin
    } elseif ($Rol == 2) {
        header("Location: caja.php"); // Caja
    } elseif ($Rol == 3) {
        header("Location: pedidos.php"); // Mozo
    } elseif ($Rol == 4) {
        header("Location: cocina.php"); // Cocina
    } else {
        header("Location: Login.php");
    }

    exit();

} else {
    // ❌ LOGIN INCORRECTO
    header("Location: Login.php");
    exit();
}