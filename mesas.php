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

function zonaMesa($numero)
{
    return ((int) $numero <= 3) ? 'Salón A' : 'Salón B';
}

function asegurarTablaHistorialMesas($conexion)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS mesas_historial (
            id_historial INT AUTO_INCREMENT PRIMARY KEY,
            id_mesa INT NOT NULL,
            estado_anterior VARCHAR(30) NOT NULL,
            estado_nuevo VARCHAR(30) NOT NULL,
            usuario VARCHAR(100) NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_mesa_fecha (id_mesa, fecha),
            INDEX idx_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    @mysqli_query($conexion, $sql);
}

function registrarHistorialMesa($conexion, $idMesa, $estadoAnterior, $estadoNuevo, $usuario)
{
    if ($estadoAnterior === $estadoNuevo) {
        return;
    }

    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO mesas_historial (id_mesa, estado_anterior, estado_nuevo, usuario) VALUES (?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'isss', $idMesa, $estadoAnterior, $estadoNuevo, $usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function asegurarEstadoLimpieza($conexion)
{
    $sql = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mesas' AND COLUMN_NAME = 'estado' LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $columnType = (string) ($row['COLUMN_TYPE'] ?? '');

    if ($columnType !== '' && stripos($columnType, 'Limpieza') === false) {
        @mysqli_query($conexion, "ALTER TABLE mesas MODIFY estado ENUM('Libre','Ocupada','Limpieza') DEFAULT 'Libre'");
    }
}

asegurarEstadoLimpieza($conexion);
asegurarTablaHistorialMesas($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mesa'])) {
    $idMesa = (int) ($_POST['id_mesa'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    $permitidos = ['Libre', 'Ocupada', 'Limpieza'];
    $usuarioAccion = (string) ($_SESSION['Usuario'] ?? 'sistema');

    if ($idMesa > 0 && in_array($estado, $permitidos, true)) {
        $estadoActual = '';
        $stmtEstado = mysqli_prepare($conexion, 'SELECT estado FROM mesas WHERE id_mesa = ? LIMIT 1');
        mysqli_stmt_bind_param($stmtEstado, 'i', $idMesa);
        mysqli_stmt_execute($stmtEstado);
        $resEstado = mysqli_stmt_get_result($stmtEstado);
        $rowEstado = $resEstado ? mysqli_fetch_assoc($resEstado) : null;
        mysqli_stmt_close($stmtEstado);
        $estadoActual = (string) ($rowEstado['estado'] ?? '');

        if ($estado === 'Ocupada') {
            // Prevent double booking: only reserve if currently Libre.
            $estadoLibre = 'Libre';
            $stmt = mysqli_prepare($conexion, 'UPDATE mesas SET estado = ? WHERE id_mesa = ? AND estado = ?');
            mysqli_stmt_bind_param($stmt, 'sis', $estado, $idMesa, $estadoLibre);
            mysqli_stmt_execute($stmt);
            $filas = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($filas === 0) {
                header('Location: /proj/mesas.php?msg=occupied');
                exit();
            }

            registrarHistorialMesa($conexion, $idMesa, $estadoActual, $estado, $usuarioAccion);
        } else {
            $stmt = mysqli_prepare($conexion, 'UPDATE mesas SET estado = ? WHERE id_mesa = ?');
            mysqli_stmt_bind_param($stmt, 'si', $estado, $idMesa);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            registrarHistorialMesa($conexion, $idMesa, $estadoActual, $estado, $usuarioAccion);
        }
    }

    header('Location: /proj/mesas.php');
    exit();
}

$mesas = [];
$res = mysqli_query($conexion, 'SELECT id_mesa, numero, estado FROM mesas ORDER BY numero ASC');
while ($row = mysqli_fetch_assoc($res)) {
    $row['zona'] = zonaMesa((int) $row['numero']);
    $mesas[] = $row;
}

$libres = 0;
$ocupadas = 0;
$limpieza = 0;

foreach ($mesas as $mesa) {
    if ($mesa['estado'] === 'Libre') $libres++;
    if ($mesa['estado'] === 'Ocupada') $ocupadas++;
    if ($mesa['estado'] === 'Limpieza') $limpieza++;
}

$historial = [];
$resHistorial = mysqli_query(
    $conexion,
    'SELECT h.id_historial, h.id_mesa, m.numero, h.estado_anterior, h.estado_nuevo, h.usuario, h.fecha
     FROM mesas_historial h
     INNER JOIN mesas m ON m.id_mesa = h.id_mesa
     ORDER BY h.fecha DESC
     LIMIT 12'
);
while ($resHistorial && $row = mysqli_fetch_assoc($resHistorial)) {
    $historial[] = $row;
}

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mesas en Tiempo Real</title>
<link rel="stylesheet" type="text/css" href="estilos/mesas.css?v=<?php echo $cssVersion; ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="/proj/no-popups.js"></script>
</head>
<body>

<div class="top">
    <a class="btn back" href="/proj/Principal.php">← Volver</a>
    <a class="btn logout" href="/proj/Salir.php">Salir</a>
</div>

<h1>Mesas en Tiempo Real</h1>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'occupied') { ?>
    <div class="alert warning">No se pudo reservar: la mesa ya fue ocupada por otro usuario.</div>
<?php } ?>

<div class="filters">
    <div class="filter-item">
        <label for="filtroEstado">Filtrar por estado</label>
        <select id="filtroEstado">
            <option value="">Todos</option>
            <option value="Libre">Libre</option>
            <option value="Ocupada">Ocupada</option>
            <option value="Limpieza">Limpieza</option>
        </select>
    </div>

    <div class="filter-item">
        <label for="filtroZona">Filtrar por zona</label>
        <select id="filtroZona">
            <option value="">Todas</option>
            <option value="Salón A">Salón A</option>
            <option value="Salón B">Salón B</option>
        </select>
    </div>
</div>

<div class="last-update">Última actualización: <span id="lastUpdate">--:--:--</span></div>

<div class="summary">
    <div class="sum-card"><div class="label">Libres</div><div id="statLibre" class="value"><?php echo $libres; ?></div></div>
    <div class="sum-card"><div class="label">Ocupadas</div><div id="statOcupada" class="value"><?php echo $ocupadas; ?></div></div>
    <div class="sum-card"><div class="label">Limpieza</div><div id="statLimpieza" class="value"><?php echo $limpieza; ?></div></div>
</div>

<section class="history-panel">
    <div class="history-head">
        <h2>Historial reciente de mesas</h2>
        <span class="muted">Últimos cambios</span>
    </div>

    <div id="historialList" class="history-list">
        <?php if (!empty($historial)) { ?>
            <?php foreach ($historial as $item) { ?>
                <div class="history-item">
                    <div class="history-main">
                        <strong>Mesa <?php echo (int) $item['numero']; ?></strong>
                        <span><?php echo h($item['estado_anterior']); ?> → <?php echo h($item['estado_nuevo']); ?></span>
                    </div>
                    <div class="history-meta">
                        <span><?php echo h($item['usuario']); ?></span>
                        <span><?php echo h($item['fecha']); ?></span>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="history-empty">Aún no hay cambios registrados.</div>
        <?php } ?>
    </div>
</section>

<div class="grid" id="mesasGrid">
<?php foreach ($mesas as $mesa) { ?>
    <?php
        $estado = (string) $mesa['estado'];
        $estadoClass = strtolower($estado);
    ?>
    <article class="mesa-card <?php echo h($estadoClass); ?>" data-id="<?php echo (int) $mesa['id_mesa']; ?>">
        <div class="mesa-head">
            <h3>Mesa <?php echo (int) $mesa['numero']; ?></h3>
            <span class="badge estado-text"><?php echo h($estado); ?></span>
        </div>

        <div class="zone"><?php echo h($mesa['zona']); ?></div>

        <div class="actions">
            <form method="post">
                <input type="hidden" name="accion_mesa" value="1">
                <input type="hidden" name="id_mesa" value="<?php echo (int) $mesa['id_mesa']; ?>">
                <input type="hidden" name="estado" value="Ocupada">
                <button type="submit" class="btn-action reserve">Reservar</button>
            </form>

            <form method="post">
                <input type="hidden" name="accion_mesa" value="1">
                <input type="hidden" name="id_mesa" value="<?php echo (int) $mesa['id_mesa']; ?>">
                <input type="hidden" name="estado" value="Limpieza">
                <button type="submit" class="btn-action cleaning">Limpieza</button>
            </form>

            <form method="post">
                <input type="hidden" name="accion_mesa" value="1">
                <input type="hidden" name="id_mesa" value="<?php echo (int) $mesa['id_mesa']; ?>">
                <input type="hidden" name="estado" value="Libre">
                <button type="submit" class="btn-action free">Liberar</button>
            </form>
        </div>
    </article>
<?php } ?>
</div>

<script>
(function () {
    var statLibre = document.getElementById('statLibre');
    var statOcupada = document.getElementById('statOcupada');
    var statLimpieza = document.getElementById('statLimpieza');
    var filtroEstado = document.getElementById('filtroEstado');
    var filtroZona = document.getElementById('filtroZona');
    var lastUpdate = document.getElementById('lastUpdate');
    var historialList = document.getElementById('historialList');

    function applyFilters() {
        var estado = filtroEstado ? filtroEstado.value : '';
        var zona = filtroZona ? filtroZona.value : '';

        document.querySelectorAll('.mesa-card').forEach(function (card) {
            var estadoText = (card.querySelector('.estado-text') || { textContent: '' }).textContent.trim();
            var zonaText = (card.querySelector('.zone') || { textContent: '' }).textContent.trim();

            var matchEstado = !estado || estadoText === estado;
            var matchZona = !zona || zonaText === zona;
            card.style.display = (matchEstado && matchZona) ? '' : 'none';
        });
    }

    function estadoClass(estado) {
        var e = (estado || '').toLowerCase();
        if (e === 'ocupada') return 'ocupada';
        if (e === 'limpieza') return 'limpieza';
        return 'libre';
    }

    function syncMesas(data) {
        if (!data || !Array.isArray(data.mesas)) return;

        data.mesas.forEach(function (mesa) {
            var card = document.querySelector('.mesa-card[data-id="' + mesa.id_mesa + '"]');
            if (!card) return;

            card.classList.remove('libre', 'ocupada', 'limpieza');
            card.classList.add(estadoClass(mesa.estado));

            var badge = card.querySelector('.estado-text');
            if (badge) {
                badge.textContent = mesa.estado;
            }

            var zone = card.querySelector('.zone');
            if (zone && mesa.zona) {
                zone.textContent = mesa.zona;
            }
        });

        if (data.stats) {
            if (statLibre) statLibre.textContent = data.stats.Libre || 0;
            if (statOcupada) statOcupada.textContent = data.stats.Ocupada || 0;
            if (statLimpieza) statLimpieza.textContent = data.stats.Limpieza || 0;
        }

        if (lastUpdate) {
            if (data.updatedAt) {
                lastUpdate.textContent = data.updatedAt;
            } else {
                lastUpdate.textContent = new Date().toLocaleTimeString();
            }
        }

        applyFilters();
    }

    function poll() {
        fetch('/proj/mesas_estado.php', { cache: 'no-store' })
            .then(function (res) { return res.json(); })
            .then(syncMesas)
            .catch(function () {});

        fetch('/proj/mesas_historial.php', { cache: 'no-store' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!historialList || !data || !Array.isArray(data.historial)) return;

                if (data.historial.length === 0) {
                    historialList.innerHTML = '<div class="history-empty">Aún no hay cambios registrados.</div>';
                    return;
                }

                historialList.innerHTML = data.historial.map(function (item) {
                    var numero = item.numero || item.id_mesa || '-';
                    var anterior = item.estado_anterior || '-';
                    var nuevo = item.estado_nuevo || '-';
                    var usuario = item.usuario || 'sistema';
                    var fecha = item.fecha || '';

                    return '<div class="history-item">'
                        + '<div class="history-main"><strong>Mesa ' + numero + '</strong><span>' + anterior + ' → ' + nuevo + '</span></div>'
                        + '<div class="history-meta"><span>' + usuario + '</span><span>' + fecha + '</span></div>'
                        + '</div>';
                }).join('');
            })
            .catch(function () {});
    }

    if (filtroEstado) {
        filtroEstado.addEventListener('change', applyFilters);
    }

    if (filtroZona) {
        filtroZona.addEventListener('change', applyFilters);
    }

    applyFilters();
    if (lastUpdate) {
        lastUpdate.textContent = new Date().toLocaleTimeString();
    }

    setInterval(poll, 7000);
})();
</script>

</body>
</html>
