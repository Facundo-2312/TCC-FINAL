<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This command can only run from the command line.' . PHP_EOL);
}

$installPath = __DIR__ . '/../SQL/install.sql';
$outputPath = __DIR__ . '/../SQL/install_infinityfree.sql';
$sql = file_get_contents($installPath);
if ($sql === false) {
    fwrite(STDERR, 'Could not read SQL/install.sql.' . PHP_EOL);
    exit(1);
}

function replaceSection($sql, $startMarker, $endMarker, $replacement)
{
    $start = strpos($sql, $startMarker);
    $end = $start === false ? false : strpos($sql, $endMarker, $start);
    if ($start === false || $end === false) {
        throw new RuntimeException('Expected SQL section was not found: ' . $startMarker);
    }
    return substr($sql, 0, $start) . $replacement . substr($sql, $end);
}

try {
    $roles = strpos($sql, '-- ROLES');
    if ($roles === false) {
        throw new RuntimeException('Roles section was not found.');
    }
    $sql = substr($sql, $roles);
    $sql = replaceSection(
        $sql,
        '-- USUARIOS (acceso al sistema)',
        '-- CATEGORIAS',
        "-- USUARIOS: crear una cuenta de prueba segura despues de importar este esquema.\n\n"
    );
    $sql = replaceSection(
        $sql,
        'INSERT INTO productos (nombre,descripcion,precio,stock,id_categoria,img) VALUES',
        '-- MESAS',
        "-- Importa SQL/catalogo_infinityfree.sql para cargar el catalogo completo.\n\n"
    );

    $employeeTriggers = strpos($sql, '-- Mantiene empleado.id_rol sincronizada');
    $cashTable = strpos($sql, '-- CAJA (apertura/cierre de sesion de caja)');
    if ($employeeTriggers === false || $cashTable === false || $cashTable <= $employeeTriggers) {
        throw new RuntimeException('Employee trigger section was not found.');
    }
    $sql = substr($sql, 0, $employeeTriggers) . substr($sql, $cashTable);

    $sql = replaceSection(
        $sql,
        'INSERT INTO caja (fecha_apertura,monto_inicial,id_usuario)',
        '-- PEDIDOS',
        ''
    );

    $sql = replaceSection(
        $sql,
        '-- Integridad de stock: valida disponibilidad antes de vender',
        '-- PAGOS',
        "-- El control de stock se realiza en PHP en este hosting, que no permite triggers.\n\n"
    );
    $views = strpos($sql, '-- VISTAS');
    if ($views === false) {
        throw new RuntimeException('Views section was not found.');
    }
    $sql = substr($sql, 0, $views);

    $sql = "-- RESTAURANTE-UY - Esquema para InfinityFree.\n"
        . "-- Selecciona primero la base vacia en phpMyAdmin y luego importa este archivo.\n"
        . "-- No crea ni elimina bases de datos, no agrega cuentas demo y no requiere triggers ni rutinas.\n\n"
        . trim($sql) . "\n";

    foreach (array('DROP DATABASE', 'CREATE DATABASE', 'USE ProyectoMagnus', 'CREATE TRIGGER', 'CREATE PROCEDURE', 'CREATE VIEW', 'DELIMITER', 'admin123') as $forbidden) {
        if (stripos($sql, $forbidden) !== false) {
            throw new RuntimeException('Unsupported or unsafe SQL remains: ' . $forbidden);
        }
    }

    if (file_put_contents($outputPath, $sql) === false) {
        throw new RuntimeException('Could not write SQL/install_infinityfree.sql.');
    }
    echo 'Created SQL/install_infinityfree.sql.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
