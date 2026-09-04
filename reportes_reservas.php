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

// Obtener lista de mesas
$resultMesas = mysqli_query($conexion, 'SELECT id_mesa, numero FROM mesas ORDER BY numero ASC');
$mesas = mysqli_fetch_all($resultMesas, MYSQLI_ASSOC);

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de Reservas</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>📊 Reporte de Reservas</h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>">← Mesas</a>
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
        </div>
    </div>

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
