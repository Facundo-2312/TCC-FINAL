<?php

// CLI-only migration runner. Usage: C:\xampp\php\php.exe database\migrate.php [--status|--dry-run]
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This command can only run from the command line.' . PHP_EOL);
}

require_once __DIR__ . '/../app_bootstrap.php';

$connection = app_db_connect();
if (!$connection) {
    fwrite(STDERR, 'No se pudo conectar a la base de datos.' . PHP_EOL);
    exit(1);
}

$argument = $argv[1] ?? '';
if (!in_array($argument, array('', '--status', '--dry-run'), true)) {
    fwrite(STDERR, 'Uso: php database/migrate.php [--status|--dry-run]' . PHP_EOL);
    exit(1);
}

mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(150) PRIMARY KEY, aplicado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$appliedResult = mysqli_query($connection, 'SELECT version FROM schema_migrations');
$applied = array();
while ($row = mysqli_fetch_assoc($appliedResult)) {
    $applied[$row['version']] = true;
}

$files = glob(__DIR__ . '/../SQL/migrations/*.sql');
sort($files, SORT_STRING);

if ($argument === '--status') {
    foreach ($files as $file) {
        $version = basename($file);
        echo ($applied[$version] ?? false ? '[aplicada] ' : '[pendiente] ') . $version . PHP_EOL;
    }
    exit(0);
}

function migrationStatements($sql)
{
    $delimiter = ';';
    $buffer = '';
    $statements = array();

    foreach (explode("\n", $sql) as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $matches)) {
            $delimiter = $matches[1];
            continue;
        }
        $buffer .= $line . "\n";
        while (($position = strpos($buffer, $delimiter)) !== false) {
            $statement = trim(substr($buffer, 0, $position));
            $buffer = substr($buffer, $position + strlen($delimiter));
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }
    }

    return $statements;
}

foreach ($files as $file) {
    $version = basename($file);
    if (isset($applied[$version])) {
        continue;
    }

    if ($argument === '--dry-run') {
        echo '[pendiente] ' . $version . PHP_EOL;
        continue;
    }

    foreach (migrationStatements(file_get_contents($file)) as $statement) {
        if (!mysqli_query($connection, $statement)) {
            fwrite(STDERR, 'Error en ' . $version . ': ' . mysqli_error($connection) . PHP_EOL);
            exit(1);
        }
    }

    $stmt = mysqli_prepare($connection, 'INSERT INTO schema_migrations (version) VALUES (?)');
    mysqli_stmt_bind_param($stmt, 's', $version);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo '[aplicada] ' . $version . PHP_EOL;
}
