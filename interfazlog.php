<?php

// INCLUIMOS LA CLASE
require_once __DIR__ . '/app_bootstrap.php';
require_once "Empleado.php";

app_start_session();

$F = new Empleado();

// Validar que llegan datos
if (!isset($_POST['Usuario']) || !isset($_POST['Pass'])) {
    app_set_flash('error', 'Completa usuario y contrasena para ingresar.');
    app_redirect('Login.php');
}

$Usuario = trim($_POST['Usuario']);
$Pass = (string) $_POST['Pass'];

if ($Usuario === '' || $Pass === '') {
    app_set_flash('error', 'Completa usuario y contrasena para ingresar.');
    app_redirect('Login.php');
}

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
        app_redirect("Principal.php"); // Admin
    } elseif ($Rol == 2) {
        app_redirect("caja.php"); // Caja
    } elseif ($Rol == 3) {
        app_redirect("pedidos.php"); // Mozo
    } elseif ($Rol == 4) {
        app_redirect("cocina.php"); // Cocina
    } else {
        app_set_flash('warning', 'Tu usuario no tiene un rol valido.');
        app_redirect("Login.php");
    }

} else {
    // ❌ LOGIN INCORRECTO
    app_set_flash('error', 'Usuario o contrasena incorrectos.');
    app_redirect("Login.php");
}