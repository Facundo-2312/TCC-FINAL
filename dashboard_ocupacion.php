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
        #contenido {
            display: block;
            height: auto;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
        }

        .filters {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #aaa;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: #fff;
            padding: 10px 12px;
            font-size: 14px;
        }

        .filter-group input:focus {
            border-color: #ff006e;
            outline: none;
            background: rgba(255, 0, 110, 0.1);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #ff006e;
            transform: translateY(-4px);
        }

        .stat-card .label {
            color: #aaa;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2.4em;
            font-weight: bold;
            background: linear-gradient(135deg, #ff006e, #fb5607);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .history-section {
            padding: 25px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .history-header h2 {
            font-size: 1.2em;
        }

        .history-section canvas {
            max-height: 320px;
        }

        table {
            width: 100%;
            color: #fff;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 0, 110, 0.15);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 0, 110, 0.4);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        tbody tr:hover {
            background: rgba(255, 0, 110, 0.08);
        }

        .btn-action {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-action.reserve {
            background: linear-gradient(135deg, #5a189a, #6d28d9);
            color: #fff;
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
