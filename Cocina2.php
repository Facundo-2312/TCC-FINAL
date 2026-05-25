<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php');

$conexion = app_db_connect();
if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}

$toastMessage = '';
$toastKind = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $toastMessage = 'Pedido eliminado';
    $toastKind = 'success';
}
if (isset($_GET['msg']) && $_GET['msg'] === 'hidden') {
    $toastMessage = 'Pedido retirado de cocina';
    $toastKind = 'success';
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function estadoClass($estado)
{
    $e = strtolower((string) $estado);
    if ($e === 'entregado') return 'entregado';
    if ($e === 'preparando') return 'preparando';
    if ($e === 'cancelado') return 'cancelado';
    return 'pendiente';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_estado'])) {
    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $nuevoEstado = trim($_POST['estado'] ?? '');
    $permitidos = ['Pendiente', 'Preparando', 'Entregado', 'Cancelado'];

    if ($idPedido > 0 && in_array($nuevoEstado, $permitidos, true)) {
        $stmtUpdate = mysqli_prepare($conexion, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmtUpdate, 'si', $nuevoEstado, $idPedido);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    app_redirect('Cocina2.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_eliminar'])) {
    $idPedidoEliminar = (int) ($_POST['id_pedido'] ?? 0);

    if ($idPedidoEliminar > 0) {
        $estadoOculto = 'ArchivadoCocina';
        $stmtHide = mysqli_prepare($conexion, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
        mysqli_stmt_bind_param($stmtHide, 'si', $estadoOculto, $idPedidoEliminar);
        mysqli_stmt_execute($stmtHide);
        mysqli_stmt_close($stmtHide);

        app_redirect('Cocina2.php?msg=hidden');
    }

    app_redirect('Cocina2.php');
}

$pedidos = [];
$stmtPedidos = mysqli_prepare(
    $conexion,
    "SELECT id_pedido, id_mesa, fecha, estado, total
     FROM pedidos
     WHERE estado IN ('Pendiente','Preparando','Entregado')
     ORDER BY FIELD(estado,'Pendiente','Preparando','Entregado'), fecha ASC"
);
mysqli_stmt_execute($stmtPedidos);
$resPedidos = mysqli_stmt_get_result($stmtPedidos);
while ($fila = mysqli_fetch_assoc($resPedidos)) {
    $pedidos[] = $fila;
}
mysqli_stmt_close($stmtPedidos);

$pendientes = 0;
$preparando = 0;
$entregados = 0;
$pedidosConDetalle = [];

foreach ($pedidos as $pedido) {
    if ($pedido['estado'] === 'Pendiente') $pendientes++;
    if ($pedido['estado'] === 'Preparando') $preparando++;
    if ($pedido['estado'] === 'Entregado') $entregados++;

    $idPedido = (int) $pedido['id_pedido'];
    $stmtDetalle = mysqli_prepare(
        $conexion,
        'SELECT dp.cantidad, pr.nombre
         FROM detalle_pedido dp
         INNER JOIN productos pr ON pr.id_producto = dp.id_producto
         WHERE dp.id_pedido = ?
         ORDER BY pr.nombre ASC'
    );
    mysqli_stmt_bind_param($stmtDetalle, 'i', $idPedido);
    mysqli_stmt_execute($stmtDetalle);
    $resDetalle = mysqli_stmt_get_result($stmtDetalle);

    $productos = [];
    while ($prod = mysqli_fetch_assoc($resDetalle)) {
        $productos[] = $prod;
    }
    mysqli_stmt_close($stmtDetalle);

    $pedido['productos'] = $productos;
    $pedidosConDetalle[] = $pedido;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cocina</title>
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<style>
:root{--bg:#171717;--panel:#242424;--panel-soft:#2c2c2c;--accent:#ff0055;--text:#f4f4f4;--muted:#bdbdbd;--line:rgba(255,255,255,.08)}
*{box-sizing:border-box}
body{margin:0;padding:20px;background:radial-gradient(circle at top left, rgba(255,0,85,.15), transparent 28%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;border:0;cursor:pointer}
.back{background:#3a3a3a;color:#fff}.salir{background:var(--accent);color:#fff}
h1{text-align:center;color:var(--accent);margin:14px 0 20px;font-size:clamp(1.8rem,3vw,2.6rem)}
.summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}
.card{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:14px;padding:14px}
.label{color:var(--muted);font-size:.9rem}.value{font-size:1.7rem;font-weight:800;margin-top:6px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,280px));gap:14px;justify-content:start;align-content:start}
.order{width:280px;max-width:280px;background:linear-gradient(180deg,#f9f9fb,#eeeeef);color:#1e1e1e;border-radius:14px;padding:12px;border:1px solid #e8e8ea;box-shadow:0 8px 18px rgba(0,0,0,.14);aspect-ratio:1/1;display:flex;flex-direction:column;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease}
.order:hover{transform:translateY(-2px);box-shadow:0 12px 22px rgba(0,0,0,.2)}
.order-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px}
.order-title{margin:0;font-size:1.15rem}
.meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 10px;font-size:.93rem}
.meta strong{color:#141414}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;color:#fff;font-size:.82rem;font-weight:700}
.badge.entregado{background:#1f9d55}.badge.preparando{background:#2563eb}.badge.cancelado{background:#dc2626}.badge.pendiente{background:#f59e0b}
ul{padding-left:18px;margin:8px 0 0;flex:1;overflow:auto}
.touch-hint{margin-top:8px;padding-top:8px;border-top:1px solid rgba(0,0,0,.08);font-size:.8rem;color:#525252}
.order-actions{display:flex;justify-content:flex-end;margin-top:8px}
.delete-btn{border:0;border-radius:8px;padding:7px 10px;background:#dc2626;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer}
.delete-btn:hover{filter:brightness(1.07)}
.mini-toast{position:fixed;right:20px;bottom:20px;background:#111;color:#fff;border:1px solid rgba(255,255,255,.2);padding:10px 14px;border-radius:10px;font-size:.9rem;font-weight:700;opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity .25s ease,transform .25s ease;z-index:9999;box-shadow:0 10px 20px rgba(0,0,0,.35)}
.mini-toast.success{background:#14532d;border-color:#1f7a43}
.mini-toast.show{opacity:1;transform:translateY(0)}
.empty{color:var(--muted)}
@media (max-width:900px){.summary{grid-template-columns:1fr}.grid{grid-template-columns:1fr;justify-items:start}.order{width:min(92vw,320px);max-width:min(92vw,320px)}.meta{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="top">
    <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
    <a class="btn salir" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">Salir</a>
</div>

<h1>Panel de Cocina</h1>

<div class="summary">
    <div class="card"><div class="label">Pendientes</div><div class="value"><?php echo $pendientes; ?></div></div>
    <div class="card"><div class="label">Preparando</div><div class="value"><?php echo $preparando; ?></div></div>
    <div class="card"><div class="label">Entregados</div><div class="value"><?php echo $entregados; ?></div></div>
</div>

<div class="grid">
<?php if (!empty($pedidosConDetalle)) { ?>
    <?php foreach ($pedidosConDetalle as $pedido) { ?>
        <form method="post" class="order js-touch-form" data-order-id="<?php echo (int) $pedido['id_pedido']; ?>" data-current="<?php echo h($pedido['estado']); ?>" tabindex="0" role="button" aria-label="Cambiar estado del pedido <?php echo (int) $pedido['id_pedido']; ?>">
            <input type="hidden" name="id_pedido" value="<?php echo (int) $pedido['id_pedido']; ?>">
            <input type="hidden" name="accion_estado" value="1">
            <input type="hidden" name="estado" class="js-next-state" value="<?php echo h($pedido['estado']); ?>">

            <div class="order-head">
                <h3 class="order-title">Pedido #<?php echo (int) $pedido['id_pedido']; ?></h3>
                <span class="badge <?php echo h(estadoClass($pedido['estado'])); ?>"><?php echo h($pedido['estado']); ?></span>
            </div>

            <div class="meta">
                <div><strong>Mesa:</strong> <?php echo (int) $pedido['id_mesa']; ?></div>
                <div><strong>Total:</strong> $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></div>
                <div style="grid-column:1 / -1"><strong>Fecha:</strong> <?php echo h($pedido['fecha']); ?></div>
            </div>

            <ul>
                <?php if (!empty($pedido['productos'])) { ?>
                    <?php foreach ($pedido['productos'] as $prod) { ?>
                        <li><?php echo (int) $prod['cantidad']; ?> x <?php echo h($prod['nombre']); ?></li>
                    <?php } ?>
                <?php } else { ?>
                    <li class="empty">Sin productos</li>
                <?php } ?>
            </ul>

            <div class="order-actions">
                <button type="button" class="delete-btn js-delete-btn" aria-label="Eliminar pedido">Eliminar</button>
            </div>

            <div class="touch-hint">Toca la tarjeta para cambiar estado</div>
        </form>

        <form method="post" class="js-delete-form" style="display:none">
            <input type="hidden" name="id_pedido" value="<?php echo (int) $pedido['id_pedido']; ?>">
            <input type="hidden" name="accion_eliminar" value="1">
        </form>
    <?php } ?>
<?php } else { ?>
    <div class="card empty">No hay pedidos para cocina.</div>
<?php } ?>
</div>

<div id="miniToast" class="mini-toast" data-init-message="<?php echo h($toastMessage); ?>" data-init-kind="<?php echo h($toastKind); ?>">Pedido entregado</div>

<script>
(function () {
    var nextByState = {
        'Pendiente': 'Preparando',
        'Preparando': 'Entregado',
        'Entregado': 'Pendiente'
    };

    var toastEl = document.getElementById('miniToast');
    var toastTimer = null;

    function showToast(text, kind) {
        if (!toastEl) return;
        toastEl.textContent = text;
        toastEl.classList.remove('success');
        if (kind === 'success') {
            toastEl.classList.add('success');
        }
        toastEl.classList.add('show');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toastEl.classList.remove('show');
        }, 1300);
    }

    var initMessage = toastEl ? (toastEl.getAttribute('data-init-message') || '') : '';
    var initKind = toastEl ? (toastEl.getAttribute('data-init-kind') || '') : '';
    if (initMessage) {
        showToast(initMessage, initKind);
    }

    document.querySelectorAll('.js-touch-form').forEach(function (form) {
        function submitNextState() {
            var orderId = form.getAttribute('data-order-id') || '0';
            var storageKey = 'kitchen_clicks_' + orderId;
            var clickCount = parseInt(sessionStorage.getItem(storageKey) || '0', 10) + 1;
            sessionStorage.setItem(storageKey, String(clickCount));

            var current = form.getAttribute('data-current') || 'Pendiente';
            var next = nextByState[current] || 'Pendiente';
            var input = form.querySelector('.js-next-state');
            if (input) {
                if (clickCount >= 4) {
                    next = 'Entregado';
                    sessionStorage.setItem(storageKey, '0');
                    input.value = next;
                    showToast('Pedido entregado');
                    setTimeout(function () {
                        form.submit();
                    }, 500);
                    return;
                }

                input.value = next;
                form.submit();
            }
        }

        form.addEventListener('click', function (event) {
            if (event.target.closest('button,input,select,a,textarea,label')) return;
            submitNextState();
        });

        form.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                submitNextState();
            }
        });
    });

    document.querySelectorAll('.js-delete-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var cardForm = button.closest('.js-touch-form');
            if (!cardForm) return;

            var deleteForm = cardForm.nextElementSibling;
            if (deleteForm && deleteForm.classList.contains('js-delete-form')) {
                deleteForm.submit();
            }
        });
    });
})();
</script>

</body>
</html>
