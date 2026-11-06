<?php
require_once __DIR__ . '/app_bootstrap.php';
app_start_session();

if (isset($_SESSION['Usuario'], $_SESSION['Rol']) && (string) $_SESSION['Rol'] === '5') {
    app_redirect('cliente.php');
}

$contextoCliente = (string) ($_GET['ctx'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && (!preg_match('/^[a-f0-9]{64}$/', $contextoCliente) || isset($_SESSION['Usuario'], $_SESSION['Rol']))) {
    App\Support\Auth::createContext();
    app_redirect('cliente_publico.php');
}

if (isset($_SESSION['Usuario'], $_SESSION['Rol'])) {
    app_set_flash('warning', 'Abre el acceso de clientes para iniciar un pedido.');
    app_redirect('cliente_publico.php');
}

$flash = app_get_flash();
$error = '';
$loginCssVersion = @filemtime(__DIR__ . '/estilos/login.css') ?: time();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die('cliente_publico.php');
    $nombre = trim((string) ($_POST['nombre_cliente'] ?? ''));
    if ($nombre === '' || strlen($nombre) > 100 || !preg_match('/^[\p{L} .\'-]+$/u', $nombre)) {
        $error = 'Ingresa un nombre valido para continuar.';
    } else {
        $usuario = (new App\Repositories\UsuarioRepository())->crearClienteInvitado($nombre);
        if ($usuario === null) {
            $error = 'No se pudo iniciar el acceso de cliente. Intenta nuevamente.';
        } else {
            App\Support\Auth::createContext();
            if (!App\Support\Auth::hasOtherContext()) session_regenerate_id(true);
            $_SESSION['Usuario'] = $usuario;
            $_SESSION['Rol'] = 5;
            $_SESSION['CI'] = null;
            $_SESSION['ClienteNombre'] = $nombre;
            $_SESSION['ClientePublico'] = true;
            app_redirect('cliente.php');
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menú de Restaurante-UY</title>
    <link rel="icon" type="image/jpeg" href="<?php echo htmlspecialchars(app_url('img/logonuevo.jpeg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_url('estilos/login.css?v=' . $loginCssVersion), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
<header><h1>Restaurante-UY · Menú para clientes</h1></header>
<main class="conteiner">
    <section class="login-container">
        <img src="<?php echo htmlspecialchars(app_url('img/logonuevo.jpeg'), ENT_QUOTES, 'UTF-8'); ?>" alt="Restaurante-UY" style="height:95px;width:95px;border-radius:100px">
        <h2>HACER UN PEDIDO</h2>
        <p>Continúa como cliente invitado. No necesitas una cuenta del sistema.</p>
        <?php if ($error !== '') { ?><p class="flash flash-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
        <?php if (!empty($flash['message'])) { ?><p class="flash flash-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
        <form method="post" action="<?php echo htmlspecialchars(app_url('cliente_publico.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_field(); ?>
            <label for="nombre-cliente">Tu nombre</label>
            <p><input id="nombre-cliente" type="text" name="nombre_cliente" maxlength="100" required autocomplete="name" placeholder="Digite su nombre"></p>
            <p><input type="submit" value="Ver menú y pedir"></p>
        </form>
    </section>
</main>
</body>
</html>
