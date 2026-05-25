<?php

session_start();

if (!isset($_SESSION['Usuario'])) {
    header('Location: /proj/Login.php');
    exit();
}

$conexion = mysqli_connect('localhost', 'root', '', 'ProyectoMagnus');
if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}
mysqli_set_charset($conexion, 'utf8mb4');

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

function obtenerIdUsuario($conexion, $usuario)
{
    $stmt = mysqli_prepare($conexion, 'SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1');
    if (!$stmt) {
        return 1;
    }

    mysqli_stmt_bind_param($stmt, 's', $usuario);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['id_usuario'] ?? 1);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
    $mesa = (int) ($_POST['mesa'] ?? 0);
    $productoManual = trim($_POST['producto_manual'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $cantidad = (int) ($_POST['cantidad'] ?? 0);

    if ($mesa <= 0 || $productoManual === '' || $precio <= 0 || $cantidad <= 0) {
        die('Datos inválidos para crear el pedido.');
    }

    $idUsuario = obtenerIdUsuario($conexion, $_SESSION['Usuario']);
    mysqli_begin_transaction($conexion);

    try {
        $stmtBuscar = mysqli_prepare($conexion, 'SELECT id_producto FROM productos WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($stmtBuscar, 's', $productoManual);
        mysqli_stmt_execute($stmtBuscar);
        $resBuscar = mysqli_stmt_get_result($stmtBuscar);
        $filaProd = $resBuscar ? mysqli_fetch_assoc($resBuscar) : null;
        mysqli_stmt_close($stmtBuscar);

        if ($filaProd) {
            $idProducto = (int) $filaProd['id_producto'];
        } else {
            $descripcion = 'Producto agregado manualmente';
            $stock = 100;
            $estadoProducto = 'Activo';
            $stmtNuevoProducto = mysqli_prepare($conexion, 'INSERT INTO productos (nombre, descripcion, precio, stock, estado) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmtNuevoProducto, 'ssdis', $productoManual, $descripcion, $precio, $stock, $estadoProducto);
            mysqli_stmt_execute($stmtNuevoProducto);
            mysqli_stmt_close($stmtNuevoProducto);
            $idProducto = mysqli_insert_id($conexion);
        }

        $subtotal = $precio * $cantidad;
        $estadoPedido = 'Pendiente';

        $stmtPedido = mysqli_prepare($conexion, 'INSERT INTO pedidos (id_mesa, id_usuario, total, estado) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmtPedido, 'iids', $mesa, $idUsuario, $subtotal, $estadoPedido);
        mysqli_stmt_execute($stmtPedido);
        mysqli_stmt_close($stmtPedido);

        $estadoMesa = 'Ocupada';
        $stmtMesa = mysqli_prepare($conexion, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
        mysqli_stmt_bind_param($stmtMesa, 'si', $estadoMesa, $mesa);
        mysqli_stmt_execute($stmtMesa);
        mysqli_stmt_close($stmtMesa);

        $idPedido = mysqli_insert_id($conexion);

        $stmtDetalle = mysqli_prepare($conexion, 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmtDetalle, 'iiidd', $idPedido, $idProducto, $cantidad, $precio, $subtotal);
        mysqli_stmt_execute($stmtDetalle);
        mysqli_stmt_close($stmtDetalle);

        mysqli_commit($conexion);
        header('Location: /proj/InterfazObtenerPedidos.php');
        exit();
    } catch (Throwable $e) {
        mysqli_rollback($conexion);
        die('No se pudo crear el pedido.');
    }
}

$pedidos = [];
$stmtPedidos = mysqli_prepare($conexion, 'SELECT id_pedido, id_mesa, fecha, estado, total FROM pedidos ORDER BY fecha DESC LIMIT 30');
mysqli_stmt_execute($stmtPedidos);
$resPedidos = mysqli_stmt_get_result($stmtPedidos);
while ($fila = mysqli_fetch_assoc($resPedidos)) {
    $pedidos[] = $fila;
}
mysqli_stmt_close($stmtPedidos);

$pedidosConDetalle = [];
foreach ($pedidos as $pedido) {
    $idPedido = (int) $pedido['id_pedido'];
    $stmtDetalle = mysqli_prepare(
        $conexion,
        'SELECT dp.cantidad, dp.precio, dp.subtotal, pr.nombre
         FROM detalle_pedido dp
         INNER JOIN productos pr ON pr.id_producto = dp.id_producto
         WHERE dp.id_pedido = ?
         ORDER BY pr.nombre ASC'
    );
    mysqli_stmt_bind_param($stmtDetalle, 'i', $idPedido);
    mysqli_stmt_execute($stmtDetalle);
    $resDetalle = mysqli_stmt_get_result($stmtDetalle);

    $productos = [];
    while ($row = mysqli_fetch_assoc($resDetalle)) {
        $productos[] = $row;
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
<title>Pedidos</title>
<script src="/proj/no-popups.js"></script>
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
    <a class="btn back" href="/proj/Principal.php">← Volver</a>
    <span style="color:#ff8fb3;font-weight:700">Gestión de Pedidos</span>
</div>

<h1>Pedidos</h1>

<div class="grid">
    <div class="panel">
        <h3 style="margin-top:0">Crear pedido rápido</h3>
        <form method="post">
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
