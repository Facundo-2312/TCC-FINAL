<?php

session_start();

if (!isset($_SESSION['Usuario'])) {
    header('Location: /proj/Login.php');
    exit();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$conexion = mysqli_connect('localhost', 'root', '', 'ProyectoMagnus');
if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}

mysqli_set_charset($conexion, 'utf8mb4');

$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

if ($desde > $hasta) {
    $tmp = $desde;
    $desde = $hasta;
    $hasta = $tmp;
}

$estadoEntregado = 'Entregado';

function ejecutarEscalar($conexion, $sql, $tipos = '', $valores = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return null;
    }

    if ($tipos !== '' && !empty($valores)) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $fila = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return $fila;
}

function ejecutarLista($conexion, $sql, $tipos = '', $valores = [])
{
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return [];
    }

    if ($tipos !== '' && !empty($valores)) {
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $lista = [];

    if ($res) {
        while ($fila = mysqli_fetch_assoc($res)) {
            $lista[] = $fila;
        }
    }

    mysqli_stmt_close($stmt);
    return $lista;
}

$sqlResumen = "
    SELECT
        COUNT(*) AS totalPedidos,
        COALESCE(SUM(total), 0) AS totalVendido,
        COALESCE(AVG(total), 0) AS ticketPromedio,
        COALESCE(SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END), 0) AS pedidosEntregados,
        COALESCE(SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END), 0) AS pedidosPendientes,
        COALESCE(SUM(CASE WHEN estado = 'Preparando' THEN 1 ELSE 0 END), 0) AS pedidosPreparando,
        COALESCE(SUM(CASE WHEN estado = 'Cancelado' THEN 1 ELSE 0 END), 0) AS pedidosCancelados
    FROM pedidos
    WHERE DATE(fecha) BETWEEN ? AND ?
";
$resumen = ejecutarEscalar($conexion, $sqlResumen, 'ss', [$desde, $hasta]);

$sqlResumenHoy = "
    SELECT
        COUNT(*) AS totalPedidosHoy,
        COALESCE(SUM(total), 0) AS totalVendidoHoy
    FROM pedidos
    WHERE DATE(fecha) = CURDATE() AND estado = 'Entregado'
";
$resumenHoy = ejecutarEscalar($conexion, $sqlResumenHoy);

$sqlTopProductos = "
    SELECT pr.nombre, SUM(dp.cantidad) AS cantidadTotal, COALESCE(SUM(dp.subtotal), 0) AS ventasTotales
    FROM detalle_pedido dp
    INNER JOIN pedidos p ON p.id_pedido = dp.id_pedido
    INNER JOIN productos pr ON pr.id_producto = dp.id_producto
    WHERE DATE(p.fecha) BETWEEN ? AND ?
    GROUP BY pr.nombre
    ORDER BY cantidadTotal DESC, ventasTotales DESC
    LIMIT 5
";
$topProductos = ejecutarLista($conexion, $sqlTopProductos, 'ss', [$desde, $hasta]);

$sqlPedidosRecientes = "
    SELECT id_pedido, id_mesa, fecha, estado, total
    FROM pedidos
    WHERE DATE(fecha) BETWEEN ? AND ?
    ORDER BY fecha DESC
    LIMIT 8
";
$pedidosRecientes = ejecutarLista($conexion, $sqlPedidosRecientes, 'ss', [$desde, $hasta]);

$sqlPedidosDelRango = "
    SELECT id_pedido, id_mesa, fecha, estado, total
    FROM pedidos
    WHERE DATE(fecha) BETWEEN ? AND ?
    ORDER BY fecha DESC
";
$pedidosDelRango = ejecutarLista($conexion, $sqlPedidosDelRango, 'ss', [$desde, $hasta]);

$pedidosConProductos = [];
foreach ($pedidosDelRango as $pedido) {
    $sqlProductos = "
        SELECT dp.cantidad, pr.nombre, dp.subtotal
        FROM detalle_pedido dp
        INNER JOIN productos pr ON pr.id_producto = dp.id_producto
        WHERE dp.id_pedido = ?
        ORDER BY pr.nombre ASC
    ";
    $productos = ejecutarLista($conexion, $sqlProductos, 'i', [(int) $pedido['id_pedido']]);
    $pedido['productos'] = $productos;
    $pedidosConProductos[] = $pedido;
}

function estadoBadgeClass($estado)
{
    $estado = strtolower((string) $estado);

    if ($estado === 'entregado') {
        return 'entregado';
    }

    if ($estado === 'preparando') {
        return 'preparando';
    }

    if ($estado === 'cancelado') {
        return 'cancelado';
    }

    return 'pendiente';
}

$estadoData = [
    'Entregado' => (int) ($resumen['pedidosEntregados'] ?? 0),
    'Pendiente' => (int) ($resumen['pedidosPendientes'] ?? 0),
    'Preparando' => (int) ($resumen['pedidosPreparando'] ?? 0),
    'Cancelado' => (int) ($resumen['pedidosCancelados'] ?? 0),
];

$topProductosData = [
    'labels' => [],
    'values' => [],
];

foreach ($topProductos as $productoTop) {
    $topProductosData['labels'][] = $productoTop['nombre'];
    $topProductosData['values'][] = (int) $productoTop['cantidadTotal'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informes</title>
<script src="/proj/no-popups.js"></script>
<style>
:root{
    --bg: #171717;
    --panel: #232323;
    --panel-soft: #2b2b2b;
    --text: #f4f4f4;
    --muted: #bdbdbd;
    --accent: #ff0055;
    --accent-2: #ff5f87;
    --line: rgba(255,255,255,.08);
    --shadow: 0 18px 45px rgba(0,0,0,.35);
}

* { box-sizing: border-box; }

body{
    margin: 0;
    padding: 22px;
    background:
      radial-gradient(circle at top left, rgba(255,0,85,.12), transparent 28%),
      radial-gradient(circle at bottom right, rgba(255,95,135,.08), transparent 22%),
      var(--bg);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}

.back-link{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--accent);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 700;
    box-shadow: var(--shadow);
}

.header{
    margin: 26px 0 20px;
    text-align: center;
}

.header h1{
    margin: 0;
    font-size: clamp(2rem, 3vw, 3rem);
    color: var(--accent);
    letter-spacing: .5px;
}

.header p{
    margin: 10px 0 0;
    color: var(--muted);
}

.filters,
.summary-grid,
.top-grid,
.orders-grid{
    display: grid;
    gap: 16px;
}

.filters{
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-bottom: 18px;
}

.filters form,
.card,
.panel{
    background: linear-gradient(180deg, var(--panel), var(--panel-soft));
    border: 1px solid var(--line);
    border-radius: 18px;
    box-shadow: var(--shadow);
}

.filters form{
    padding: 16px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: end;
}

.filters label{
    display: block;
    font-size: .9rem;
    color: var(--muted);
    margin-bottom: 6px;
}

.filters input{
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #444;
    background: #141414;
    color: white;
}

.filters .actions{
    display: flex;
    gap: 10px;
    align-items: end;
}

.btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 10px;
    padding: 12px 16px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}

.btn.primary{ background: var(--accent); color: white; }
.btn.ghost{ background: #3a3a3a; color: white; }

.summary-grid{
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin: 20px 0;
}

.kpi{
    padding: 18px;
}

.kpi .label{
    color: var(--muted);
    font-size: .9rem;
    margin-bottom: 8px;
}

.kpi .value{
    font-size: 1.8rem;
    font-weight: 800;
}

.kpi .sub{
    margin-top: 6px;
    color: var(--muted);
    font-size: .92rem;
}

.section-title{
    margin: 28px 0 14px;
    font-size: 1.25rem;
    color: white;
}

.top-grid{
    grid-template-columns: 1.1fr .9fr;
    margin-bottom: 22px;
}

.card{ padding: 18px; }
.panel{ padding: 18px; }

.charts-grid{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin: 22px 0;
}

.chart-box{
    min-height: 320px;
}

.chart-box canvas{
    width: 100% !important;
    max-height: 280px;
}

.table-wrap{
    overflow-x: auto;
}

table{
    width: 100%;
    border-collapse: collapse;
}

th, td{
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    vertical-align: top;
}

th{
    color: var(--accent-2);
    font-size: .92rem;
}

.badge{
    display: inline-block;
    padding: 6px 10px;
    border-radius: 999px;
    color: white;
    font-size: .82rem;
    font-weight: 700;
}

.badge.entregado{ background: #1f9d55; }
.badge.preparando{ background: #2563eb; }
.badge.cancelado{ background: #dc2626; }
.badge.pendiente{ background: #f59e0b; }

.products-list{
    margin: 10px 0 0;
    padding-left: 18px;
    color: var(--muted);
}

.empty{
    color: var(--muted);
    padding: 12px 0;
}

@media (max-width: 1100px){
    .summary-grid,
    .top-grid,
    .filters,
    .charts-grid{
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 720px){
    body{ padding: 16px; }
    .summary-grid,
    .top-grid,
    .filters,
    .charts-grid{
        grid-template-columns: 1fr;
    }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<a href="/proj/Principal.php" class="back-link">← Volver</a>

<div class="header">
    <h1>INFORMES DE VENTAS</h1>
    <p>Rango analizado: <?php echo h($desde); ?> a <?php echo h($hasta); ?></p>
</div>

<div class="filters">
    <form method="get">
        <div style="flex:1; min-width: 180px;">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="<?php echo h($desde); ?>">
        </div>
        <div style="flex:1; min-width: 180px;">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="<?php echo h($hasta); ?>">
        </div>
        <div class="actions">
            <button class="btn primary" type="submit">Filtrar</button>
            <a class="btn ghost" href="ListaPedido.php">Hoy</a>
        </div>
    </form>
</div>

<div class="summary-grid">
    <div class="card kpi">
        <div class="label">Total vendido en el rango</div>
        <div class="value">$<?php echo number_format((float) ($resumen['totalVendido'] ?? 0), 2, ',', '.'); ?></div>
        <div class="sub"><?php echo (int) ($resumen['totalPedidos'] ?? 0); ?> pedidos analizados</div>
    </div>
    <div class="card kpi">
        <div class="label">Ticket promedio</div>
        <div class="value">$<?php echo number_format((float) ($resumen['ticketPromedio'] ?? 0), 2, ',', '.'); ?></div>
        <div class="sub">Promedio por pedido</div>
    </div>
    <div class="card kpi">
        <div class="label">Pedidos entregados</div>
        <div class="value"><?php echo (int) ($resumen['pedidosEntregados'] ?? 0); ?></div>
        <div class="sub">Estado completado</div>
    </div>
    <div class="card kpi">
        <div class="label">Ventas hoy</div>
        <div class="value">$<?php echo number_format((float) ($resumenHoy['totalVendidoHoy'] ?? 0), 2, ',', '.'); ?></div>
        <div class="sub"><?php echo (int) ($resumenHoy['totalPedidosHoy'] ?? 0); ?> pedidos entregados hoy</div>
    </div>
</div>

<div class="top-grid">
    <div class="panel">
        <div class="section-title">Estados del período</div>
        <table>
            <tr><th>Estado</th><th>Cantidad</th></tr>
            <tr><td>Entregado</td><td><?php echo (int) ($resumen['pedidosEntregados'] ?? 0); ?></td></tr>
            <tr><td>Pendiente</td><td><?php echo (int) ($resumen['pedidosPendientes'] ?? 0); ?></td></tr>
            <tr><td>Preparando</td><td><?php echo (int) ($resumen['pedidosPreparando'] ?? 0); ?></td></tr>
            <tr><td>Cancelado</td><td><?php echo (int) ($resumen['pedidosCancelados'] ?? 0); ?></td></tr>
        </table>
    </div>

    <div class="panel">
        <div class="section-title">Top productos</div>
        <?php if (!empty($topProductos)) { ?>
            <table>
                <tr><th>Producto</th><th>Cantidad</th><th>Ventas</th></tr>
                <?php foreach ($topProductos as $producto) { ?>
                    <tr>
                        <td><?php echo h($producto['nombre']); ?></td>
                        <td><?php echo (int) $producto['cantidadTotal']; ?></td>
                        <td>$<?php echo number_format((float) $producto['ventasTotales'], 2, ',', '.'); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <div class="empty">No hay productos vendidos en este rango.</div>
        <?php } ?>
    </div>
</div>

<div class="charts-grid">
    <div class="panel chart-box">
        <div class="section-title">Estados del período</div>
        <canvas id="statesChart"></canvas>
    </div>
    <div class="panel chart-box">
        <div class="section-title">Top productos por cantidad</div>
        <canvas id="productsChart"></canvas>
    </div>
</div>

<div class="panel">
    <div class="section-title">Pedidos recientes</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Mesa</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pedidosRecientes)) { ?>
                    <?php foreach ($pedidosRecientes as $pedido) { ?>
                        <tr>
                            <td>#<?php echo h($pedido['id_pedido']); ?></td>
                            <td><?php echo h($pedido['id_mesa']); ?></td>
                            <td><?php echo h($pedido['fecha']); ?></td>
                            <td><span class="badge <?php echo h(estadoBadgeClass($pedido['estado'])); ?>"><?php echo h($pedido['estado']); ?></span></td>
                            <td>$<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></td>
                            <td>
                                <?php
                                $productosPedido = array_values(array_filter($pedidosConProductos, function ($item) use ($pedido) {
                                    return (int) $item['id_pedido'] === (int) $pedido['id_pedido'];
                                }));
                                $productosPedido = $productosPedido[0]['productos'] ?? [];
                                ?>
                                <?php if (!empty($productosPedido)) { ?>
                                    <ul class="products-list">
                                        <?php foreach ($productosPedido as $prod) { ?>
                                            <li><?php echo (int) $prod['cantidad']; ?> x <?php echo h($prod['nombre']); ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } else { ?>
                                    <span class="empty">Sin detalle</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="empty">No hay pedidos en este período.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const estadoData = <?php echo json_encode($estadoData, JSON_UNESCAPED_UNICODE); ?>;
const topProductosData = <?php echo json_encode($topProductosData, JSON_UNESCAPED_UNICODE); ?>;

const statesCtx = document.getElementById('statesChart');
if (statesCtx) {
    new Chart(statesCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(estadoData),
            datasets: [{
                data: Object.values(estadoData),
                backgroundColor: ['#1f9d55', '#f59e0b', '#2563eb', '#dc2626'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: '#f4f4f4' }
                }
            }
        }
    });
}

const productsCtx = document.getElementById('productsChart');
if (productsCtx) {
    new Chart(productsCtx, {
        type: 'bar',
        data: {
            labels: topProductosData.labels,
            datasets: [{
                label: 'Cantidad vendida',
                data: topProductosData.values,
                backgroundColor: '#ff0055'
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { ticks: { color: '#f4f4f4' }, grid: { color: 'rgba(255,255,255,.08)' } },
                y: { ticks: { color: '#f4f4f4' }, grid: { color: 'rgba(255,255,255,.08)' } }
            },
            plugins: {
                legend: {
                    labels: { color: '#f4f4f4' }
                }
            }
        }
    });
}
</script>

</body>
</html>
