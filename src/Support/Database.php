<?php

namespace App\Support;

// Centralized DB configuration/connection. Same behavior as the previous app_db_config/app_db_connect functions.
class Database
{
    private static $connection = null;

    public static function config(array $overrides = array())
    {
        $config = array(
            'host' => getenv('APP_DB_HOST') ?: 'localhost',
            'user' => getenv('APP_DB_USER') ?: 'root',
            'password' => getenv('APP_DB_PASSWORD') ?: '',
            'database' => getenv('APP_DB_NAME') ?: 'ProyectoMagnus',
            'charset' => getenv('APP_DB_CHARSET') ?: 'utf8mb4',
        );

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $config[$key] = $value;
        }

        return $config;
    }

    public static function connect(array $overrides = array())
    {
        if (!empty($overrides)) {
            return self::createConnection($overrides);
        }

        if (self::$connection === null) {
            self::$connection = self::createConnection($overrides);
        }

        return self::$connection;
    }

    private static function createConnection(array $overrides)
    {
        $config = self::config($overrides);

        $connection = mysqli_connect(
            (string) $config['host'],
            (string) $config['user'],
            (string) $config['password'],
            (string) $config['database']
        );

        if ($connection && !empty($config['charset'])) {
            mysqli_set_charset($connection, (string) $config['charset']);
        }

        return $connection;
    }
}
