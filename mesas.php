<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2', '3']);

$conexion = app_db_connect();
if (!$conexion) {
    App\Support\Db::fail('No se pudo conectar con la base de datos.', 'mesas.php: ' . mysqli_connect_error());
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$mesaController = new App\Controllers\MesaController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mesa'])) {
    csrf_verify_or_die('mesas.php');

    $idMesa = (int) ($_POST['id_mesa'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    $usuarioAccion = (string) ($_SESSION['Usuario'] ?? 'sistema');

    $resultado = $mesaController->cambiarEstado($idMesa, $estado, $usuarioAccion);
    if (!$resultado['ok'] && $resultado['codigo'] === 'occupied') {
        app_redirect('mesas.php?msg=occupied');
    }

    app_redirect('mesas.php');
    exit();
}

$vistaMesas = $mesaController->listar();
$mesas = $vistaMesas['mesas'];
$libres = $vistaMesas['stats']['Libre'];
$ocupadas = $vistaMesas['stats']['Ocupada'];
$limpieza = $vistaMesas['stats']['Limpieza'];
$historial = $mesaController->historial();

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
<script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>

<div class="top">
    <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
    <a class="btn logout" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">Salir</a>
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
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion_mesa" value="1">
                <input type="hidden" name="id_mesa" value="<?php echo (int) $mesa['id_mesa']; ?>">
                <input type="hidden" name="estado" value="Ocupada">
                <button type="submit" class="btn-action reserve">Reservar</button>
            </form>

            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion_mesa" value="1">
                <input type="hidden" name="id_mesa" value="<?php echo (int) $mesa['id_mesa']; ?>">
                <input type="hidden" name="estado" value="Limpieza">
                <button type="submit" class="btn-action cleaning">Limpieza</button>
            </form>

            <form method="post">
                <?php echo csrf_field(); ?>
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
        fetch('<?php echo htmlspecialchars(app_url('mesas_estado.php'), ENT_QUOTES, 'UTF-8'); ?>', { cache: 'no-store' })
            .then(function (res) { return res.json(); })
            .then(syncMesas)
            .catch(function () {});

        fetch('<?php echo htmlspecialchars(app_url('mesas_historial.php'), ENT_QUOTES, 'UTF-8'); ?>', { cache: 'no-store' })
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
