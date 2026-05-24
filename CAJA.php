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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facturar'])) {
    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $metodo = trim($_POST['metodo_pago'] ?? '');
    $metodosPermitidos = ['Efectivo', 'Tarjeta', 'Transferencia'];

    if ($idPedido > 0 && in_array($metodo, $metodosPermitidos, true)) {
        mysqli_begin_transaction($conexion);

        try {
            $stmtPedido = mysqli_prepare($conexion, 'SELECT id_mesa, total FROM pedidos WHERE id_pedido = ? LIMIT 1');
            mysqli_stmt_bind_param($stmtPedido, 'i', $idPedido);
            mysqli_stmt_execute($stmtPedido);
            $resPedido = mysqli_stmt_get_result($stmtPedido);
            $pedido = $resPedido ? mysqli_fetch_assoc($resPedido) : null;
            mysqli_stmt_close($stmtPedido);

            if ($pedido) {
                $stmtPagoExistente = mysqli_prepare($conexion, 'SELECT id_pago FROM pagos WHERE id_pedido = ? LIMIT 1');
                mysqli_stmt_bind_param($stmtPagoExistente, 'i', $idPedido);
                mysqli_stmt_execute($stmtPagoExistente);
                $resPagoExistente = mysqli_stmt_get_result($stmtPagoExistente);
                $pagoExistente = $resPagoExistente ? mysqli_fetch_assoc($resPagoExistente) : null;
                mysqli_stmt_close($stmtPagoExistente);

                if (!$pagoExistente) {
                    $monto = (float) $pedido['total'];
                    $stmtPago = mysqli_prepare($conexion, 'INSERT INTO pagos (id_pedido, metodo_pago, monto) VALUES (?, ?, ?)');
                    mysqli_stmt_bind_param($stmtPago, 'isd', $idPedido, $metodo, $monto);
                    mysqli_stmt_execute($stmtPago);
                    mysqli_stmt_close($stmtPago);
                }

                $idMesa = (int) $pedido['id_mesa'];
                $estadoLibre = 'Libre';
                $stmtMesa = mysqli_prepare($conexion, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
                mysqli_stmt_bind_param($stmtMesa, 'si', $estadoLibre, $idMesa);
                mysqli_stmt_execute($stmtMesa);
                mysqli_stmt_close($stmtMesa);
            }

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
        }
    }

    header('Location: /proj/caja.php');
    exit();
}

$sqlResumenCaja = "
    SELECT
        COALESCE(SUM(monto), 0) AS recaudadoHoy,
        COUNT(*) AS pagosHoy
    FROM pagos
    WHERE DATE(fecha) = CURDATE()
";
$resResumen = mysqli_query($conexion, $sqlResumenCaja);
$resumen = $resResumen ? mysqli_fetch_assoc($resResumen) : ['recaudadoHoy' => 0, 'pagosHoy' => 0];

$pedidosPendientes = [];
$stmtPendientes = mysqli_prepare(
    $conexion,
    "SELECT p.id_pedido, p.id_mesa, p.fecha, p.total, p.estado
     FROM pedidos p
     LEFT JOIN pagos pa ON pa.id_pedido = p.id_pedido
    WHERE p.estado IN ('Entregado','ArchivadoCocina') AND pa.id_pago IS NULL
     ORDER BY p.fecha DESC"
);
mysqli_stmt_execute($stmtPendientes);
$resPendientes = mysqli_stmt_get_result($stmtPendientes);
while ($fila = mysqli_fetch_assoc($resPendientes)) {
    $pedidosPendientes[] = $fila;
}
mysqli_stmt_close($stmtPendientes);

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Caja</title>
<style>
:root{--bg:#171717;--panel:#242424;--panel-soft:#2c2c2c;--accent:#ff0055;--text:#f4f4f4;--muted:#bdbdbd;--line:rgba(255,255,255,.08)}
*{box-sizing:border-box}
body{margin:0;padding:20px;background:radial-gradient(circle at top left, rgba(255,0,85,.15), transparent 30%),var(--bg);color:var(--text);font-family:Arial,Helvetica,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;border:0;cursor:pointer}
.back{background:#3a3a3a;color:#fff}.logout{background:var(--accent);color:#fff}
h1{text-align:center;color:var(--accent);margin:14px 0 20px;font-size:clamp(1.8rem,3vw,2.6rem)}
.summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:16px}
.card{background:linear-gradient(180deg,var(--panel),var(--panel-soft));border:1px solid var(--line);border-radius:14px;padding:14px}
.label{color:var(--muted);font-size:.9rem}.value{font-size:1.7rem;font-weight:800;margin-top:6px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.order{background:#fff;color:#1f1f1f;border-radius:14px;padding:14px}
ul{padding-left:18px;margin-top:8px}
select,button{padding:10px;border-radius:8px;border:1px solid #ccc}
.facturar{background:#1f9d55;color:#fff;border:0;cursor:pointer;font-weight:700}
.empty{color:var(--muted)}
@media (max-width:900px){.summary,.grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="top">
    <a class="btn back" href="/proj/Principal.php">← Volver</a>
    <a class="btn logout" href="/proj/Salir.php">Salir</a>
</div>

<h1>Panel de Caja</h1>

<div class="summary">
    <div class="card"><div class="label">Recaudado hoy</div><div class="value">$<?php echo number_format((float) ($resumen['recaudadoHoy'] ?? 0), 2, ',', '.'); ?></div></div>
    <div class="card"><div class="label">Pagos registrados hoy</div><div class="value"><?php echo (int) ($resumen['pagosHoy'] ?? 0); ?></div></div>
</div>

<div class="grid">
<?php if (!empty($pedidosPendientes)) { ?>
    <?php foreach ($pedidosPendientes as $pedido) { ?>
        <?php $idPedido = (int) $pedido['id_pedido']; ?>
        <div class="order">
            <h3 style="margin:0 0 8px">Pedido #<?php echo $idPedido; ?></h3>
            <div><strong>Mesa:</strong> <?php echo (int) $pedido['id_mesa']; ?></div>
            <div><strong>Fecha:</strong> <?php echo h($pedido['fecha']); ?></div>
            <div><strong>Total:</strong> $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></div>

            <ul>
                <?php if (!empty($detallePorPedido[$idPedido])) { ?>
                    <?php foreach ($detallePorPedido[$idPedido] as $d) { ?>
                        <li><?php echo (int) $d['cantidad']; ?> x <?php echo h($d['nombre']); ?> ($<?php echo number_format((float) $d['subtotal'], 2, ',', '.'); ?>)</li>
                    <?php } ?>
                <?php } else { ?>
                    <li class="empty">Sin detalle</li>
                <?php } ?>
            </ul>

            <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
                <input type="hidden" name="id_pedido" value="<?php echo $idPedido; ?>">
                <select name="metodo_pago" required>
                    <option value="">Método de pago</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <button class="facturar" type="submit" name="facturar">Facturar</button>
            </form>
        </div>
    <?php } ?>
<?php } else { ?>
    <div class="card empty">No hay pedidos entregados pendientes de cobro.</div>
<?php } ?>
</div>

</body>
</html>
