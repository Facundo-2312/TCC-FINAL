<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '3']);

$conexion = app_db_connect();
if (!$conexion) {
    App\Support\Db::fail('No se pudo conectar con la base de datos.', 'InterfazObtenerPedidos.php: ' . mysqli_connect_error());
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

$pedidoController = new App\Controllers\PedidoController();
$productoController = new App\Controllers\ProductoController();
$productos = array_values(array_filter($productoController->listar(), function ($producto) {
    return ($producto['estado'] ?? 'Activo') === 'Activo' && (int) ($producto['stock'] ?? 0) > 0;
}));
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido'])) {
    csrf_verify_or_die('InterfazObtenerPedidos.php');

    $mesa = (int) ($_POST['mesa'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);

    if ($mesa <= 0 || !is_array($items) || empty($items)) {
        $mensaje = 'Selecciona una mesa y al menos un producto.';
        $tipoMensaje = 'error';
    } elseif ($pedidoController->crearPedidoMozo($mesa, $_SESSION['Usuario'], $observaciones, $items)) {
        app_set_flash('success', 'Pedido enviado a cocina correctamente.');
        app_redirect('InterfazObtenerPedidos.php');
    } else {
        $mensaje = 'No se pudo crear el pedido. Verifica la mesa y los productos.';
        $tipoMensaje = 'error';
    }
}

$pedidosConDetalle = $pedidoController->listarRecientes(30);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pedidos</title>
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<style>
:root{--bg:#111217;--panel:#1c1e25;--panel-soft:#272a33;--accent:#ff0055;--accent-soft:#ff4d8a;--text:#f4f4f4;--muted:#bdbdbd;--line:rgba(255,255,255,.1);--success:#22a95b}
*{box-sizing:border-box}
body{margin:0;padding:24px;background:radial-gradient(circle at top left,rgba(255,0,85,.16),transparent 34%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;max-width:1440px;margin:auto}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 15px;border-radius:8px;text-decoration:none;font-weight:700;border:0;cursor:pointer}.back{background:#343740;color:#fff}.primary{background:var(--accent);color:#fff;width:100%;font-size:1rem}.primary:hover,.add:hover{background:var(--accent-soft)}
h1{max-width:1440px;margin:18px auto;color:#fff;font-size:clamp(1.8rem,3vw,2.5rem)}.top span{color:#ff8fb3;font-weight:700}.grid{max-width:1440px;margin:auto;display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:18px}.panel{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:8px;padding:18px}.catalog-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}.catalog-head h2,.order-title{margin:0;font-size:1.2rem}.products{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px}.product{overflow:hidden;background:#15171d;border:1px solid var(--line);border-radius:8px}.product img{display:block;width:100%;height:132px;object-fit:cover;background:#0d0e12}.product-info{padding:12px}.product h3{margin:0 0 6px;font-size:1rem}.product p{color:var(--muted);font-size:.82rem;line-height:1.35;height:34px;margin:0 0 10px;overflow:hidden}.product-bottom{display:flex;justify-content:space-between;align-items:center;gap:8px}.price{font-weight:700;color:#fff}.add{border:0;border-radius:6px;background:var(--accent);color:#fff;padding:8px 10px;font-weight:700;cursor:pointer}.order-panel{align-self:start;position:sticky;top:16px}label{display:block;color:var(--muted);font-size:.86rem;margin:14px 0 6px}input,textarea{width:100%;padding:11px 12px;border-radius:6px;border:1px solid #424651;background:#101116;color:#fff;font:inherit}textarea{min-height:76px;resize:vertical}.cart{margin-top:16px;border-top:1px solid var(--line);max-height:330px;overflow:auto}.cart-empty{color:var(--muted);padding:18px 0;text-align:center}.cart-item{padding:12px 0;border-bottom:1px solid var(--line)}.cart-name{font-weight:700;font-size:.92rem}.cart-line{display:flex;justify-content:space-between;align-items:center;margin-top:8px;color:var(--muted);font-size:.88rem}.quantity{display:inline-flex;align-items:center;gap:8px}.quantity button{width:26px;height:26px;border:1px solid #515561;border-radius:5px;background:transparent;color:#fff;cursor:pointer}.total{display:flex;justify-content:space-between;font-size:1.15rem;font-weight:700;padding:16px 0}.alert{max-width:1440px;margin:0 auto 16px;padding:12px 14px;border-radius:6px;background:rgba(220,38,38,.16);border:1px solid rgba(220,38,38,.5);color:#fecaca}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;margin-top:14px}.card{background:#f5f5f6;color:#1f1f1f;border-radius:8px;padding:12px;border:1px solid #e8e8ea;box-shadow:0 8px 18px rgba(0,0,0,.14)}
.card-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px}
.card-title{margin:0;font-size:1.06rem}
.meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 10px;font-size:.92rem}
.meta strong{color:#141414}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;color:#fff;font-size:.82rem;font-weight:700}
.badge.entregado{background:#1f9d55}.badge.preparando{background:#2563eb}.badge.cancelado{background:#dc2626}.badge.pendiente{background:#f59e0b}
.ul{margin:7px 0 0;padding-left:18px;font-size:.93rem}
.empty{color:var(--muted)}
@media (max-width:1000px){.grid{grid-template-columns:1fr}.order-panel{position:static}.cards{grid-template-columns:1fr}.meta{grid-template-columns:1fr}}@media (max-width:620px){body{padding:15px}.products{grid-template-columns:repeat(2,minmax(0,1fr))}.product img{height:105px}.product-info{padding:10px}.product p{display:none}.top span{display:none}}
</style>
</head>
<body>

<div class="top">
    <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
    <span style="color:#ff8fb3;font-weight:700">Gestión de Pedidos</span>
</div>

<h1>Tomar pedido</h1>

<?php if ($mensaje !== '') { ?>
<div class="alert <?php echo h($tipoMensaje); ?>"><?php echo h($mensaje); ?></div>
<?php } ?>

<div class="grid">
    <div class="panel">
        <div class="catalog-head"><h2>Menu disponible</h2><span><?php echo count($productos); ?> productos</span></div>
        <div class="products">
        <?php foreach ($productos as $producto) { ?>
            <?php $rutaImagen = trim((string) ($producto['img'] ?? '')); $imagen = $rutaImagen !== '' && is_file(__DIR__ . '/MVCsix1.0/' . $rutaImagen) ? 'MVCsix1.0/' . $rutaImagen : 'MVCsix1.0/img/default_comida_hq.jpg'; ?>
            <article class="product">
                <img src="<?php echo h($imagen); ?>" alt="<?php echo h($producto['nombre']); ?>">
                <div class="product-info"><h3><?php echo h($producto['nombre']); ?></h3><p><?php echo h($producto['descripcion']); ?></p><div class="product-bottom"><span class="price">$<?php echo number_format((float) $producto['precio'], 2, ',', '.'); ?></span><button class="add" type="button" data-id="<?php echo (int) $producto['id_producto']; ?>" data-name="<?php echo h($producto['nombre']); ?>" data-price="<?php echo h((string) $producto['precio']); ?>">Agregar</button></div></div>
            </article>
        <?php } ?>
        </div>
    </div>

    <form class="panel order-panel" method="post" id="order-form">
        <?php echo csrf_field(); ?>
        <h2 class="order-title">Pedido actual</h2>
        <label for="mesa">Mesa</label><input type="number" id="mesa" name="mesa" min="1" required>
        <label for="observaciones">Notas para cocina</label><textarea id="observaciones" name="observaciones" maxlength="500" placeholder="Sin cebolla, extra salsa..."></textarea>
        <input type="hidden" name="items" id="items">
        <div id="cart" class="cart"><div class="cart-empty">Agrega productos desde el menu.</div></div>
        <div class="total"><span>Total</span><span id="total">$0,00</span></div>
        <button class="btn primary" type="submit" name="crear_pedido">Enviar a cocina</button>
    </form>
</div>

<div class="panel" style="max-width:1440px;margin:18px auto">
    <h2 class="order-title">Ultimos pedidos</h2>
    <div class="cards">
        <?php foreach ($pedidosConDetalle as $pedido) { ?>
        <div class="card"><div class="card-head"><h4 class="card-title">Pedido #<?php echo (int) $pedido['id_pedido']; ?></h4><span class="badge <?php echo h(estadoClass($pedido['estado'])); ?>"><?php echo h($pedido['estado']); ?></span></div><div class="meta"><div><strong>Mesa:</strong> <?php echo (int) $pedido['id_mesa']; ?></div><div><strong>Total:</strong> $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></div><div style="grid-column:1 / -1"><strong>Fecha:</strong> <?php echo h($pedido['fecha']); ?></div></div></div>
        <?php } ?>
    </div>
</div>

<script>
const cart = new Map();
const money = value => '$' + value.toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2});
function renderCart() {
    const container = document.getElementById('cart');
    const items = [...cart.values()];
    document.getElementById('items').value = JSON.stringify(items.map(item => ({IDProducto: item.id, quantity: item.quantity})));
    if (!items.length) { container.innerHTML = '<div class="cart-empty">Agrega productos desde el menu.</div>'; document.getElementById('total').textContent = '$0,00'; return; }
    let total = 0;
    container.innerHTML = items.map(item => { total += item.price * item.quantity; return `<div class="cart-item"><div class="cart-name">${item.name}</div><div class="cart-line"><span>${money(item.price * item.quantity)}</span><span class="quantity"><button type="button" data-change="-1" data-id="${item.id}">-</button>${item.quantity}<button type="button" data-change="1" data-id="${item.id}">+</button></span></div></div>`; }).join('');
    document.getElementById('total').textContent = money(total);
}
document.querySelectorAll('.add').forEach(button => button.addEventListener('click', () => { const id = Number(button.dataset.id); const item = cart.get(id) || {id, name: button.dataset.name, price: Number(button.dataset.price), quantity: 0}; item.quantity++; cart.set(id, item); renderCart(); }));
document.getElementById('cart').addEventListener('click', event => { const button = event.target.closest('button[data-change]'); if (!button) return; const item = cart.get(Number(button.dataset.id)); item.quantity += Number(button.dataset.change); if (item.quantity < 1) cart.delete(item.id); renderCart(); });
document.getElementById('order-form').addEventListener('submit', event => { if (!cart.size) { event.preventDefault(); alert('Agrega al menos un producto al pedido.'); } });
</script>

</body>
</html>
