<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$conexion = app_db_connect();
if (!$conexion) {
    App\Support\Db::fail('No se pudo conectar con la base de datos.');
}

$servicioReserva = new App\Services\ReservaService();

// Obtener fecha a analizar (default: hoy)
$fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
if (!strtotime($fecha)) {
    $fecha = date('Y-m-d');
}

// Estadísticas generales
$query = 'SELECT 
    COUNT(*) as total_reservas,
    SUM(cantidad_personas) as personas_totales,
    COUNT(CASE WHEN estado = "Confirmada" THEN 1 END) as confirmadas,
    COUNT(CASE WHEN estado = "Cancelada" THEN 1 END) as canceladas,
    COUNT(CASE WHEN estado = "Completada" THEN 1 END) as completadas
FROM reservas 
WHERE DATE(hora_inicio) = ?';

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, 's', $fecha);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Ocupación por hora
$queryOcupacion = 'SELECT 
    HOUR(hora_inicio) as hora,
    COUNT(*) as cantidad,
    SUM(cantidad_personas) as personas
FROM reservas 
WHERE DATE(hora_inicio) = ? AND estado = "Confirmada"
GROUP BY HOUR(hora_inicio)
ORDER BY hora ASC';

$stmt = mysqli_prepare($conexion, $queryOcupacion);
mysqli_stmt_bind_param($stmt, 's', $fecha);
mysqli_stmt_execute($stmt);
$ocupacionPorHora = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Ocupación por mesa
$queryMesas = 'SELECT 
    m.id_mesa,
    m.numero,
    COUNT(r.id_reserva) as cantidad_reservas,
    SUM(r.cantidad_personas) as personas_totales
FROM mesas m
LEFT JOIN reservas r ON r.id_mesa = m.id_mesa AND DATE(r.hora_inicio) = ? AND r.estado = "Confirmada"
GROUP BY m.id_mesa, m.numero
ORDER BY m.numero ASC';

$stmt = mysqli_prepare($conexion, $queryMesas);
mysqli_stmt_bind_param($stmt, 's', $fecha);
mysqli_stmt_execute($stmt);
$ocupacionMesas = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Top clientes
$queryClientes = 'SELECT 
    nombre_cliente,
    COUNT(*) as cantidad,
    SUM(cantidad_personas) as personas,
    MAX(hora_inicio) as ultima_fecha
FROM reservas 
WHERE estado = "Confirmada"
GROUP BY nombre_cliente
ORDER BY cantidad DESC
LIMIT 10';

$stmt = mysqli_prepare($conexion, $queryClientes);
mysqli_stmt_execute($stmt);
$topClientes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Preparar datos para gráficos
$horasDisponibles = range(8, 23);
$datosOcupacion = array();
foreach ($horasDisponibles as $h) {
    $datosOcupacion[$h] = array('cantidad' => 0, 'personas' => 0);
}
foreach ($ocupacionPorHora as $o) {
    $datosOcupacion[(int)$o['hora']] = array(
        'cantidad' => (int)$o['cantidad'],
        'personas' => (int)$o['personas']
    );
}

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard de Ocupación</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>📈 Dashboard de Ocupación</h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>">← Mesas</a>
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
        </div>
    </div>

    <!-- Selector de Fecha -->
    <div class="filters">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="filter-group">
                <label for="fecha">📅 Fecha a Analizar</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
            </div>
            <button type="submit" class="btn-action reserve" style="padding: 10px 20px;">🔍 Cargar</button>
        </form>
    </div>

    <!-- Estadísticas Principales -->
    <div class="stats">
        <div class="stat-card libres">
            <div class="label">Total de Reservas</div>
            <div class="value"><?php echo (int)($stats['total_reservas'] ?? 0); ?></div>
        </div>
        <div class="stat-card ocupadas">
            <div class="label">Personas Esperadas</div>
            <div class="value"><?php echo (int)($stats['personas_totales'] ?? 0); ?></div>
        </div>
        <div class="stat-card limpieza">
            <div class="label">Confirmadas</div>
            <div class="value"><?php echo (int)($stats['confirmadas'] ?? 0); ?></div>
        </div>
        <div class="stat-card reservadas">
            <div class="label">Canceladas</div>
            <div class="value"><?php echo (int)($stats['canceladas'] ?? 0); ?></div>
        </div>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        
        <!-- Ocupación por Hora -->
        <div class="history-section">
            <div class="history-header">
                <h2>⏰ Ocupación por Hora</h2>
            </div>
            <canvas id="chartOcupacion" height="100"></canvas>
        </div>

        <!-- Ocupación por Mesa -->
        <div class="history-section">
            <div class="history-header">
                <h2>🍽️ Reservas por Mesa</h2>
            </div>
            <canvas id="chartMesas" height="100"></canvas>
        </div>

    </div>

    <!-- Tabla de Ocupación Detallada -->
    <div class="history-section" style="margin-top: 30px;">
        <div class="history-header">
            <h2>🍽️ Detalle de Ocupación por Mesa</h2>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 2px solid rgba(255, 255, 255, 0.1);">
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Mesa</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Reservas</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Personas</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Ocupación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ocupacionMesas as $m) { ?>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                            <td style="padding: 12px;"><strong>Mesa <?php echo (int)$m['numero']; ?></strong></td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo (int)$m['cantidad_reservas']; ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo (int)($m['personas_totales'] ?? 0); ?> 👥
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ((int)$m['cantidad_reservas'] > 0) { ?>
                                    <div style="background: rgba(38, 208, 124, 0.3); padding: 5px 10px; border-radius: 4px; color: #26d07c;">
                                        Ocupada
                                    </div>
                                <?php } else { ?>
                                    <div style="background: rgba(128, 128, 128, 0.2); padding: 5px 10px; border-radius: 4px; color: #aaa;">
                                        Libre
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="history-section" style="margin-top: 30px;">
        <div class="history-header">
            <h2>⭐ Top Clientes</h2>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 2px solid rgba(255, 255, 255, 0.1);">
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Cliente</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Reservas</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Total Personas</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Última Reserva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topClientes as $c) { ?>
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                            <td style="padding: 12px;"><strong><?php echo htmlspecialchars($c['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td style="padding: 12px; text-align: center;"><?php echo (int)$c['cantidad']; ?></td>
                            <td style="padding: 12px; text-align: center;"><?php echo (int)$c['personas']; ?> 👥</td>
                            <td style="padding: 12px; color: #aaa; font-size: 13px;">
                                <?php echo date('d/m/Y', strtotime($c['ultima_fecha'])); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
// Gráfico de Ocupación por Hora
var horasLabels = <?php echo json_encode(array_map(function($h) { return $h . ':00'; }, $horasDisponibles)); ?>;
var horasData = <?php echo json_encode(array_map(function($h) { return $datosOcupacion[$h]['personas']; }, $horasDisponibles)); ?>;
var horasReservas = <?php echo json_encode(array_map(function($h) { return $datosOcupacion[$h]['cantidad']; }, $horasDisponibles)); ?>;

var ctx1 = document.getElementById('chartOcupacion').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: horasLabels,
        datasets: [{
            label: 'Personas',
            data: horasData,
            backgroundColor: 'rgba(255, 0, 110, 0.6)',
            borderColor: '#ff006e',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: { color: '#fff' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255, 255, 255, 0.05)' }
            },
            x: {
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255, 255, 255, 0.05)' }
            }
        }
    }
});

// Gráfico de Ocupación por Mesa
var mesasLabels = <?php echo json_encode(array_map(function($m) { return 'Mesa ' . $m['numero']; }, $ocupacionMesas)); ?>;
var mesasData = <?php echo json_encode(array_map(function($m) { return (int)$m['personas_totales']; }, $ocupacionMesas)); ?>;

var ctx2 = document.getElementById('chartMesas').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: mesasLabels,
        datasets: [{
            data: mesasData,
            backgroundColor: [
                '#ff006e', '#fb5607', '#ffd60a', '#26d07c',
                '#5a189a', '#3a86ff', '#38b6ff', '#8338ec',
                '#ff006e', '#fb5607', '#ffd60a', '#26d07c'
            ],
            borderColor: '#16213e',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: { color: '#fff' }
            }
        }
    }
});
</script>

</body>
</html>
