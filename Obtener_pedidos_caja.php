<?php

require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

header('Content-Type: application/json; charset=utf-8');
echo json_encode((new App\Controllers\PedidoController())->listarParaCajaLegacy(), JSON_UNESCAPED_UNICODE);
