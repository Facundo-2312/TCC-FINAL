<?php
require_once __DIR__ . '/app_bootstrap.php';
$conn = app_db_connect();
if (!$conn) {
    echo "DB_FAIL\n";
    exit(1);
}
$tables = ['mesas', 'mesas_historial', 'reservas'];
foreach ($tables as $table) {
    $res = mysqli_query($conn, 'SHOW TABLES LIKE "' . mysqli_real_escape_string($conn, $table) . '"');
    echo $table . '_TABLE=' . ($res && mysqli_num_rows($res) > 0 ? 'YES' : 'NO') . "\n";
}
$res = mysqli_query($conn, 'DESCRIBE mesas');
if ($res) {
    echo "MESAS_SCHEMA\n";
    while ($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . ':' . $row['Type'] . "\n";
    }
}
$res = mysqli_query($conn, 'DESCRIBE mesas_historial');
if ($res) {
    echo "HISTORIAL_SCHEMA\n";
    while ($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . ':' . $row['Type'] . "\n";
    }
}
$serv = new App\Services\MesaService();
try {
    $data = $serv->listarConResumen();
    echo 'LISTAR_OK=' . (isset($data['mesas']) ? count($data['mesas']) : 'NO') . "\n";
    echo 'STATS=' . json_encode($data['stats'] ?? []) . "\n";
} catch (Throwable $e) {
    echo 'LISTAR_ERR=' . get_class($e) . ':' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
