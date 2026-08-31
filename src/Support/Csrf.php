<?php

namespace App\Support;

// Synchronizer-token CSRF protection (token stored in session, verified with constant-time compare).
class Csrf
{
    const SESSION_KEY = '_csrf_token';
    const FIELD_NAME = '_csrf';

    public static function token()
    {
        Auth::startSession();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field()
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }

    public static function verify($token)
    {
        Auth::startSession();
        $expected = isset($_SESSION[self::SESSION_KEY]) ? $_SESSION[self::SESSION_KEY] : null;

        return is_string($token) && $token !== '' && $expected !== null && hash_equals($expected, $token);
    }

    // Verifies $_POST['_csrf'] and stops the request (with a friendly redirect) when invalid.
    public static function requireValidPost($redirectTo = null)
    {
        $token = isset($_POST[self::FIELD_NAME]) ? $_POST[self::FIELD_NAME] : '';

        if (self::verify($token)) {
            return;
        }

        SecurityLog::log('csrf_rechazado', array('uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''));

        if ($redirectTo !== null) {
            Auth::setFlash('error', 'Tu sesion de formulario expiro. Intenta nuevamente.');
            Url::redirect($redirectTo);
        }

        http_response_code(403);
        echo 'Solicitud invalida (token de seguridad ausente o expirado).';
        exit;
    }
}
