<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '3', '4']);
app_redirect('Cocina2.php');
