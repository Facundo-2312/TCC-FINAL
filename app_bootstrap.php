<?php

if (!function_exists('app_start_session')) {
    function app_start_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            session_set_cookie_params(array(
                'lifetime' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ));

            session_start();
        }

        $now = time();
        $timeout = 7200;

        if (isset($_SESSION['_last_activity']) && ($now - (int) $_SESSION['_last_activity']) > $timeout) {
            $_SESSION = array();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }

            session_destroy();
            session_start();
            $_SESSION['_flash'] = array(
                'type' => 'warning',
                'message' => 'Tu sesion expiro por inactividad. Inicia sesion nuevamente.'
            );
        }

        $_SESSION['_last_activity'] = $now;

        if (!isset($_SESSION['_last_regen'])) {
            $_SESSION['_last_regen'] = $now;
        } elseif (($now - (int) $_SESSION['_last_regen']) > 900) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = $now;
        }
    }
}

if (!function_exists('app_set_flash')) {
    function app_set_flash($type, $message)
    {
        app_start_session();
        $_SESSION['_flash'] = array(
            'type' => (string) $type,
            'message' => (string) $message
        );
    }
}

if (!function_exists('app_get_flash')) {
    function app_get_flash()
    {
        app_start_session();
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return $flash;
    }
}

if (!function_exists('app_redirect')) {
    function app_redirect($path)
    {
        header('Location: ' . app_url($path));
        exit();
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path()
    {
        static $basePath = null;

        if ($basePath !== null) {
            return $basePath;
        }

        $configuredPath = getenv('APP_BASE_PATH');
        if (is_string($configuredPath) && $configuredPath !== '') {
            $trimmedPath = trim(str_replace('\\', '/', $configuredPath));
            $basePath = $trimmedPath === '/' ? '' : '/' . trim($trimmedPath, '/');
            return $basePath;
        }

        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $appRoot = realpath(__DIR__);

        if ($documentRoot && $appRoot) {
            $documentRoot = str_replace('\\', '/', $documentRoot);
            $appRoot = str_replace('\\', '/', $appRoot);

            if (strpos($appRoot, $documentRoot) === 0) {
                $relativePath = trim(substr($appRoot, strlen($documentRoot)), '/');
                $basePath = $relativePath === '' ? '' : '/' . $relativePath;
                return $basePath;
            }
        }

        $basePath = '';
        return $basePath;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        $path = (string) $path;

        if ($path === '') {
            return app_base_path() !== '' ? app_base_path() : '/';
        }

        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $path) === 1) {
            return $path;
        }

        if ($path[0] === '/') {
            return $path;
        }

        $basePath = app_base_path();
        return ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_db_config')) {
    function app_db_config($overrides = array())
    {
        $config = array(
            'host' => getenv('APP_DB_HOST') ?: 'localhost',
            'user' => getenv('APP_DB_USER') ?: 'root',
            'password' => getenv('APP_DB_PASSWORD') ?: '',
            'database' => getenv('APP_DB_NAME') ?: 'ProyectoMagnus',
            'charset' => getenv('APP_DB_CHARSET') ?: 'utf8mb4',
        );

        foreach ((array) $overrides as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $config[$key] = $value;
        }

        return $config;
    }
}

if (!function_exists('app_db_connect')) {
    function app_db_connect($overrides = array())
    {
        $config = app_db_config($overrides);
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

if (!function_exists('app_require_login')) {
    function app_require_login($loginPath = 'Login.php', $roles = null)
    {
        app_start_session();

        if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
            app_set_flash('warning', 'Debes iniciar sesion para continuar.');
            app_redirect($loginPath);
        }

        if (is_array($roles) && !in_array((string) $_SESSION['Rol'], array_map('strval', $roles), true)) {
            app_set_flash('error', 'No tienes permisos para acceder a esta seccion.');
            app_redirect($loginPath);
        }
    }
}

if (!function_exists('app_logout')) {
    function app_logout($redirectPath = 'Login.php')
    {
        app_start_session();
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        session_start();
        $_SESSION['_flash'] = array(
            'type' => 'success',
            'message' => 'Sesion cerrada correctamente.'
        );

        app_redirect($redirectPath);
    }
}
