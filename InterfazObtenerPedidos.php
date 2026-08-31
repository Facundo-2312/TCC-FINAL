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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    csrf_verify_or_die('InterfazObtenerPedidos.php');

    $mesa = (int) ($_POST['mesa'] ?? 0);
    $productoManual = trim($_POST['producto_manual'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $cantidad = (int) ($_POST['cantidad'] ?? 0);

    if ($mesa <= 0 || $productoManual === '' || $precio <= 0 || $cantidad <= 0) {
        die('Datos inválidos para crear el pedido.');
    }

    if ($pedidoController->crearPedidoRapido($mesa, $productoManual, $precio, $cantidad, $_SESSION['Usuario'])) {
        app_redirect('InterfazObtenerPedidos.php');
    }

    die('No se pudo crear el pedido.');
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
:root{--bg:#171717;--panel:#242424;--panel-soft:#2c2c2c;--accent:#ff0055;--text:#f4f4f4;--muted:#bdbdbd;--line:rgba(255,255,255,.08)}
*{box-sizing:border-box}
body{margin:0;padding:20px;background:radial-gradient(circle at top left, rgba(255,0,85,.15), transparent 30%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;border:0;cursor:pointer}
.back{background:#3a3a3a;color:#fff}.primary{background:var(--accent);color:#fff}
h1{text-align:center;color:var(--accent);margin:14px 0 20px;font-size:clamp(1.8rem,3vw,2.6rem)}
.grid{display:grid;grid-template-columns:360px 1fr;gap:16px}
.panel{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:14px;padding:16px}
label{display:block;color:var(--muted);font-size:.9rem;margin-bottom:5px}
input{width:100%;padding:11px 12px;border-radius:10px;border:1px solid #444;background:#121212;color:#fff;margin-bottom:12px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:12px}
.card{background:linear-gradient(180deg,#f9f9fb,#eeeeef);color:#1f1f1f;border-radius:12px;padding:12px;border:1px solid #e8e8ea;box-shadow:0 8px 18px rgba(0,0,0,.14)}
.card-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px}
.card-title{margin:0;font-size:1.06rem}
.meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 10px;font-size:.92rem}
.meta strong{color:#141414}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;color:#fff;font-size:.82rem;font-weight:700}
.badge.entregado{background:#1f9d55}.badge.preparando{background:#2563eb}.badge.cancelado{background:#dc2626}.badge.pendiente{background:#f59e0b}
.ul{margin:7px 0 0;padding-left:18px;font-size:.93rem}
.empty{color:var(--muted)}
@media (max-width:1000px){.grid{grid-template-columns:1fr}.cards{grid-template-columns:1fr}.meta{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="top">
    <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
    <span style="color:#ff8fb3;font-weight:700">Gestión de Pedidos</span>
</div>

<h1>Pedidos</h1>

<div class="grid">
    <div class="panel">
        <h3 style="margin-top:0">Crear pedido rápido</h3>
        <form method="post">
            <?php echo csrf_field(); ?>
            <label for="mesa">Mesa</label>
            <input type="number" id="mesa" name="mesa" min="1" required>

            <label for="producto_manual">Producto</label>
            <input type="text" id="producto_manual" name="producto_manual" placeholder="Ej: Pizza" required>

            <label for="precio">Precio</label>
            <input type="number" id="precio" name="precio" min="0.01" step="0.01" required>

            <label for="cantidad">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" min="1" required>

            <button class="btn primary" type="submit" name="crear">Crear Pedido</button>
        </form>
    </div>

    <div class="panel">
        <h3 style="margin-top:0">Últimos pedidos</h3>
        <div class="cards">
            <?php if (!empty($pedidosConDetalle)) { ?>
                <?php foreach ($pedidosConDetalle as $pedido) { ?>
                    <div class="card">
                        <div class="card-head">
                            <h4 class="card-title">Pedido #<?php echo (int) $pedido['id_pedido']; ?></h4>
                            <span class="badge <?php echo h(estadoClass($pedido['estado'])); ?>"><?php echo h($pedido['estado']); ?></span>
                        </div>

                        <div class="meta">
                            <div><strong>Mesa:</strong> <?php echo (int) $pedido['id_mesa']; ?></div>
                            <div><strong>Total:</strong> $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></div>
                            <div style="grid-column:1 / -1"><strong>Fecha:</strong> <?php echo h($pedido['fecha']); ?></div>
                        </div>

                        <ul class="ul">
                            <?php if (!empty($pedido['productos'])) { ?>
                                <?php foreach ($pedido['productos'] as $prod) { ?>
                                    <li><?php echo (int) $prod['cantidad']; ?> x <?php echo h($prod['nombre']); ?> ($<?php echo number_format((float) $prod['subtotal'], 2, ',', '.'); ?>)</li>
                                <?php } ?>
                            <?php } else { ?>
                                <li class="empty">Sin detalle</li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty">No hay pedidos registrados.</div>
            <?php } ?>
        </div>
    </div>
</div>

</body>
</html>
