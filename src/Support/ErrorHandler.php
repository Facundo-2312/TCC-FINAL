<?php

namespace App\Support;

// Centralized handling/logging for uncaught exceptions. Does not touch warning/notice
// behavior so existing pages that rely on @-suppressed warnings keep working unchanged.
class ErrorHandler
{
    private static $registered = false;

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        set_exception_handler(array(__CLASS__, 'handleException'));

        // Hide native PHP error/warning output from the browser in production while still logging it;
        // uncaught Throwables are handled separately above with a generic message.
        if (self::isProduction()) {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            ini_set('log_errors', '1');
        }
    }

    public static function isProduction()
    {
        $env = getenv('APP_ENV') ?: 'production';
        return strtolower($env) !== 'development';
    }

    public static function log(\Throwable $e)
    {
        error_log(sprintf(
            '[%s] %s: %s in %s:%d',
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    public static function handleException(\Throwable $e)
    {
        self::log($e);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::isProduction()) {
            echo 'Ocurrio un error inesperado. Intenta nuevamente en unos minutos.';
        } else {
            echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }
}
