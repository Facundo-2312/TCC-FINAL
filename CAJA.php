<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$conexion = app_db_connect();
if (!$conexion) {
    App\Support\Db::fail('No se pudo conectar con la base de datos.', 'CAJA.php: ' . mysqli_connect_error());
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facturar'])) {
    csrf_verify_or_die('CAJA.php');

    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $metodo = trim($_POST['metodo_pago'] ?? '');
    $controller = new App\Controllers\PagoController();
    $resultado = $controller->facturar($idPedido, $metodo);
    app_set_flash($resultado['ok'] ? 'success' : 'error', $resultado['ok']
        ? 'Pago confirmado correctamente.'
        : 'No se pudo confirmar el pago. Verifica que el pedido no este ya pagado e intenta nuevamente.');

    app_redirect('CAJA.php');
    exit();
}

$sqlResumenCaja = "
    SELECT
        COALESCE(SUM(monto), 0) AS recaudadoHoy,
        COUNT(*) AS pagosHoy
    FROM pagos
    WHERE fecha_dia = CURDATE()
";
$resResumen = mysqli_query($conexion, $sqlResumenCaja);
if (!$resResumen) {
    throw new RuntimeException(mysqli_error($conexion));
}
$resumen = mysqli_fetch_assoc($resResumen);

$pedidosPendientes = [];
$stmtPendientes = mysqli_prepare(
    $conexion,
    "SELECT p.id_pedido, m.numero AS mesa_numero, p.fecha, p.total, p.estado,
            sc.metodo_pago AS cobro_metodo, sc.solicitada_en
     FROM pedidos p
     LEFT JOIN mesas m ON m.id_mesa = p.id_mesa
     LEFT JOIN pagos pa ON pa.id_pedido = p.id_pedido
     LEFT JOIN solicitudes_cobro sc ON sc.id_pedido = p.id_pedido
    WHERE p.estado IN ('Entregado','ArchivadoCocina') AND pa.id_pago IS NULL
     ORDER BY (sc.id_pedido IS NOT NULL) DESC, sc.solicitada_en ASC, p.fecha ASC, p.id_pedido ASC"
);
if (!$stmtPendientes) {
    throw new RuntimeException(mysqli_error($conexion));
}
mysqli_stmt_execute($stmtPendientes);
$resPendientes = mysqli_stmt_get_result($stmtPendientes);
while ($fila = mysqli_fetch_assoc($resPendientes)) {
    $pedidosPendientes[] = $fila;
}
mysqli_stmt_close($stmtPendientes);

$resumen['pendienteCobro'] = array_sum(array_column($pedidosPendientes, 'total'));
$resumen['pedidosPendientes'] = count($pedidosPendientes);
$resumen['solicitudes'] = count(array_filter($pedidosPendientes, function ($pedido) {
    return $pedido['cobro_metodo'] !== null;
}));
$resPagos = mysqli_query($conexion,
    'SELECT pa.id_pago, pa.id_pedido, pa.fecha, pa.monto, pa.metodo_pago, m.numero AS mesa_numero
     FROM pagos pa
     LEFT JOIN pedidos p ON p.id_pedido = pa.id_pedido
     LEFT JOIN mesas m ON m.id_mesa = p.id_mesa
     WHERE pa.fecha_dia = CURDATE()
     ORDER BY pa.fecha DESC, pa.id_pago DESC LIMIT 10'
);
if (!$resPagos) {
    throw new RuntimeException(mysqli_error($conexion));
}
$ultimosPagos = mysqli_fetch_all($resPagos, MYSQLI_ASSOC);

$detallePorPedido = [];
foreach ($pedidosPendientes as $pedido) {
    $idPedido = (int) $pedido['id_pedido'];
    $stmtDetalle = mysqli_prepare(
        $conexion,
        'SELECT dp.cantidad, pr.nombre, dp.subtotal
         FROM detalle_pedido dp
         INNER JOIN productos pr ON pr.id_producto = dp.id_producto
         WHERE dp.id_pedido = ?
         ORDER BY pr.nombre ASC'
    );
    if (!$stmtDetalle) {
        throw new RuntimeException(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtDetalle, 'i', $idPedido);
    mysqli_stmt_execute($stmtDetalle);
    $resDetalle = mysqli_stmt_get_result($stmtDetalle);

    $detalle = [];
    while ($d = mysqli_fetch_assoc($resDetalle)) {
        $detalle[] = $d;
    }
    mysqli_stmt_close($stmtDetalle);

    $detallePorPedido[$idPedido] = $detalle;
}

if (isset($_GET['solicitudes'])) {
    $tarjetas = [];
    foreach ($pedidosPendientes as $pedido) {
        ob_start();
        require __DIR__ . '/src/Views/caja_pedido.php';
        $tarjetas[] = array('id_pedido' => (int) $pedido['id_pedido'], 'html' => ob_get_clean());
    }
    ob_start();
    require __DIR__ . '/src/Views/caja_pagos.php';
    $pagosHtml = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array(
        'pedidos' => $pedidosPendientes, 'resumen' => $resumen,
        'tarjetas' => $tarjetas, 'pagos_html' => $pagosHtml
    ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
$flashCaja = app_get_flash();

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Caja</title>
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<style>
:root{--bg:#0d0f14;--panel:#161922;--panel-soft:#1d212c;--accent:#ff2d6f;--text:#f3f4f8;--muted:#a9adba;--line:rgba(255,255,255,.1)}
*{box-sizing:border-box}
body{margin:0;padding:24px;background:radial-gradient(ellipse at top left,rgba(255,45,111,.13),transparent 45%),var(--bg);color:var(--text);font-family:'Segoe UI',Arial,sans-serif}
main{max-width:1440px;margin:auto}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;border:0;cursor:pointer}
.back{background:#3a3a3a;color:#fff}.logout{background:var(--accent);color:#fff}
h1{margin:28px 0 8px;font-size:clamp(1.8rem,3vw,2.6rem)}
h2{font-size:1.2rem;margin:0 0 8px}h3{margin:0;font-size:1.1rem}
.subtitle{color:var(--muted);margin:0 0 24px;line-height:1.5}
.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:26px}
.card{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:14px;padding:14px}
.label{color:var(--muted);font-size:.9rem}.value{font-size:1.7rem;font-weight:800;margin-top:6px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.order{background:linear-gradient(160deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:16px;padding:20px;min-width:0}
.order.requested{border-color:rgba(255,45,111,.5)}
.order-head{display:flex;justify-content:space-between;align-items:center;gap:12px}
.badge{border-radius:99px;padding:5px 10px;font-size:.75rem;background:rgba(255,45,111,.16);color:#ff8eb1;white-space:nowrap}
.solicitud-cobro{font-size:.9rem;color:#ff8eb1;min-height:20px}
.order-meta,.order-note{color:var(--muted);font-size:.82rem;line-height:1.5}
.order ul{padding:0;list-style:none;border-top:1px solid var(--line)}
.order li{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)}
.payment-form{display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-top:18px}
.payment-form label{display:flex;flex-direction:column;gap:6px;font-size:.82rem;flex:1;min-width:130px}
.payment-form .facturar{width:100%;margin-top:4px}
.section-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px}
.section-head p{margin:0;color:var(--muted);font-size:.85rem}
.history{margin-top:28px;padding:22px;border:1px solid var(--line);border-radius:16px;background:var(--panel)}
.table-scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;text-align:left;white-space:nowrap}
th{font-size:.78rem;color:var(--muted);font-weight:600}td,th{padding:13px 12px;border-bottom:1px solid var(--line)}td{font-size:.88rem}
.confirmed{color:#7ee2a8}.empty-state{padding:32px;text-align:center;grid-column:1/-1;border:1px dashed var(--line);border-radius:14px;color:var(--muted)}
.empty-state strong{display:block;color:var(--text);margin-bottom:8px}
.flash{padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--panel-soft)}
#cobros-status{min-height:22px;color:var(--muted);font-size:.85rem}
ul{padding-left:18px;margin-top:8px}
select,button,input{padding:11px;border-radius:8px;border:1px solid var(--line);font:inherit}
select,input{background:#101218;color:var(--text);width:100%}
select:focus,button:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
.monto-cobro{display:flex;flex-direction:column;gap:4px;font-size:.85rem;font-weight:700}
.monto-cobro input{background:rgba(255,255,255,.04);color:var(--text);font-weight:700}
.facturar{background:#1f9d55;color:#fff;border:0;cursor:pointer;font-weight:700}
.empty{color:var(--muted)}
@media (max-width:1000px){.summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:700px){body{padding:16px}.grid{grid-template-columns:1fr}.value{font-size:1.35rem}.history{padding:14px}.section-head{align-items:start;flex-direction:column}}
@media (max-width:380px){.summary{grid-template-columns:1fr}}
</style>
</head>
<body>
<main>

<div class="top">
    <a class="btn back" href="<?php echo htmlspecialchars(app_url('inicio_rol.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
    <a class="btn logout" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">Salir</a>
</div>

<h1>Panel de Caja</h1>
<p class="subtitle">Control de cobros del restaurante · <?php echo date('d/m/Y'); ?> · Actualizacion automatica cada 5 segundos</p>
<?php if ($flashCaja) { ?><p class="flash" role="status"><?php echo h($flashCaja['message']); ?></p><?php } ?>
<p id="cobros-status" role="status" aria-live="polite">Conectado al panel de cobros.</p>

<div class="summary">
    <div class="card"><div class="label">Cobrado hoy</div><div class="value" id="caja-recaudado">$<?php echo number_format((float) $resumen['recaudadoHoy'], 2, ',', '.'); ?></div><small class="label">Pagos confirmados, sin adicionales</small></div>
    <div class="card"><div class="label">Pendiente de cobro</div><div class="value" id="caja-pendiente">$<?php echo number_format((float) $resumen['pendienteCobro'], 2, ',', '.'); ?></div><small class="label"><span id="caja-pedidos"><?php echo $resumen['pedidosPendientes']; ?></span> pedidos entregados sin pagar</small></div>
    <div class="card"><div class="label">Solicitudes de clientes</div><div class="value" id="caja-solicitudes"><?php echo $resumen['solicitudes']; ?></div><small class="label">Esperando confirmacion de Caja</small></div>
    <div class="card"><div class="label">Pagos registrados hoy</div><div class="value" id="caja-pagos"><?php echo (int) $resumen['pagosHoy']; ?></div><small class="label">Operaciones completadas</small></div>
</div>

<section aria-labelledby="pendientes-titulo">
<div class="section-head"><div><h2 id="pendientes-titulo">Pedidos por cobrar</h2><p>Las solicitudes de clientes aparecen primero. Confirma solo al recibir el dinero. Los pedidos aparecen aqui cuando Cocina los marca como entregados.</p></div></div>
<div class="grid" id="caja-pendientes">
<?php foreach ($pedidosPendientes as $pedido) { require __DIR__ . '/src/Views/caja_pedido.php'; } ?>
<div class="empty-state" id="caja-sin-pendientes"<?php echo $pedidosPendientes ? ' hidden' : ''; ?>><strong>Todo al dia</strong>No hay pedidos entregados pendientes de cobro.</div>
</div>
</section>
<section class="history" aria-labelledby="pagos-titulo">
<div class="section-head"><div><h2 id="pagos-titulo">Ultimos pagos confirmados</h2><p>Ultimas 10 operaciones de hoy. Importes del pedido, sin adicionales historicos.</p></div></div>
<div class="table-scroll" id="caja-historial"><?php require __DIR__ . '/src/Views/caja_pagos.php'; ?></div>
</section>
</main>
<script>
const cobrosStatus = document.getElementById('cobros-status');
const pendientesContainer = document.getElementById('caja-pendientes');
const sinPendientes = document.getElementById('caja-sin-pendientes');
const historial = document.getElementById('caja-historial');
const tarjetasHtml = new Map();
pendientesContainer.addEventListener('change', event => {
    if (event.target.matches('select[name="metodo_pago"]')) event.target.dataset.editado = '1';
});
const formatoMonto = value => '$' + Number(value).toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2});
let consultandoCobros = false;
async function actualizarSolicitudesCobro() {
    if (consultandoCobros || document.hidden) return;
    consultandoCobros = true;
    try {
        const response = await fetch(<?php echo json_encode(app_url('CAJA.php?solicitudes=1')); ?>, {cache: 'no-store', credentials: 'same-origin'});
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        if (!Array.isArray(data.tarjetas) || !data.resumen || typeof data.pagos_html !== 'string') throw new Error('Respuesta invalida');
        const ids = new Set(data.tarjetas.map(tarjeta => String(tarjeta.id_pedido)));
        pendientesContainer.querySelectorAll('[data-pedido]').forEach(card => {
            if (!ids.has(card.dataset.pedido)) {
                tarjetasHtml.delete(card.dataset.pedido);
                card.remove();
            }
        });
        data.tarjetas.forEach(tarjeta => {
            const id = String(Number(tarjeta.id_pedido));
            let card = pendientesContainer.querySelector('[data-pedido="' + id + '"]');
            if (card && (card.contains(document.activeElement) || tarjetasHtml.get(id) === tarjeta.html)) return;
            const template = document.createElement('template');
            template.innerHTML = tarjeta.html.trim();
            const nueva = template.content.firstElementChild;
            if (!nueva || nueva.dataset.pedido !== id) throw new Error('Tarjeta invalida');
            if (card) {
                const anterior = card.querySelector('select[name="metodo_pago"]');
                if (anterior.dataset.editado) {
                    const select = nueva.querySelector('select[name="metodo_pago"]');
                    select.value = anterior.value;
                    select.dataset.editado = '1';
                }
                card.replaceWith(nueva);
            } else {
                pendientesContainer.insertBefore(nueva, sinPendientes);
            }
            tarjetasHtml.set(id, tarjeta.html);
        });
        if (!pendientesContainer.contains(document.activeElement)) {
            data.tarjetas.forEach(tarjeta => {
                const card = pendientesContainer.querySelector('[data-pedido="' + Number(tarjeta.id_pedido) + '"]');
                pendientesContainer.insertBefore(card, sinPendientes);
            });
        }
        sinPendientes.hidden = data.tarjetas.length > 0;
        document.getElementById('caja-recaudado').textContent = formatoMonto(data.resumen.recaudadoHoy);
        document.getElementById('caja-pendiente').textContent = formatoMonto(data.resumen.pendienteCobro);
        document.getElementById('caja-pedidos').textContent = data.resumen.pedidosPendientes;
        document.getElementById('caja-solicitudes').textContent = data.resumen.solicitudes;
        document.getElementById('caja-pagos').textContent = data.resumen.pagosHoy;
        if (historial.innerHTML !== data.pagos_html) historial.innerHTML = data.pagos_html;
        cobrosStatus.textContent = 'Actualizado a las ' + new Date().toLocaleTimeString('es-UY');
    } catch (error) {
        cobrosStatus.textContent = 'No se pudieron actualizar las solicitudes. Se reintentara automaticamente.';
    } finally {
        consultandoCobros = false;
    }
}
document.addEventListener('visibilitychange', () => { if (!document.hidden) actualizarSolicitudesCobro(); });
setInterval(actualizarSolicitudesCobro, 5000);
</script>
</body>
</html>
