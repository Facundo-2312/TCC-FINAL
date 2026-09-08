<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$conexion = app_db_connect();
if (!$conexion) {
    App\Support\Db::fail('No se pudo conectar con la base de datos.');
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$servicioReserva = new App\Services\ReservaService();

// Obtener filtros
$filtroEstado = trim($_GET['estado'] ?? '');
$filtroFechaInicio = trim($_GET['fecha_inicio'] ?? '');
$filtroFechaFin = trim($_GET['fecha_fin'] ?? '');
$filtroMesa = (int)($_GET['id_mesa'] ?? 0);

// Construir consulta
$query = 'SELECT r.*, m.numero as mesa_numero FROM reservas r
          INNER JOIN mesas m ON m.id_mesa = r.id_mesa WHERE 1=1';

$params = array();
$tipos = '';

if (!empty($filtroEstado)) {
    $query .= ' AND r.estado = ?';
    $params[] = $filtroEstado;
    $tipos .= 's';
}

if (!empty($filtroFechaInicio)) {
    $query .= ' AND DATE(r.hora_inicio) >= ?';
    $params[] = $filtroFechaInicio;
    $tipos .= 's';
}

if (!empty($filtroFechaFin)) {
    $query .= ' AND DATE(r.hora_inicio) <= ?';
    $params[] = $filtroFechaFin;
    $tipos .= 's';
}

if ($filtroMesa > 0) {
    $query .= ' AND r.id_mesa = ?';
    $params[] = $filtroMesa;
    $tipos .= 'i';
}

$query .= ' ORDER BY r.hora_inicio DESC';

$stmt = mysqli_prepare($conexion, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reservas = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Estadísticas
$estadisticas = array(
    'total' => 0,
    'confirmadas' => 0,
    'canceladas' => 0,
    'completadas' => 0,
    'personas' => 0
);

foreach ($reservas as $r) {
    $estadisticas['total']++;
    $estadisticas[$r['estado'] === 'Confirmada' ? 'confirmadas' : (strtolower($r['estado']))] = ($estadisticas[$r['estado'] === 'Confirmada' ? 'confirmadas' : (strtolower($r['estado']))] ?? 0) + 1;
    $estadisticas['personas'] += (int)$r['cantidad_personas'];
}

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

// Obtener lista de mesas
$resultMesas = mysqli_query($conexion, 'SELECT id_mesa, numero FROM mesas ORDER BY numero ASC');
$mesas = mysqli_fetch_all($resultMesas, MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Reservas</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

        .filter-group input,
        .filter-group select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: #fff;
            padding: 10px 12px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
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

        .history-empty {
            text-align: center;
            padding: 30px;
            color: #888;
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

        .mesa-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mesa-badge.confirmada {
            background: #26d07c;
            color: #000;
        }

        .mesa-badge.cancelada {
            background: #ff6b6b;
            color: #fff;
        }

        .mesa-badge.completada {
            background: #5a189a;
            color: #fff;
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

        .btn-action.free {
            background: linear-gradient(135deg, #26d07c, #1fad5b);
            color: #fff;
        }
    </style>
</head>

<body>

<header>
    <h1>📊 REPORTES DE RESERVAS</h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('centro_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">
    <!-- Filtros -->
    <div class="filters">
        <form method="get" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
            <div class="filter-group">
                <label for="fecha_inicio">📅 Desde</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo h($filtroFechaInicio); ?>">
            </div>

            <div class="filter-group">
                <label for="fecha_fin">📅 Hasta</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo h($filtroFechaFin); ?>">
            </div>

            <div class="filter-group">
                <label for="estado">📌 Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="Confirmada" <?php echo $filtroEstado === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                    <option value="Cancelada" <?php echo $filtroEstado === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    <option value="Completada" <?php echo $filtroEstado === 'Completada' ? 'selected' : ''; ?>>Completada</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="id_mesa">🍽️ Mesa</label>
                <select id="id_mesa" name="id_mesa">
                    <option value="0">Todas</option>
                    <?php foreach ($mesas as $m) { ?>
                        <option value="<?php echo (int)$m['id_mesa']; ?>" <?php echo $filtroMesa === (int)$m['id_mesa'] ? 'selected' : ''; ?>>
                            Mesa <?php echo (int)$m['numero']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <button type="submit" class="btn-action reserve" style="padding: 10px 20px;">🔍 Filtrar</button>
            <a href="reportes_reservas.php" class="btn-action free" style="padding: 10px 20px; text-decoration: none; text-align: center;">✕ Limpiar</a>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card libres">
            <div class="label">Total de Reservas</div>
            <div class="value"><?php echo $estadisticas['total']; ?></div>
        </div>
        <div class="stat-card ocupadas">
            <div class="label">Confirmadas</div>
            <div class="value"><?php echo $estadisticas['confirmadas']; ?></div>
        </div>
        <div class="stat-card limpieza">
            <div class="label">Completadas</div>
            <div class="value"><?php echo $estadisticas['completadas']; ?></div>
        </div>
        <div class="stat-card reservadas">
            <div class="label">Total de Personas</div>
            <div class="value"><?php echo $estadisticas['personas']; ?></div>
        </div>
    </div>

    <!-- Tabla de Reservas -->
    <div class="history-section">
        <div class="history-header">
            <h2>📋 Lista de Reservas (<?php echo count($reservas); ?> resultados)</h2>
        </div>

        <?php if (empty($reservas)) { ?>
            <div class="history-empty">No hay reservas que coincidan con los filtros seleccionados.</div>
        <?php } else { ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.05); border-bottom: 2px solid rgba(255, 255, 255, 0.1);">
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Cliente</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Mesa</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Personas</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Entrada</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Salida</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Teléfono</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600;">Estado</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $r) { ?>
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 12px;"><strong><?php echo h($r['nombre_cliente']); ?></strong></td>
                                <td style="padding: 12px;">Mesa <?php echo (int)$r['mesa_numero']; ?></td>
                                <td style="padding: 12px;"><?php echo (int)$r['cantidad_personas']; ?> 👥</td>
                                <td style="padding: 12px; font-size: 13px; color: #aaa;">
                                    <?php echo date('d/m H:i', strtotime($r['hora_inicio'])); ?>
                                </td>
                                <td style="padding: 12px; font-size: 13px; color: #aaa;">
                                    <?php echo date('d/m H:i', strtotime($r['hora_fin'])); ?>
                                </td>
                                <td style="padding: 12px; font-size: 13px; color: #aaa;">
                                    <?php echo h($r['telefono'] ?? '-'); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span class="mesa-badge <?php echo strtolower($r['estado']); ?>">
                                        <?php echo h($r['estado']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <a href="editar_reserva.php?id=<?php echo (int)$r['id_reserva']; ?>" 
                                       class="btn-action reserve" style="padding: 6px 12px; font-size: 12px;">
                                        ✏️ Editar
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <!-- Exportar -->
    <div style="margin-top: 20px; text-align: center;">
        <a href="exportar_reservas.php?<?php echo http_build_query(array(
            'estado' => $filtroEstado,
            'fecha_inicio' => $filtroFechaInicio,
            'fecha_fin' => $filtroFechaFin,
            'id_mesa' => $filtroMesa
        )); ?>" class="btn-action reserve" style="display: inline-block; padding: 12px 24px;">
            📥 Exportar a CSV
        </a>
    </div>
</div>

</body>
</html>
