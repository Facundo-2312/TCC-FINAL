<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2', '3', '4']);

(new App\Controllers\PedidoController())->listarConDetalleJson();