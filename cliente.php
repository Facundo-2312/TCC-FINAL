<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php');

function cliente_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cliente_imagen($producto)
{
    $ruta = trim((string) ($producto['img'] ?? ''));
    if ($ruta !== '' && preg_match('/^(https?:)?\/\//i', $ruta)) {
        return $ruta;
    }

    $normalizada = ltrim(str_replace('\\', '/', $ruta), '/');
    $base = basename($normalizada);
    $baseSinExtension = pathinfo($base, PATHINFO_FILENAME);
    $nombre = mb_strtolower((string) ($producto['nombre'] ?? ''), 'UTF-8');
    $imagenesCatalogoCorregidas = array(
        'copa de dulce de leche' => 'MVCsix1.0/img/catalogo/copa-dulce-leche.jpg',
        'flan casero' => 'MVCsix1.0/img/catalogo/flan-casero.jpg',
        'helado' => 'MVCsix1.0/files/Helado.jpg',
        'mousse de chocolate' => 'MVCsix1.0/img/catalogo/mousse-chocolate.jpg',
        'torta de chocolate' => 'MVCsix1.0/img/catalogo/torta-chocolate.jpg',
        'tiramisu' => 'MVCsix1.0/img/catalogo/tiramisu.jpg'
    );
    foreach ($imagenesCatalogoCorregidas as $nombreProducto => $imagen) {
        if ($nombre === $nombreProducto && is_file(__DIR__ . '/' . $imagen)) {
            return $imagen;
        }
    }

    if (strpos($normalizada, 'img/catalogo/') === 0) {
        $catalogo = 'MVCsix1.0/' . $normalizada;
        if (is_file(__DIR__ . '/' . $catalogo)) {
            return $catalogo;
        }
    }

    $imagenesCorregidas = array(
        'hamburguesa' => 'MVCsix1.0/img/default_hamburguesa_hq.jpg',
        'pizza' => 'MVCsix1.0/img/default_pizza_hq.jpg',
        'coca' => 'MVCsix1.0/img/coca_hq.jpg',
        'cola' => 'MVCsix1.0/img/coca_hq.jpg',
        'agua' => 'MVCsix1.0/img/agua_hq.jpg'
    );
    foreach ($imagenesCorregidas as $keyword => $imagen) {
        if (mb_strpos($nombre, $keyword) !== false && is_file(__DIR__ . '/' . $imagen)) {
            return $imagen;
        }
    }

    $candidatas = array(
        $normalizada,
        $normalizada !== '' ? 'MVCsix1.0/' . $normalizada : '',
        $base !== '' ? 'MVCsix1.0/img/' . $base : '',
        $base !== '' ? 'MVCsix1.0/files/' . $base : '',
        $baseSinExtension !== '' ? 'MVCsix1.0/img/' . $baseSinExtension . '_hq.jpg' : '',
        $baseSinExtension !== '' ? 'MVCsix1.0/files/' . $baseSinExtension . '_hq.jpg' : ''
    );

    foreach ($candidatas as $candidata) {
        if ($candidata !== '' && strpos($candidata, '..') === false && is_file(__DIR__ . '/' . $candidata)) {
            return $candidata;
        }
    }

    $defaults = array(
        'hamburguesa' => 'MVCsix1.0/img/default_hamburguesa_hq.jpg',
        'pizza' => 'MVCsix1.0/img/default_pizza_hq.jpg',
        'coca' => 'MVCsix1.0/img/default_bebida_hq.jpg',
        'cola' => 'MVCsix1.0/img/default_bebida_hq.jpg',
        'agua' => 'MVCsix1.0/img/default_bebida_hq.jpg',
        'jugo' => 'MVCsix1.0/img/default_bebida_hq.jpg',
        'helado' => 'MVCsix1.0/img/default_postre_hq.jpg'
    );
    foreach ($defaults as $keyword => $imagen) {
        if (mb_strpos($nombre, $keyword) !== false && is_file(__DIR__ . '/' . $imagen)) {
            return $imagen;
        }
    }

    return 'MVCsix1.0/img/default_comida_hq.jpg';
}

$productoController = new App\Controllers\ProductoController();
$pedidoController = new App\Controllers\PedidoController();
$reservaService = new App\Services\ReservaService();
$mesaRepository = new App\Repositories\MesaRepository();
$flash = app_get_flash();
$mensaje = (string) ($flash['message'] ?? '');
$tipoMensaje = (string) ($flash['type'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_reserva'])) {
    csrf_verify_or_die('cliente.php');
    $idMesaReserva = (int) ($_POST['id_mesa_reserva'] ?? 0);
    $nombreReserva = trim((string) ($_POST['nombre_reserva'] ?? ($_SESSION['ClienteNombre'] ?? '')));
    $cantidadPersonas = (int) ($_POST['cantidad_personas'] ?? 0);
    $horaInicioReserva = trim((string) ($_POST['hora_inicio_reserva'] ?? ''));
    $telefonoReserva = trim((string) ($_POST['telefono_reserva'] ?? ''));
    $notasReserva = trim((string) ($_POST['notas_reserva'] ?? ''));
    $horaInicioTimestamp = strtotime($horaInicioReserva);
    $horaFinReserva = $horaInicioTimestamp ? date('Y-m-d H:i:s', $horaInicioTimestamp + 90 * 60) : '';
    $mesaReserva = null;
    foreach ($mesaRepository->listar() as $mesaDisponible) {
        if ((int) $mesaDisponible['id_mesa'] === $idMesaReserva) {
            $mesaReserva = $mesaDisponible;
            break;
        }
    }

    $errorReserva = '';
    if (!$mesaReserva) $errorReserva = 'Selecciona una mesa disponible.';
    elseif ($nombreReserva === '' || strlen($nombreReserva) > 100) $errorReserva = 'Ingresa el nombre de la reserva.';
    elseif ($cantidadPersonas <= 0 || $cantidadPersonas > (int) ($mesaReserva['capacidad'] ?: 50)) $errorReserva = 'La cantidad de personas supera la capacidad de la mesa.';
    elseif (!$horaInicioTimestamp || $horaInicioTimestamp < time()) $errorReserva = 'Selecciona un horario disponible futuro.';
    elseif ($telefonoReserva !== '' && strlen($telefonoReserva) > 20) $errorReserva = 'El telefono no puede superar 20 caracteres.';

    if ($errorReserva !== '') {
        $mensaje = $errorReserva;
        $tipoMensaje = 'error';
    } else {
        $idUsuarioReserva = null;
        $usuarioReserva = new App\Repositories\UsuarioRepository();
        $cuentaReserva = $usuarioReserva->buscarAcceso((string) $_SESSION['Usuario']);
        if ($cuentaReserva) $idUsuarioReserva = (int) $cuentaReserva['id_usuario'];
        $resultadoReserva = $reservaService->crear(array('id_mesa' => $idMesaReserva, 'nombre_cliente' => $nombreReserva, 'cantidad_personas' => $cantidadPersonas, 'hora_inicio' => date('Y-m-d H:i:s', $horaInicioTimestamp), 'hora_fin' => $horaFinReserva, 'telefono' => $telefonoReserva, 'notas' => $notasReserva, 'id_usuario' => $idUsuarioReserva));
        $mensaje = $resultadoReserva['ok'] ? 'Reserva realizada con éxito.' : $resultadoReserva['error'];
        $tipoMensaje = $resultadoReserva['ok'] ? 'success' : 'error';
    }
}

if (!isset($_SESSION['cliente_pedido_token'])) {
    $_SESSION['cliente_pedido_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    csrf_verify_or_die('cliente.php');

    $token = (string) ($_POST['pedido_token'] ?? '');
    $tokenValido = $token !== '' && isset($_SESSION['cliente_pedido_token']) && hash_equals($_SESSION['cliente_pedido_token'], $token);
    $mesa = (int) ($_POST['mesa'] ?? 0);
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $items = json_decode((string) ($_POST['items'] ?? '[]'), true);

    if (!$tokenValido) {
        $mensaje = 'Este pedido ya fue enviado o la pagina esta desactualizada. Vuelve a cargarla.';
        $tipoMensaje = 'error';
    } elseif ($mesa <= 0 || !is_array($items) || empty($items)) {
        $mensaje = 'Selecciona una mesa y al menos un producto.';
        $tipoMensaje = 'error';
    } elseif (strlen($observaciones) > 500) {
        $mensaje = 'Las observaciones no pueden superar los 500 caracteres.';
        $tipoMensaje = 'error';
    } else {
        $idPedido = $pedidoController->crearPedidoMozo($mesa, $_SESSION['Usuario'], $observaciones, $items);
        if ($idPedido) {
            unset($_SESSION['cliente_pedido_token']);
            app_set_flash('success', 'Pedido realizado con éxito.');
            app_redirect('cliente.php?pedido=ok');
        }
        $mensaje = 'No se pudo registrar el pedido. Verifica la mesa, la disponibilidad y las cantidades.';
        $tipoMensaje = 'error';
    }
}

$productos = array_values(array_filter($productoController->listar(), function ($producto) {
    return ($producto['estado'] ?? 'Inactivo') === 'Activo';
}));
$pedidosCliente = $pedidoController->listarDelUsuario($_SESSION['Usuario'], 10);
$clienteVolver = (string) ($_SESSION['Rol'] ?? '') === '5' ? 'Login.php' : 'Principal.php';

function cliente_estado_clase($estado)
{
    return strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $estado));
}

function cliente_demora($estado)
{
    if ($estado === 'Entregado') return 'Listo para retirar';
    if ($estado === 'Cancelado') return 'Pedido cancelado';
    if ($estado === 'Preparando') return 'Aproximadamente 10 minutos';
    return 'Aproximadamente 20 minutos';
}

$conexion = app_db_connect();
$mesas = array();
$mesasTodas = $mesaRepository->listar();
if ($conexion) {
    foreach ($mesasTodas as $mesa) {
        if (($mesa['estado'] ?? '') === 'Libre') $mesas[] = $mesa;
    }
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pedido del cliente</title>
<script src="<?php echo cliente_h(app_url('no-popups.js')); ?>"></script>
<style>
:root{--bg:#111217;--panel:#1c1e25;--panel-soft:#272a33;--accent:#ff0055;--accent-soft:#ff4d8a;--text:#f4f4f4;--muted:#bdbdbd;--line:rgba(255,255,255,.1);--success:#22a95b;--danger:#dc2626}
*{box-sizing:border-box}body{margin:0;padding:22px;background:radial-gradient(circle at top left,rgba(255,0,85,.16),transparent 34%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;max-width:1500px;margin:auto}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 15px;border:0;border-radius:8px;text-decoration:none;font-weight:700;cursor:pointer}.back{background:#343740;color:#fff}.primary{width:100%;background:var(--accent);color:#fff;font-size:1rem}.primary:hover,.add:hover{background:var(--accent-soft)}h1{max-width:1500px;margin:18px auto;color:#fff;font-size:clamp(1.8rem,3vw,2.5rem)}.user{color:#ff8fb3;font-weight:700}.alert{max-width:1500px;margin:0 auto 16px;padding:12px 14px;border-radius:6px;color:#fecaca;background:rgba(220,38,38,.16);border:1px solid rgba(220,38,38,.5)}.alert.success{color:#bbf7d0;background:rgba(34,169,91,.16);border-color:rgba(34,169,91,.55)}.reservation-panel{max-width:1500px;margin:0 auto 18px}.reservation-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0 14px}.reservation-submit{max-width:260px;margin-top:16px}.tracking-panel{max-width:1500px;margin:0 auto 18px}.tracking-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.tracking-item{padding:13px;border:1px solid var(--line);border-radius:7px;background:#15171d}.tracking-item strong{font-size:1rem}.tracking-item p{margin:8px 0 0;color:var(--muted);font-size:.84rem}.tracking-status{float:right;padding:4px 8px;border-radius:999px;color:#fff;font-size:.75rem;font-weight:700;background:#f59e0b}.tracking-status.preparando{background:#2563eb}.tracking-status.entregado{background:#22a95b}.tracking-status.cancelado{background:#dc2626}.layout{max-width:1500px;margin:auto;display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}.panel{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:8px;padding:18px}.panel h2{margin:0 0 16px;font-size:1.2rem}.products{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}.product{display:flex;flex-direction:column;overflow:hidden;background:#15171d;border:1px solid var(--line);border-radius:8px;min-width:0}.product img{display:block;width:100%;height:150px;object-fit:cover;background:#0d0e12}.product-info{display:flex;flex:1;flex-direction:column;padding:12px}.product h3{margin:0 0 6px;font-size:1rem}.product p{height:36px;margin:0 0 10px;color:var(--muted);font-size:.84rem;line-height:1.35;overflow:hidden}.product-bottom{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto}.price{font-weight:700}.availability{display:block;margin-bottom:8px;color:#86efac;font-size:.75rem}.availability.unavailable{color:#fca5a5}.add{padding:8px 10px;border:0;border-radius:6px;background:var(--accent);color:#fff;font-weight:700;cursor:pointer}.add:disabled{background:#555b66;cursor:not-allowed;opacity:.75}.order-panel{position:sticky;top:16px}label{display:block;margin:12px 0 6px;color:var(--muted);font-size:.86rem}select,textarea,input{width:100%;padding:11px 12px;border:1px solid #424651;border-radius:6px;background:#101116;color:#fff;font:inherit}textarea{min-height:78px;resize:vertical}.cart{margin-top:16px;border-top:1px solid var(--line);max-height:350px;overflow:auto}.empty{padding:18px 0;text-align:center;color:var(--muted)}.cart-item{padding:12px 0;border-bottom:1px solid var(--line)}.cart-name{font-weight:700;font-size:.92rem}.cart-line{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:8px;color:var(--muted);font-size:.88rem}.quantity{display:inline-flex;align-items:center;gap:8px}.quantity button{width:27px;height:27px;border:1px solid #515561;border-radius:5px;background:transparent;color:#fff;cursor:pointer}.remove{border:0;background:transparent;color:#ff8fb3;cursor:pointer;font-size:.8rem}.total{display:flex;justify-content:space-between;padding:16px 0;font-size:1.15rem;font-weight:700}.note{margin:14px 0 0;color:var(--muted);font-size:.8rem;line-height:1.4}@media(max-width:1050px){.reservation-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.layout{grid-template-columns:1fr}.order-panel{position:static}}@media(max-width:620px){body{padding:15px}.reservation-grid{grid-template-columns:1fr}.products{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.product img{height:112px}.product-info{padding:10px}.product p{display:none}.top .user{display:none}}
</style>
</head>
<body>
<div class="top"><a class="btn back" href="<?php echo cliente_h(app_url($clienteVolver)); ?>">← Volver</a><span class="user">Bienvenido, <?php echo cliente_h($_SESSION['ClienteNombre'] ?? $_SESSION['Usuario']); ?></span><a class="btn back" href="<?php echo cliente_h(app_url('Salir.php')); ?>">Salir</a></div>
<h1>Arma tu pedido</h1>
<?php if ($mensaje !== '') { ?><div class="alert <?php echo cliente_h($tipoMensaje); ?>"><?php echo cliente_h($mensaje); ?></div><?php } ?>
<form class="panel reservation-panel" method="post"><h2>Reservar mesa</h2><p class="note">Elige una mesa, una fecha y un horario disponible de 90 minutos.</p><?php echo csrf_field(); ?><input type="hidden" name="crear_reserva" value="1"><input type="hidden" id="hora_inicio_reserva" name="hora_inicio_reserva"><div class="reservation-grid"><div><label for="id_mesa_reserva">Mesa</label><select id="id_mesa_reserva" name="id_mesa_reserva" required><option value="">Selecciona una mesa</option><?php foreach ($mesas as $mesa) { ?><option value="<?php echo (int) $mesa['id_mesa']; ?>">Mesa <?php echo (int) $mesa['numero']; ?> · Capacidad <?php echo (int) ($mesa['capacidad'] ?: 4); ?></option><?php } ?></select></div><div><label for="cantidad_personas">Personas</label><input id="cantidad_personas" name="cantidad_personas" type="number" min="1" max="50" required></div><div><label for="nombre_reserva">Nombre reservado</label><input id="nombre_reserva" name="nombre_reserva" maxlength="100" required value="<?php echo cliente_h($_SESSION['ClienteNombre'] ?? ''); ?>"></div><div><label for="fecha_reserva">Fecha</label><input id="fecha_reserva" type="date" required value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>"></div><div><label for="horario_reserva">Horarios disponibles</label><select id="horario_reserva" required disabled><option>Selecciona una mesa</option></select></div><div><label for="telefono_reserva">Telefono</label><input id="telefono_reserva" name="telefono_reserva" maxlength="20" inputmode="tel"></div><div><label for="notas_reserva">Notas</label><input id="notas_reserva" name="notas_reserva" maxlength="500"></div></div><button class="btn primary reservation-submit" type="submit">Reservar mesa</button></form>
<section class="panel tracking-panel"><h2>Seguimiento de mis pedidos</h2><div id="order-tracking" class="tracking-list"><?php if (!$pedidosCliente) { ?><p class="empty">Todavia no tienes pedidos realizados.</p><?php } else { foreach ($pedidosCliente as $pedido) { ?><article class="tracking-item"><div><strong>Pedido #<?php echo (int) $pedido['id_pedido']; ?></strong><span class="tracking-status <?php echo cliente_h(cliente_estado_clase($pedido['estado'])); ?>"><?php echo cliente_h($pedido['estado']); ?></span></div><p><?php echo cliente_h(cliente_demora($pedido['estado'])); ?> · <?php echo cliente_h($pedido['fecha']); ?></p><p><?php echo count($pedido['productos']); ?> producto(s) · Total $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></p></article><?php } } ?></div></section>
<section class="panel table-live-panel"><h2>Estado actual de la mesa</h2><label for="mesa_supervisada">Mesa a consultar</label><select id="mesa_supervisada"><option value="">Selecciona una mesa</option><?php foreach ($mesasTodas as $mesa) { ?><option value="<?php echo (int) $mesa['id_mesa']; ?>">Mesa <?php echo (int) $mesa['numero']; ?> · <?php echo cliente_h($mesa['estado']); ?></option><?php } ?></select><div id="table-live"><p class="empty">Selecciona una mesa para ver quién está allí y qué está pidiendo.</p></div></section>
<div class="layout">
<section class="panel"><h2>Menu disponible</h2><div class="products">
<?php foreach ($productos as $producto) { $disponible = (int) ($producto['stock'] ?? 0) > 0; ?><article class="product"><img src="<?php echo cliente_h(app_url(cliente_imagen($producto))); ?>" alt="<?php echo cliente_h($producto['nombre']); ?>"><div class="product-info"><h3><?php echo cliente_h($producto['nombre']); ?></h3><p><?php echo cliente_h($producto['descripcion']); ?></p><span class="availability<?php echo $disponible ? '' : ' unavailable'; ?>"><?php echo $disponible ? 'Disponible' : 'Agotado'; ?></span><div class="product-bottom"><span class="price">$<?php echo number_format((float) $producto['precio'], 2, ',', '.'); ?></span><button class="add" type="button" data-id="<?php echo (int) $producto['id_producto']; ?>" data-name="<?php echo cliente_h($producto['nombre']); ?>" data-price="<?php echo cliente_h($producto['precio']); ?>"<?php echo $disponible ? '' : ' disabled'; ?>><?php echo $disponible ? 'Agregar' : 'Agotado'; ?></button></div></div></article><?php } ?>
<?php if (!$productos) { ?><p class="empty">No hay productos disponibles en este momento.</p><?php } ?>
</div></section>
<form class="panel order-panel" method="post" id="order-form"><?php echo csrf_field(); ?><input type="hidden" name="pedido_token" value="<?php echo cliente_h($_SESSION['cliente_pedido_token']); ?>"><input type="hidden" name="items" id="items"><h2>Pedido actual</h2><label for="mesa">Mesa</label><select id="mesa" name="mesa" required><option value="">Selecciona una mesa</option><?php foreach ($mesas as $mesa) { ?><option value="<?php echo (int) $mesa['numero']; ?>">Mesa <?php echo (int) $mesa['numero']; ?></option><?php } ?></select><label for="observaciones">Notas para cocina</label><textarea id="observaciones" name="observaciones" maxlength="500" placeholder="Sin cebolla, extra salsa..."></textarea><div id="cart" class="cart"><div class="empty">Agrega productos desde el menu.</div></div><div class="total"><span>Total</span><span id="total">$0,00</span></div><button class="btn primary" type="submit" name="crear_pedido">Realizar pedido</button><p class="note">El total se verifica nuevamente al confirmar y el pedido se envía al mismo panel que utiliza cocina.</p></form>
</div>
<script>
const cart = new Map();
const reservationMesa = document.getElementById('id_mesa_reserva');
const reservationDate = document.getElementById('fecha_reserva');
const reservationSlots = document.getElementById('horario_reserva');
const reservationStart = document.getElementById('hora_inicio_reserva');
const orderTable = document.getElementById('mesa');
const monitoredTable = document.getElementById('mesa_supervisada');
const tableLive = document.getElementById('table-live');
function renderTableLive(data) {
    let html = '<p><strong>Mesa ' + data.mesa.numero + '</strong> · Estado: ' + data.mesa.estado + '</p>';
    if (data.reserva) {
        html += '<div class="table-person"><strong>Persona reservada:</strong> ' + escapeHtml(data.reserva.nombre_cliente) + ' (' + data.reserva.cantidad_personas + ' personas)<br><span>' + data.reserva.hora_inicio + ' - ' + data.reserva.hora_fin + '</span></div>';
    } else {
        html += '<p class="empty">No hay una reserva activa para esta mesa.</p>';
    }
    if (!data.pedidos.length) {
        html += '<p class="empty">No hay pedidos activos en esta mesa.</p>';
    } else {
        html += data.pedidos.map(pedido => '<article class="table-order"><strong>Pedido #' + pedido.id_pedido + '</strong><span class="tracking-status ' + pedido.estado.toLowerCase() + '">' + pedido.estado + '</span><ul>' + pedido.productos.map(producto => '<li>' + producto.cantidad + ' x ' + escapeHtml(producto.nombre) + '</li>').join('') + '</ul><small>Total: $' + Number(pedido.total).toLocaleString('es-UY', {minimumFractionDigits: 2}) + '</small></article>').join('');
    }
    tableLive.innerHTML = html;
}
async function loadTableLive() {
    if (!monitoredTable.value) return;
    try {
        const response = await fetch('<?php echo cliente_h(app_url('mesa_cliente_estado.php')); ?><?php echo strpos(app_url('mesa_cliente_estado.php'), '?') === false ? '?' : '&'; ?>id_mesa=' + encodeURIComponent(monitoredTable.value), {credentials: 'same-origin'});
        const data = await response.json();
        if (data.ok) renderTableLive(data);
    } catch (error) {
        tableLive.innerHTML = '<p class="empty">No se pudo actualizar el estado de la mesa.</p>';
    }
}
const mesaNumeroAId = <?php echo json_encode(array_column($mesas, 'id_mesa', 'numero')); ?>;
Array.from(orderTable.options).forEach(option => { if (mesaNumeroAId[option.value]) option.dataset.id = mesaNumeroAId[option.value]; });
monitoredTable.addEventListener('change', loadTableLive);
async function loadReservationSlots() {
    if (!reservationMesa.value || !reservationDate.value) return;
    reservationSlots.disabled = true;
    reservationSlots.innerHTML = '<option>Cargando horarios...</option>';
    try {
        const response = await fetch('<?php echo cliente_h(app_url('horarios_disponibles.php')); ?><?php echo strpos(app_url('horarios_disponibles.php'), '?') === false ? '?' : '&'; ?>id_mesa=' + encodeURIComponent(reservationMesa.value) + '&fecha=' + encodeURIComponent(reservationDate.value), {credentials: 'same-origin'});
        if (!response.ok) throw new Error('Respuesta HTTP ' + response.status);
        const data = await response.json();
        reservationSlots.innerHTML = '<option value="">Selecciona un horario</option>';
        (data.slots || []).filter(slot => slot.disponible).forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.hora_inicio;
            option.textContent = slot.etiqueta;
            reservationSlots.appendChild(option);
        });
        if (reservationSlots.options.length === 1) reservationSlots.innerHTML = '<option value="">No hay horarios disponibles</option>';
        reservationSlots.disabled = false;
    } catch (error) {
        reservationSlots.innerHTML = '<option value="">No se pudieron cargar horarios</option>';
        reservationSlots.title = error.message || 'Error al cargar horarios';
    }
}
reservationMesa.addEventListener('change', loadReservationSlots);
reservationDate.addEventListener('change', loadReservationSlots);
reservationSlots.addEventListener('change', () => { reservationStart.value = reservationSlots.value.replace('T', ' ') + ':00'; });
if (reservationMesa.value && reservationDate.value) loadReservationSlots();
document.querySelector('.reservation-panel').addEventListener('submit', event => {
    if (!reservationStart.value) {
        event.preventDefault();
        window.alert('Selecciona un horario disponible antes de reservar.');
    }
});
const draftKey = 'cliente_pedido_draft_<?php echo cliente_h(rawurlencode((string) $_SESSION['Usuario'])); ?>';
const pedidoConfirmado = <?php echo isset($_GET['pedido']) && $_GET['pedido'] === 'ok' ? 'true' : 'false'; ?>;
const money = value => '$' + value.toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
function saveDraft() {
    const mesa = document.getElementById('mesa');
    const observaciones = document.getElementById('observaciones');
    localStorage.setItem(draftKey, JSON.stringify({
        items: [...cart.values()],
        mesa: mesa ? mesa.value : '',
        observaciones: observaciones ? observaciones.value : ''
    }));
}
function restoreDraft() {
    try {
        const draft = JSON.parse(localStorage.getItem(draftKey) || '{}');
        (draft.items || []).forEach(item => {
            const id = Number(item.id);
            const quantity = Number(item.quantity);
            if (id > 0 && quantity > 0 && quantity <= 100) {
                cart.set(id, {id, name: String(item.name || ''), price: Number(item.price) || 0, quantity});
            }
        });
        if (document.getElementById('mesa')) document.getElementById('mesa').value = draft.mesa || '';
        if (document.getElementById('observaciones')) document.getElementById('observaciones').value = draft.observaciones || '';
    } catch (error) {
        localStorage.removeItem(draftKey);
    }
}
function renderCart(persist = true) {
    const container = document.getElementById('cart');
    const items = [...cart.values()];
    document.getElementById('items').value = JSON.stringify(items.map(item => ({IDProducto: item.id, quantity: item.quantity})));
    if (!items.length) { container.innerHTML = '<div class="empty">Agrega productos desde el menu.</div>'; document.getElementById('total').textContent = '$0,00'; if (persist) saveDraft(); return; }
    let total = 0;
    container.innerHTML = items.map(item => { total += item.price * item.quantity; return `<div class="cart-item"><div class="cart-name">${escapeHtml(item.name)}</div><div class="cart-line"><span>${money(item.price * item.quantity)}</span><span class="quantity"><button type="button" data-change="-1" data-id="${item.id}">-</button>${item.quantity}<button type="button" data-change="1" data-id="${item.id}">+</button></span></div><button class="remove" type="button" data-remove="${item.id}">Eliminar</button></div>`; }).join('');
    document.getElementById('total').textContent = money(total);
    if (persist) saveDraft();
}
document.querySelectorAll('.add').forEach(button => button.addEventListener('click', () => { const id = Number(button.dataset.id); const item = cart.get(id) || {id, name: button.dataset.name, price: Number(button.dataset.price), quantity: 0}; if (item.quantity < 100) item.quantity++; cart.set(id, item); renderCart(); }));
document.getElementById('cart').addEventListener('click', event => { const button = event.target.closest('button'); if (!button) return; const id = Number(button.dataset.id || button.dataset.remove); const item = cart.get(id); if (!item) return; if (button.dataset.remove) cart.delete(id); else { item.quantity += Number(button.dataset.change); if (item.quantity < 1) cart.delete(id); } renderCart(); });
document.getElementById('mesa').addEventListener('change', saveDraft);
document.getElementById('observaciones').addEventListener('input', saveDraft);
window.addEventListener('storage', event => { if (event.key !== draftKey) return; cart.clear(); restoreDraft(); renderCart(false); });
document.getElementById('order-form').addEventListener('submit', event => { if (!cart.size) { event.preventDefault(); window.alert('Agrega al menos un producto al pedido.'); } });
if (pedidoConfirmado) localStorage.removeItem(draftKey);
else restoreDraft();
renderCart(false);
setInterval(loadTableLive, 15000);
</script>
</body>
</html>