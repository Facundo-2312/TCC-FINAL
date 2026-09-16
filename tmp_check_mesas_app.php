<?php
require_once __DIR__ . '/app_bootstrap.php';

try {
    $controller = new App\Controllers\MesaController();
    $result = $controller->listar();
    echo "LIST_OK\n";
    var_export($result);
} catch (Throwable $e) {
    echo "EXCEPTION:" . get_class($e) . "\n";
    echo "MESSAGE:" . $e->getMessage() . "\n";
    echo "FILE:" . $e->getFile() . "\n";
    echo "LINE:" . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
