<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1']);
app_redirect('MVCsix1.0/ListaPedido.php');
