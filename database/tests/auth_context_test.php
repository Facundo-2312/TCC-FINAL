<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../app_bootstrap.php';

use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Url;

set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, (string) $exception . PHP_EOL);
    exit(1);
});

function checkContext($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function tabRequest($context, $post = false)
{
    session_write_close();
    $_GET = $post || $context === null ? array() : array('ctx' => $context);
    $_POST = $post ? array('ctx' => $context) : array();
    Auth::startSession();
}

ob_start();
$_GET = $_POST = array();
$_SERVER['HTTP_USER_AGENT'] = 'auth-context-regression';
Auth::startSession();

try {
    $contexts = $tokens = array();
    for ($role = 1; $role <= 5; $role++) {
        tabRequest(null);
        checkContext(!isset($_SESSION['Usuario'], $_SESSION['Rol']), 'Una pestana nueva hereda un rol.');
        $contexts[$role] = Auth::createContext();
        $_SESSION['Usuario'] = 'test_role_' . $role;
        $_SESSION['Rol'] = $role;
        $_SESSION['CI'] = $role;
        $_SESSION['ClienteNombre'] = 'test_name_' . $role;
        $_SESSION['cliente_pedido_token'] = 'test_order_' . $role;
        $tokens[$role] = Csrf::token();
        Auth::setFlash('success', 'test_flash_' . $role);
    }

    foreach (array(5, 1, 4, 2, 3, 1, 5) as $role) {
        tabRequest($contexts[$role], $role % 2 === 0);
        checkContext(Auth::role() === (string) $role && Auth::username() === 'test_role_' . $role,
            'Se mezclan identidades entre pestanas.');
        checkContext($_SESSION['CI'] === $role && $_SESSION['ClienteNombre'] === 'test_name_' . $role
            && $_SESSION['cliente_pedido_token'] === 'test_order_' . $role, 'Se mezclan datos del cliente.');
        checkContext(Csrf::verify($tokens[$role]) && !Csrf::verify($tokens[$role === 5 ? 1 : 5]),
            'El token CSRF no esta aislado por pestana.');
        checkContext(strpos(Url::to('cliente.php?mesa=1#reservar'), 'ctx=' . $contexts[$role] . '#reservar') !== false,
            'La navegacion pierde el contexto.');
    }

    tabRequest($contexts[1]);
    $newContext = Auth::createContext();
    checkContext(Auth::contextId() === $newContext && !isset($_SESSION['Usuario']),
        'createContext se deshace al volver a iniciar la sesion con el ctx de la URL.');
    Auth::setFlash('warning', 'new_login');
    checkContext(Auth::getFlash()['message'] === 'new_login' && Auth::contextId() === $newContext,
        'El formulario de login vuelve al contexto anterior.');
    tabRequest($contexts[1]);
    checkContext(Auth::role() === '1', 'Abrir otro login cierra la pestana anterior.');

    tabRequest('invalid');
    checkContext(Auth::role() === null && Auth::contextId() === '', 'Un ctx invalido hereda el ultimo rol.');
    tabRequest(null);
    checkContext(Auth::role() === null, 'Una URL sin ctx hereda el ultimo rol.');

    // Same state transition as logout(), whose redirect terminates the request.
    tabRequest($contexts[2]);
    unset($_SESSION['_contexts'][$contexts[2]], $_SESSION['_context_activity'][$contexts[2]]);
    foreach (array('Usuario', 'Rol', 'CI', 'ClienteNombre', 'cliente_pedido_token', '_csrf_token', '_active_context') as $key) {
        unset($_SESSION[$key]);
    }
    checkContext(Auth::contextId() === '' && Auth::role() === null, 'Se reactiva el contexto despues del cierre.');
    tabRequest($contexts[2]);
    checkContext(Auth::role() === null, 'Se recupera una sesion cerrada.');
    tabRequest($contexts[1]);
    checkContext(Auth::role() === '1' && Auth::getFlash()['message'] === 'test_flash_1',
        'Cerrar una pestana afecta al rol o los mensajes de otra.');
} finally {
    session_destroy();
    ob_end_clean();
}

echo 'OK: cinco roles con una cookie, GET/POST, CSRF, nuevo login y cierre aislados.' . PHP_EOL;
