<?php

// Bootstraps the App\ autoloader/error handler once, then exposes the legacy app_* procedural
// API as thin facades over App\Support\{Auth,Url,Database,ErrorHandler}. Existing pages that call
// app_start_session()/app_db_connect()/etc. keep working unchanged; new code should prefer the
// App\Support classes (and App\Controllers/Services/Repositories) directly.
require_once __DIR__ . '/src/Support/Autoload.php';

App\Support\Env::load(__DIR__ . '/.env');
App\Support\ErrorHandler::register();

if (!function_exists('app_start_session')) {
    function app_start_session()
    {
        App\Support\Auth::startSession();
    }
}

if (!function_exists('csrf_field')) {
    // Echoes a hidden <input> with a per-session CSRF token. Use inside any state-changing <form>.
    function csrf_field()
    {
        return App\Support\Csrf::field();
    }
}

if (!function_exists('csrf_verify_or_die')) {
    // Verifies $_POST['_csrf'] for the current request; redirects (or 403s) when invalid/missing.
    function csrf_verify_or_die($redirectTo = null)
    {
        App\Support\Csrf::requireValidPost($redirectTo);
    }
}

if (!function_exists('app_set_flash')) {
    function app_set_flash($type, $message)
    {
        App\Support\Auth::setFlash($type, $message);
    }
}

if (!function_exists('app_get_flash')) {
    function app_get_flash()
    {
        return App\Support\Auth::getFlash();
    }
}

if (!function_exists('app_redirect')) {
    function app_redirect($path)
    {
        App\Support\Url::redirect($path);
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path()
    {
        return App\Support\Url::basePath();
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        return App\Support\Url::to($path);
    }
}

if (!function_exists('app_db_config')) {
    function app_db_config($overrides = array())
    {
        return App\Support\Database::config((array) $overrides);
    }
}

if (!function_exists('app_db_connect')) {
    function app_db_connect($overrides = array())
    {
        return App\Support\Database::connect((array) $overrides);
    }
}

if (!function_exists('app_require_login')) {
    function app_require_login($loginPath = 'Login.php', $roles = null)
    {
        App\Support\Auth::requireLogin($loginPath, $roles);
    }
}

if (!function_exists('app_logout')) {
    function app_logout($redirectPath = 'Login.php')
    {
        App\Support\Auth::logout($redirectPath);
    }
}
