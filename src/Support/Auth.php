<?php

namespace App\Support;

// Centralized session/authentication/authorization. Same behavior as the previous app_* session functions.
class Auth
{
    private static $contextKeys = array('Usuario', 'Rol', 'CI', 'ClienteNombre', 'cliente_pedido_token', '_csrf_token');

    public static function startSession()
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

        self::activateContext();
        $_SESSION['_last_activity'] = $now;

        if (!isset($_SESSION['_last_regen'])) {
            $_SESSION['_last_regen'] = $now;
        } elseif (($now - (int) $_SESSION['_last_regen']) > 900) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = $now;
        }

        self::guardAgainstHijacking();
    }

    private static function activateContext()
    {
        $requested = trim((string) ($_GET['ctx'] ?? $_POST['ctx'] ?? ''));
        if ($requested === '' || !preg_match('/^[a-f0-9]{64}$/', $requested)) {
            return;
        }

        if (!isset($_SESSION['_contexts']) || !is_array($_SESSION['_contexts'])) {
            $_SESSION['_contexts'] = array();
        }

        $active = (string) ($_SESSION['_active_context'] ?? '');
        if ($active === $requested) {
            return;
        }

        if ($active !== '') {
            $_SESSION['_contexts'][$active] = self::captureContext();
        }

        $context = $_SESSION['_contexts'][$requested] ?? array();
        foreach (self::$contextKeys as $key) {
            if (array_key_exists($key, $context)) $_SESSION[$key] = $context[$key];
            else unset($_SESSION[$key]);
        }
        $_SESSION['_active_context'] = $requested;
    }

    private static function captureContext()
    {
        $context = array();
        foreach (self::$contextKeys as $key) {
            if (array_key_exists($key, $_SESSION)) $context[$key] = $_SESSION[$key];
        }
        return $context;
    }

    public static function createContext()
    {
        self::startSession();
        $contextId = bin2hex(random_bytes(32));
        if (!isset($_SESSION['_contexts']) || !is_array($_SESSION['_contexts'])) $_SESSION['_contexts'] = array();
        $active = (string) ($_SESSION['_active_context'] ?? '');
        if ($active !== '') $_SESSION['_contexts'][$active] = self::captureContext();
        foreach (self::$contextKeys as $key) unset($_SESSION[$key]);
        $_SESSION['_active_context'] = $contextId;
        $_SESSION['_contexts'][$contextId] = array();
        return $contextId;
    }

    public static function contextId()
    {
        self::startSession();
        return (string) ($_SESSION['_active_context'] ?? '');
    }

    // Binds an authenticated session to a hash of the User-Agent. If a stolen session cookie is
    // replayed from a different client, the fingerprint mismatches and the session is destroyed.
    private static function guardAgainstHijacking()
    {
        if (!isset($_SESSION['Usuario'])) {
            return;
        }

        $huella = hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');

        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $huella;
            return;
        }

        if (!hash_equals($_SESSION['_fingerprint'], $huella)) {
            SecurityLog::log('sesion_secuestro_sospechoso', array('usuario' => $_SESSION['Usuario']));

            $_SESSION = array();
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            session_start();
            $_SESSION['_flash'] = array(
                'type' => 'warning',
                'message' => 'Tu sesion se cerro por un motivo de seguridad. Inicia sesion nuevamente.'
            );
        }
    }

    public static function setFlash($type, $message)
    {
        self::startSession();
        $_SESSION['_flash'] = array(
            'type' => (string) $type,
            'message' => (string) $message
        );
    }

    public static function getFlash()
    {
        self::startSession();
        $flash = isset($_SESSION['_flash']) ? $_SESSION['_flash'] : null;
        unset($_SESSION['_flash']);
        return $flash;
    }

    public static function requireLogin($loginPath = 'Login.php', $roles = null)
    {
        self::startSession();

        if (!isset($_SESSION['Usuario']) || !isset($_SESSION['Rol'])) {
            self::setFlash('warning', 'Debes iniciar sesion para continuar.');
            Url::redirect($loginPath);
        }

        if (is_array($roles) && !in_array((string) $_SESSION['Rol'], array_map('strval', $roles), true)) {
            self::setFlash('error', 'No tienes permisos para acceder a esta seccion.');
            Url::redirect($loginPath);
        }
    }

    public static function logout($redirectPath = 'Login.php')
    {
        self::startSession();
        $active = (string) ($_SESSION['_active_context'] ?? '');
        if ($active !== '' && isset($_SESSION['_contexts'][$active])) unset($_SESSION['_contexts'][$active]);
        foreach (self::$contextKeys as $key) unset($_SESSION[$key]);
        unset($_SESSION['_active_context']);

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        $_SESSION['_flash'] = array('type' => 'success', 'message' => 'Sesion cerrada correctamente.');

        Url::redirect($redirectPath);
    }

    public static function role()
    {
        return isset($_SESSION['Rol']) ? (string) $_SESSION['Rol'] : null;
    }

    public static function username()
    {
        return isset($_SESSION['Usuario']) ? $_SESSION['Usuario'] : null;
    }
}
