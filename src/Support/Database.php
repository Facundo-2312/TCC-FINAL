<?php

namespace App\Support;

// Centralized DB configuration/connection. Same behavior as the previous app_db_config/app_db_connect functions.
class Database
{
    private static $connection = null;

    public static function config(array $overrides = array())
    {
        // APP_DB_* tiene prioridad; MYSQL* son las variables que inyectan Railway y otros PaaS.
        $config = array(
            'host' => self::env('APP_DB_HOST', 'MYSQLHOST') ?: 'localhost',
            'user' => self::env('APP_DB_USER', 'MYSQLUSER') ?: 'root',
            'password' => self::env('APP_DB_PASSWORD', 'MYSQLPASSWORD') ?: '',
            'database' => self::env('APP_DB_NAME', 'MYSQLDATABASE') ?: 'ProyectoMagnus',
            'charset' => Env::get('APP_DB_CHARSET') ?: 'utf8mb4',
            'port' => (int) (self::env('APP_DB_PORT', 'MYSQLPORT') ?: 3306),
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

    public static function timezoneName()
    {
        $tz = Env::get('APP_TIMEZONE') ?: 'America/Montevideo';
        return in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : 'America/Montevideo';
    }

    public static function applyTimezone()
    {
        date_default_timezone_set(self::timezoneName());
    }

    private static function env($primary, $fallback)
    {
        $value = Env::get($primary);
        if ($value === false || $value === '') {
            $value = Env::get($fallback);
        }

        return $value === false ? '' : $value;
    }

    private static function createConnection(array $overrides)
    {
        $config = self::config($overrides);

        $connection = mysqli_connect(
            (string) $config['host'],
            (string) $config['user'],
            (string) $config['password'],
            (string) $config['database'],
            (int) $config['port']
        );

        if ($connection && !empty($config['charset'])) {
            mysqli_set_charset($connection, (string) $config['charset']);
        }

        if ($connection) {
            // Misma zona que PHP, como desfase fijo (no requiere tablas de zonas cargadas en MySQL).
            $offset = (new \DateTime('now', new \DateTimeZone(self::timezoneName())))->format('P');
            mysqli_query($connection, "SET time_zone = '" . $offset . "'");
        }

        return $connection;
    }
}
