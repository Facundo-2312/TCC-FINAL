<?php
require_once __DIR__ . '/app_bootstrap.php';
app_start_session();

function registro_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die('registro_cliente.php');

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion'] ?? '');

    if ($nombre === '' || strlen($nombre) > 100 || !preg_match('/^[\p{L} .\'-]+$/u', $nombre)) {
        $mensaje = 'Ingresa un nombre valido.';
        $tipoMensaje = 'error';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario)) {
        $mensaje = 'El usuario debe tener entre 3 y 50 caracteres y solo puede usar letras, numeros, punto, guion o guion bajo.';
        $tipoMensaje = 'error';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirmacion) {
        $mensaje = 'Las contrasenas deben coincidir y tener entre 8 y 72 caracteres.';
        $tipoMensaje = 'error';
    } else {
        $resultado = (new App\Repositories\UsuarioRepository())->crearCliente($nombre, $usuario, $password);
        if ($resultado['ok']) {
            app_set_flash('success', 'Registro creado. Ya puedes iniciar sesion como cliente.');
            app_redirect('Login.php');
        }
        $mensaje = $resultado['error'];
        $tipoMensaje = 'error';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro de cliente</title>
<style>
:root{--bg:#171717;--panel:#242424;--accent:#ff0055;--text:#f3f3f3;--muted:#bdbdbd;--line:rgba(255,255,255,.1)}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:radial-gradient(circle at top left,rgba(255,0,85,.18),transparent 35%),var(--bg);font-family:'Segoe UI',Arial,sans-serif;color:var(--text)}.card{width:min(460px,100%);padding:28px;border:1px solid var(--line);border-radius:14px;background:linear-gradient(180deg,var(--panel),#2c2c2c);box-shadow:0 20px 45px rgba(0,0,0,.3)}h1{margin:0 0 8px;font-size:1.8rem}.intro{margin:0 0 22px;color:var(--muted)}label{display:block;margin:14px 0 6px;color:var(--muted);font-size:.9rem}input{width:100%;padding:12px;border:1px solid #4a4a4a;border-radius:7px;background:#111;color:#fff;font:inherit}.btn{display:block;width:100%;margin-top:22px;padding:12px;border:0;border-radius:7px;background:var(--accent);color:#fff;font-weight:700;font-size:1rem;cursor:pointer}.back{display:block;margin-top:14px;color:#ff9aba;text-align:center;text-decoration:none}.alert{margin-bottom:16px;padding:12px;border:1px solid rgba(220,38,38,.5);border-radius:7px;background:rgba(220,38,38,.16);color:#fecaca}.hint{margin-top:12px;color:var(--muted);font-size:.82rem;line-height:1.4}
</style>
</head>
<body><main class="card"><h1>Crear cuenta de cliente</h1><p class="intro">Registra tus datos para consultar el menu y realizar pedidos.</p><?php if ($mensaje !== '') { ?><div class="alert <?php echo registro_h($tipoMensaje); ?>"><?php echo registro_h($mensaje); ?></div><?php } ?><form method="post"><?php echo csrf_field(); ?><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" maxlength="100" required value="<?php echo registro_h($_POST['nombre'] ?? ''); ?>"><label for="usuario">Usuario</label><input id="usuario" name="usuario" maxlength="50" required autocomplete="username" value="<?php echo registro_h($_POST['usuario'] ?? ''); ?>"><label for="password">Contrasena</label><input id="password" type="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password"><label for="confirmacion">Repetir contrasena</label><input id="confirmacion" type="password" name="confirmacion" minlength="8" maxlength="72" required autocomplete="new-password"><button class="btn" type="submit">Registrarme</button></form><a class="back" href="<?php echo registro_h(app_url('Login.php')); ?>">Ya tengo una cuenta</a><p class="hint">La cuenta se crea con permisos exclusivos para consultar productos y enviar pedidos.</p></main></body>
</html>