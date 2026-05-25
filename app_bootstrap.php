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
        header('Location: ' . $path);
        exit();
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
