<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '3']);

$rol = (int) ($_SESSION['Rol'] ?? 0);

if ($rol === 3) {
    app_redirect('MVCsix1.0/pedido/pedido.php');
}

app_redirect('InterfazObtenerPedidos.php');
