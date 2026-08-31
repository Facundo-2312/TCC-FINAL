<?php

namespace App\Support;

// Central append-only security log (failed logins, lockouts, CSRF failures, DB errors hidden from users).
class SecurityLog
{
    public static function path()
    {
        $configured = getenv('APP_SECURITY_LOG_PATH');
        return $configured !== false && $configured !== '' ? $configured : (__DIR__ . '/../../storage/logs/security.log');
    }

    public static function log($event, array $context = array())
    {
        $path = self::path();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $usuario = isset($_SESSION['Usuario']) ? $_SESSION['Usuario'] : '-';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';

        $line = sprintf(
            '[%s] event=%s ip=%s usuario=%s %s',
            date('Y-m-d H:i:s'),
            (string) $event,
            $ip,
            $usuario,
            empty($context) ? '' : json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
