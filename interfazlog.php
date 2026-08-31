<?php

// INCLUIMOS LA CLASE
require_once __DIR__ . '/app_bootstrap.php';
require_once "Empleado.php";

use App\Support\LoginThrottle;
use App\Support\SecurityLog;

app_start_session();

$F = new Empleado();

// Validar que llegan datos
if (!isset($_POST['Usuario']) || !isset($_POST['Pass'])) {
    app_set_flash('error', 'Completa usuario y contrasena para ingresar.');
    app_redirect('Login.php');
}

csrf_verify_or_die('Login.php');

$Usuario = trim($_POST['Usuario']);
$Pass = (string) $_POST['Pass'];

if ($Usuario === '' || $Pass === '') {
    app_set_flash('error', 'Completa usuario y contrasena para ingresar.');
    app_redirect('Login.php');
}

// Bloqueo de fuerza bruta: 5 intentos fallidos = 15 minutos bloqueado (por usuario+IP).
$segundosBloqueado = LoginThrottle::segundosBloqueado($Usuario);
if ($segundosBloqueado > 0) {
    $minutos = (int) ceil($segundosBloqueado / 60);
    app_set_flash('error', "Demasiados intentos fallidos. Intenta nuevamente en {$minutos} minuto(s).");
    app_redirect('Login.php');
}

// LOGIN
$res = $F->Login($Usuario, $Pass);

if ($res !== null) {

    LoginThrottle::limpiar($Usuario);
    SecurityLog::log('login_exitoso', array('usuario' => $Usuario));

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
    LoginThrottle::registrarFallo($Usuario);
    app_set_flash('error', 'Usuario o contrasena incorrectos.');
    app_redirect("Login.php");
}