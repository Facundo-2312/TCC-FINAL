<?php

session_start();

if (!isset($_SESSION['Usuario'])) {
    header('Location: /proj/Login.php');
    exit();
}

$rol = (int) ($_SESSION['Rol'] ?? 0);

if ($rol === 3) {
    header('Location: /proj/MVCsix1.0/pedido/pedido.php');
    exit();
}

header('Location: /proj/InterfazObtenerPedidos.php');
exit();
