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

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard de Ocupación</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .date-selector {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.02);
            padding: 20px;
            border-radius: 10px;
        }

        .date-selector input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #ff0055;
            border-radius: 5px;
            color: #fff;
            padding: 8px 12px;
        }

        .date-selector button {
            background-color: #ff0055;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .date-selector button:hover {
            background-color: #ff1a66;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: #fff;
        }

        .stat-label {
            color: #aaa;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #ff0055;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            height: 400px;
        }

        .chart-title {
            color: #fff;
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            color: #fff;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: rgba(255, 0, 85, 0.2);
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ff0055;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        tr:hover {
            background: rgba(255, 0, 85, 0.1);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            overflow-x: auto;
        }

        #contenido {
            min-height: 100vh;
        }
    </style>
</head>

<body>

<header>
    <h1>📈 DASHBOARD DE OCUPACIÓN</h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('centro_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">
    <div class="dashboard-container">

        <!-- Selector de Fecha -->
        <div class="date-selector">
            <label for="fechaSelector">📅 Selecciona una fecha:</label>
            <input type="date" id="fechaSelector" value="<?php echo htmlspecialchars($fecha); ?>">
            <button onclick="cambiarFecha()">Ir</button>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Reservas</div>
                <div class="stat-value"><?php echo $stats['total_reservas'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Personas</div>
                <div class="stat-value"><?php echo $stats['personas_totales'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Confirmadas</div>
                <div class="stat-value"><?php echo $stats['confirmadas'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Canceladas</div>
                <div class="stat-value"><?php echo $stats['canceladas'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Gráfico: Ocupación por Hora -->
        <div class="chart-container">
            <div class="chart-title">Ocupación por Hora</div>
            <canvas id="graficoHoras"></canvas>
        </div>

        <!-- Gráfico: Ocupación por Mesa -->
        <div class="chart-container">
            <div class="chart-title">Ocupación por Mesa</div>
            <canvas id="graficoMesas"></canvas>
        </div>

        <!-- Tabla: Detalles de Mesas -->
        <div class="table-container">
            <h3 style="color: #fff; margin-top: 0;">🍽️ Detalle de Mesas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mesa</th>
                        <th>Reservas</th>
                        <th>Personas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ocupacionMesas as $m) { ?>
                        <tr>
                            <td>Mesa <?php echo (int)$m['numero']; ?></td>
                            <td><?php echo (int)($m['cantidad_reservas'] ?? 0); ?></td>
                            <td><?php echo (int)($m['personas_totales'] ?? 0); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Tabla: Top Clientes -->
        <div class="table-container">
            <h3 style="color: #fff; margin-top: 0;">👤 Top 10 Clientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Reservas</th>
                        <th>Personas</th>
                        <th>Última Reserva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topClientes as $c) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$c['cantidad']; ?></td>
                            <td><?php echo (int)$c['personas']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['ultima_fecha'])); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function cambiarFecha() {
    var fecha = document.getElementById('fechaSelector').value;
    if (fecha) {
        window.location.href = '<?php echo htmlspecialchars(app_url('dashboard_ocupacion.php'), ENT_QUOTES, 'UTF-8'); ?>?fecha=' + fecha;
    }
}

// Gráfico: Ocupación por Hora
var ctxHoras = document.getElementById('graficoHoras').getContext('2d');
var datosHoras = <?php echo json_encode([
    'horas' => array_keys($datosOcupacion),
    'cantidad' => array_column($datosOcupacion, 'cantidad'),
    'personas' => array_column($datosOcupacion, 'personas')
]); ?>;

new Chart(ctxHoras, {
    type: 'line',
    data: {
        labels: datosHoras.horas.map(h => h + ':00'),
        datasets: [
            {
                label: 'Reservas',
                data: datosHoras.cantidad,
                borderColor: '#ff0055',
                backgroundColor: 'rgba(255, 0, 85, 0.1)',
                tension: 0.4,
                borderWidth: 2,
                fill: true
            },
            {
                label: 'Personas',
                data: datosHoras.personas,
                borderColor: '#26d07c',
                backgroundColor: 'rgba(38, 208, 124, 0.1)',
                tension: 0.4,
                borderWidth: 2,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#fff' }
            }
        },
        scales: {
            y: {
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            },
            x: {
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            }
        }
    }
});

// Gráfico: Ocupación por Mesa
var ctxMesas = document.getElementById('graficoMesas').getContext('2d');
var datosMesas = <?php echo json_encode([
    'mesas' => array_map(function($m) { return 'Mesa ' . $m['numero']; }, $ocupacionMesas),
    'reservas' => array_column($ocupacionMesas, 'cantidad_reservas'),
    'personas' => array_column($ocupacionMesas, 'personas_totales')
]); ?>;

new Chart(ctxMesas, {
    type: 'bar',
    data: {
        labels: datosMesas.mesas,
        datasets: [
            {
                label: 'Reservas',
                data: datosMesas.reservas,
                backgroundColor: '#ff0055'
            },
            {
                label: 'Personas',
                data: datosMesas.personas,
                backgroundColor: '#5a189a'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#fff' }
            }
        },
        scales: {
            y: {
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            },
            x: {
                ticks: { color: '#aaa' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            }
        }
    }
});
</script>
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
