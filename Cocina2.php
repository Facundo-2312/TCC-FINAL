<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '3', '4']);
$soloLectura = (string) ($_SESSION['Rol'] ?? '') === '3';
$pedidoController = new App\Controllers\PedidoController();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $esJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    $mensaje = '';
    $codigo = 200;
    if ($soloLectura) {
        $codigo = 403;
        $mensaje = 'Tu rol solo permite consultar los pedidos.';
    } elseif (!App\Support\Csrf::verify($_POST['_csrf'] ?? '')) {
        App\Support\SecurityLog::log('csrf_rechazado', array('uri' => $_SERVER['REQUEST_URI'] ?? ''));
        $codigo = 403;
        $mensaje = 'El formulario expiro. Recarga la pagina antes de intentar nuevamente.';
    } else {
        try {
            $ok = $pedidoController->cambiarEstadoCocina(
                (int) ($_POST['id_pedido'] ?? 0),
                (string) ($_POST['estado_anterior'] ?? ''),
                (string) ($_POST['estado'] ?? '')
            );
            $codigo = $ok ? 200 : 409;
            $mensaje = $ok ? 'Pedido actualizado correctamente.' : 'El pedido cambio en otra pantalla o la accion no es valida. Revisa su estado actual.';
        } catch (Throwable $exception) {
            App\Support\ErrorHandler::log($exception);
            $codigo = 500;
            $mensaje = 'No se pudo actualizar el pedido. Intenta nuevamente.';
        }
    }
    if ($esJson) {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array('ok' => $codigo === 200, 'message' => $mensaje), JSON_UNESCAPED_UNICODE);
        exit;
    }
    app_set_flash($codigo === 200 ? 'success' : 'error', $mensaje);
    app_redirect('Cocina2.php');
}

$vistaCocina = $pedidoController->listarCocina();
$resumen = $vistaCocina['resumen'];
if (isset($_GET['actualizar'])) {
    $tarjetas = array();
    foreach ($vistaCocina['pedidos'] as $pedido) {
        ob_start();
        require __DIR__ . '/src/Views/cocina_pedido.php';
        $tarjetas[] = array('id_pedido' => (int) $pedido['id_pedido'], 'estado' => $pedido['estado'], 'html' => ob_get_clean());
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('resumen' => $resumen, 'tarjetas' => $tarjetas), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
$flash = app_get_flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel de Cocina</title>
<script src="<?php echo h(app_url('no-popups.js')); ?>"></script>
<style>
:root{--bg:#0d0f14;--panel:#161922;--soft:#1d212c;--accent:#ff2d6f;--text:#f3f4f8;--muted:#a9adba;--line:rgba(255,255,255,.1)}
*{box-sizing:border-box}
body{margin:0;padding:24px;background:radial-gradient(ellipse at top left,rgba(255,45,111,.13),transparent 45%),var(--bg);color:var(--text);font-family:'Segoe UI',Arial,sans-serif}
main{max-width:1540px;margin:auto}
.top,.order-head,.column-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.btn{display:inline-flex;padding:10px 16px;border-radius:10px;text-decoration:none;font-weight:700;color:var(--text);background:var(--soft);border:1px solid var(--line)}
.logout{background:var(--accent);border-color:var(--accent)}
h1{margin:28px 0 8px;font-size:clamp(1.8rem,3vw,2.6rem)}
.subtitle{color:var(--muted);line-height:1.5;margin:0 0 24px}
.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:26px}
.metric{padding:20px;border:1px solid var(--line);border-radius:14px;background:linear-gradient(160deg,var(--panel),var(--soft))}
.metric p{margin:0;color:var(--muted);font-size:.88rem}.metric strong{display:block;font-size:2rem;margin:8px 0}.metric small{color:var(--muted)}
.board{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;align-items:start}
.column{min-width:0;background:rgba(22,25,34,.65);border:1px solid var(--line);border-radius:16px;padding:16px}
.column-head{margin-bottom:6px}.column h2{font-size:1.05rem;margin:0}.column-head span{padding:4px 10px;border-radius:8px;background:var(--soft);font-weight:700}
.column-note{font-size:.8rem;color:var(--muted);margin:0 0 18px}
.order{border:1px solid var(--line);border-top:3px solid #f5a623;border-radius:12px;background:linear-gradient(160deg,var(--panel),var(--soft));padding:18px;margin-bottom:16px}
.order.preparando{border-top-color:#619bff}.order.entregado{border-top-color:#2fbf71}
h3{font-size:1rem;margin:0}.badge{font-size:.72rem;font-weight:700;border-radius:99px;padding:5px 9px;white-space:nowrap}
.badge.pendiente{background:rgba(245,166,35,.15);color:#ffd187}.badge.preparando{background:rgba(59,130,246,.17);color:#a7c7ff}.badge.entregado{background:rgba(47,191,113,.15);color:#7ee2a8}
.mesa{font-weight:700;margin:14px 0 6px}
.order-meta,.order-note{color:var(--muted);font-size:.8rem;line-height:1.5}
.waiting{color:#ffd187}.products{padding:0;list-style:none;margin:16px 0}.products li{padding:10px 0;border-top:1px solid var(--line);line-height:1.45}
.quantity{color:#ff8eb1}.customization{margin:6px 0 0 22px;color:#ffd187;font-size:.85rem}
.kitchen-notes{border:1px solid rgba(245,166,35,.25);border-radius:10px;padding:12px;background:rgba(245,166,35,.06);font-size:.85rem;overflow-wrap:anywhere}
.kitchen-notes strong{color:#ffd187}.kitchen-notes p{margin:6px 0 0;white-space:normal}
.kitchen-action{display:flex;gap:10px;flex-wrap:wrap}
button{font:inherit;font-weight:700;cursor:pointer;border:1px solid var(--line);border-radius:9px;padding:11px 12px;width:100%}
.action-btn{background:var(--accent);color:white}.action-btn.secondary,.archive-btn{background:transparent;color:var(--muted)}.archive-btn{font-size:.8rem}
button:hover{filter:brightness(1.15)}button:disabled{opacity:.5;cursor:wait}
button:focus-visible,a:focus-visible{outline:2px solid #ff8eb1;outline-offset:3px}
.empty{color:var(--muted);padding:24px 12px;text-align:center;border:1px dashed var(--line);border-radius:10px;font-size:.85rem}
.flash{padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--soft)}
#kitchen-status{min-height:22px;color:var(--muted);font-size:.85rem}#kitchen-message{color:#ffd187;line-height:1.5}
@media(max-width:1100px){.summary{grid-template-columns:repeat(2,minmax(0,1fr))}.board{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){body{padding:16px}.board{grid-template-columns:1fr}.metric{padding:16px}.metric strong{font-size:1.6rem}}
@media(max-width:380px){.summary{grid-template-columns:1fr}}
</style>
</head>
<body>
<main>
<nav class="top" aria-label="Navegacion"><a class="btn" href="<?php echo h(app_url('inicio_rol.php')); ?>">← Volver</a><a class="btn logout" href="<?php echo h(app_url('Salir.php')); ?>">Salir</a></nav>
<h1>Panel de Cocina</h1>
<p class="subtitle">Preparacion y entrega de pedidos · <?php echo date('d/m/Y'); ?> · Actualizacion automatica cada 5 segundos<?php echo $soloLectura ? ' · Modo solo lectura' : ''; ?></p>
<?php if ($flash) { ?><p class="flash" role="status"><?php echo h($flash['message']); ?></p><?php } ?>
<div class="summary">
    <div class="metric"><p>En cocina</p><strong id="kitchen-active"><?php echo $resumen['Pendiente'] + $resumen['Preparando']; ?></strong><small>Pendientes y en preparacion</small></div>
    <div class="metric"><p>Pendientes</p><strong id="kitchen-Pendiente"><?php echo $resumen['Pendiente']; ?></strong><small>Por comenzar</small></div>
    <div class="metric"><p>Preparando</p><strong id="kitchen-Preparando"><?php echo $resumen['Preparando']; ?></strong><small>En elaboracion</small></div>
    <div class="metric"><p>Entregados</p><strong id="kitchen-Entregado"><?php echo $resumen['Entregado']; ?></strong><small>Visibles hasta retirarlos del panel</small></div>
</div>
<p id="kitchen-status" role="status">Conectado al panel de pedidos.</p>
<p id="kitchen-message" role="status" aria-live="polite"></p>
<div class="board" id="kitchen-board">
<?php
$columnas = array('Pendiente' => 'Pendientes', 'Preparando' => 'Preparando', 'Entregado' => 'Entregados');
foreach ($columnas as $estadoColumna => $titulo) {
?>
<section class="column" aria-labelledby="title-<?php echo $estadoColumna; ?>">
    <div class="column-head"><h2 id="title-<?php echo $estadoColumna; ?>"><?php echo $titulo; ?></h2><span id="count-<?php echo $estadoColumna; ?>"><?php echo $resumen[$estadoColumna]; ?></span></div>
    <p class="column-note">Pedidos mas antiguos primero</p>
    <div id="orders-<?php echo $estadoColumna; ?>">
    <?php foreach ($vistaCocina['pedidos'] as $pedido) {
        if ($pedido['estado'] === $estadoColumna) require __DIR__ . '/src/Views/cocina_pedido.php';
    } ?>
    <p class="empty" id="empty-<?php echo $estadoColumna; ?>"<?php echo $resumen[$estadoColumna] ? ' hidden' : ''; ?>>No hay pedidos en este estado.</p>
    </div>
</section>
<?php } ?>
</div>
</main>
<script>
const board = document.getElementById('kitchen-board');
const statusEl = document.getElementById('kitchen-status');
const messageEl = document.getElementById('kitchen-message');
const estados = ['Pendiente', 'Preparando', 'Entregado'];
const htmlAnterior = new Map();
let actualizando = false;
let enviando = false;
async function actualizarCocina() {
    if (actualizando || enviando || document.hidden) return;
    actualizando = true;
    try {
        const response = await fetch(<?php echo json_encode(app_url('Cocina2.php?actualizar=1')); ?>, {cache: 'no-store', credentials: 'same-origin'});
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (!Array.isArray(data.tarjetas) || !data.resumen) throw new Error('Respuesta invalida');
        // A response started before a click must not replace its form while the POST is pending.
        if (enviando) return;
        const ids = new Set(data.tarjetas.map(tarjeta => String(tarjeta.id_pedido)));
        board.querySelectorAll('[data-order-id]').forEach(card => {
            if (!ids.has(card.dataset.orderId)) { htmlAnterior.delete(card.dataset.orderId); card.remove(); }
        });
        data.tarjetas.forEach(tarjeta => {
            if (!estados.includes(tarjeta.estado)) throw new Error('Estado invalido');
            const id = String(Number(tarjeta.id_pedido));
            let card = board.querySelector('[data-order-id="' + id + '"]');
            const column = document.getElementById('orders-' + tarjeta.estado);
            const empty = document.getElementById('empty-' + tarjeta.estado);
            if (!card || card.dataset.state !== tarjeta.estado || (!card.contains(document.activeElement) && htmlAnterior.get(id) !== tarjeta.html)) {
                const template = document.createElement('template');
                template.innerHTML = tarjeta.html.trim();
                const nueva = template.content.firstElementChild;
                if (!nueva || nueva.dataset.orderId !== id) throw new Error('Pedido invalido');
                if (card) card.replaceWith(nueva);
                card = nueva;
                htmlAnterior.set(id, tarjeta.html);
            }
            if (card.parentElement !== column || !board.contains(document.activeElement)) column.insertBefore(card, empty);
            else if (!card.parentElement) column.insertBefore(card, empty);
        });
        estados.forEach(estado => {
            document.getElementById('kitchen-' + estado).textContent = data.resumen[estado];
            document.getElementById('count-' + estado).textContent = data.resumen[estado];
            document.getElementById('empty-' + estado).hidden = data.resumen[estado] > 0;
        });
        document.getElementById('kitchen-active').textContent = Number(data.resumen.Pendiente) + Number(data.resumen.Preparando);
        statusEl.textContent = 'Actualizado a las ' + new Date().toLocaleTimeString('es-UY');
    } catch (error) {
        statusEl.textContent = 'No se pudo actualizar Cocina. Se reintentara automaticamente.';
    } finally {
        actualizando = false;
    }
}
board.addEventListener('submit', async event => {
    const form = event.target.closest('.kitchen-action');
    if (!form) return;
    event.preventDefault();
    if (enviando) return;
    const button = event.submitter;
    if (!button) { messageEl.textContent = 'Selecciona una accion para el pedido.'; return; }
    const datos = new FormData(form);
    datos.set(button.name, button.value);
    enviando = true;
    board.querySelectorAll('button').forEach(item => { item.disabled = true; });
    messageEl.textContent = 'Actualizando pedido...';
    try {
        const response = await fetch(form.action, {method: 'POST', body: datos, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
        const data = await response.json();
        if (typeof data.message !== 'string') throw new Error('Respuesta invalida');
        messageEl.textContent = data.message;
        if (response.ok && data.ok && document.activeElement && form.contains(document.activeElement)) document.activeElement.blur();
    } catch (error) {
        messageEl.textContent = 'No se pudo confirmar el cambio. Revisa el estado actualizado antes de reintentar.';
    } finally {
        enviando = false;
        board.querySelectorAll('button').forEach(item => { item.disabled = false; });
        await actualizarCocina();
    }
});
document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizarCocina(); });
setInterval(actualizarCocina, 5000);
</script>
</body>
</html>
