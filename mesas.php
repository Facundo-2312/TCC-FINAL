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

// Cargar reservas para mostrar en las mesas
$servicioReserva = new App\Services\ReservaService();
$reservasActivas = $servicioReserva->listarActivas();
$reservasPorMesa = array();
foreach ($reservasActivas as $r) {
    $id = (int)$r['id_mesa'];
    if (!isset($reservasPorMesa[$id])) {
        $reservasPorMesa[$id] = array();
    }
    $reservasPorMesa[$id][] = $r;
}

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mesas en Tiempo Real</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🍽️ Mesas en Tiempo Real</h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
            <a class="btn logout" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">Salir</a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'occupied') { ?>
        <div class="alert error">
            ⚠️ No se pudo reservar: la mesa ya fue ocupada por otro usuario.
        </div>
    <?php } ?>

    <!-- Filtros -->
    <div class="filters">
        <div class="filter-group">
            <label for="filtroEstado">📊 Filtrar por estado</label>
            <select id="filtroEstado">
                <option value="">Todos</option>
                <option value="Libre">Libre</option>
                <option value="Ocupada">Ocupada</option>
                <option value="Limpieza">Limpieza</option>
                <option value="Reservada">Reservada</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="filtroZona">🏠 Filtrar por zona</label>
            <select id="filtroZona">
                <option value="">Todas</option>
                <option value="Salón A">Salón A</option>
                <option value="Salón B">Salón B</option>
            </select>
        </div>

        <div style="margin-left: auto;">
            <span class="last-update">Última actualización: <strong id="lastUpdate">--:--:--</strong></span>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card libres">
            <div class="label">Mesas Libres</div>
            <div class="value" id="statLibre"><?php echo $libres; ?></div>
        </div>
        <div class="stat-card ocupadas">
            <div class="label">Mesas Ocupadas</div>
            <div class="value" id="statOcupada"><?php echo $ocupadas; ?></div>
        </div>
        <div class="stat-card limpieza">
            <div class="label">En Limpieza</div>
            <div class="value" id="statLimpieza"><?php echo $limpieza; ?></div>
        </div>
        <div class="stat-card reservadas">
            <div class="label">Reservadas</div>
            <div class="value" id="statReservadas">0</div>
        </div>
    </div>

    <!-- Salón A -->
    <div class="salon">
        <div class="mesas-grid" id="mesasGrid">
            <?php foreach ($mesas as $mesa) { ?>
                <?php
                    $estado = (string) $mesa['estado'];
                    $estadoClass = strtolower($estado);
                    $idMesa = (int)$mesa['id_mesa'];
                    $tieneReserva = isset($reservasPorMesa[$idMesa]) && count($reservasPorMesa[$idMesa]) > 0;
                    $reserva = $tieneReserva ? $reservasPorMesa[$idMesa][0] : null;
                ?>
                <article class="mesa-card <?php echo h($estadoClass); ?>" data-id="<?php echo $idMesa; ?>" data-zona="<?php echo h($mesa['zona']); ?>">
                    <div class="mesa-header">
                        <h3>Mesa <?php echo (int) $mesa['numero']; ?></h3>
                        <span class="mesa-badge <?php echo h($estadoClass); ?>"><?php echo h($estado); ?></span>
                    </div>

                    <div class="mesa-info">
                        <div class="mesa-info-item">
                            <span class="label">Zona</span>
                            <span class="value"><?php echo h($mesa['zona']); ?></span>
                        </div>
                        <div class="mesa-info-item">
                            <span class="label">Capacidad</span>
                            <span class="value"><?php echo (int)($mesa['capacidad'] ?? 4); ?> personas</span>
                        </div>
                        <?php if ($tieneReserva) { ?>
                            <div class="mesa-info-item">
                                <span class="label">Cliente</span>
                                <span class="value"><?php echo h($reserva['nombre_cliente']); ?></span>
                            </div>
                            <div class="mesa-info-item">
                                <span class="label">Personas</span>
                                <span class="value"><?php echo (int)$reserva['cantidad_personas']; ?></span>
                            </div>
                            <div class="mesa-info-item">
                                <span class="label">Entrada</span>
                                <span class="value"><?php echo date('H:i', strtotime($reserva['hora_inicio'])); ?></span>
                            </div>
                            <div class="mesa-info-item">
                                <span class="label">Salida</span>
                                <span class="value"><?php echo date('H:i', strtotime($reserva['hora_fin'])); ?></span>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="mesa-actions">
                        <button class="btn-action reserve" onclick="abrirModalReserva(<?php echo $idMesa; ?>)">
                            📅 Hacer Reserva
                        </button>

                        <form method="post" style="display: flex; gap: 10px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="accion_mesa" value="1">
                            <input type="hidden" name="id_mesa" value="<?php echo $idMesa; ?>">
                            
                            <input type="hidden" name="estado" value="Ocupada">
                            <button type="submit" class="btn-action reserve" style="flex: 1;">✓ Ocupar</button>

                            <input type="hidden" name="estado" value="Limpieza">
                            <button type="submit" class="btn-action cleaning" style="flex: 1;">🧹 Limpiar</button>
                            
                            <input type="hidden" name="estado" value="Libre">
                            <button type="submit" class="btn-action free" style="flex: 1;">✔ Liberar</button>
                        </form>
                    </div>
                </article>
            <?php } ?>
        </div>
    </div>

    <!-- Historial -->
    <div class="history-section">
        <div class="history-header">
            <h2>📋 Historial de Cambios</h2>
            <span style="color: #888; font-size: 12px;">Últimos cambios de estado</span>
        </div>
        <div class="history-list" id="historialList">
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
    </div>
</div>

<!-- Modal de Reserva -->
<div class="modal" id="modalReserva">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📅 Nueva Reserva - Mesa <span id="mesaNumero">--</span></h2>
            <button class="close-btn" onclick="cerrarModalReserva()">×</button>
        </div>

        <form id="formReserva" onsubmit="guardarReserva(event)">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_mesa" id="inputIdMesa" value="0">

            <div class="form-group">
                <label for="nombreCliente">👤 Nombre del Cliente *</label>
                <input type="text" id="nombreCliente" name="nombre_cliente" required maxlength="100" placeholder="Ej: Juan García">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cantidadPersonas"># Cantidad de Personas *</label>
                    <input type="number" id="cantidadPersonas" name="cantidad_personas" required min="1" max="50" value="1" placeholder="Ej: 4">
                </div>
                <div class="form-group">
                    <label for="telefono">📱 Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="Ej: 123-456-7890">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="horaInicio">⏰ Hora Entrada *</label>
                    <input type="datetime-local" id="horaInicio" name="hora_inicio" required>
                </div>
                <div class="form-group">
                    <label for="horaFin">⏰ Hora Salida *</label>
                    <input type="datetime-local" id="horaFin" name="hora_fin" required>
                </div>
            </div>

            <div class="form-group">
                <label for="notas">📝 Notas Especiales</label>
                <textarea id="notas" name="notas" placeholder="Ej: Celebración de cumpleaños, dieta especial, etc."></textarea>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-submit">✓ Guardar Reserva</button>
                <button type="button" class="btn-cancel" onclick="cerrarModalReserva()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php echo 'var csrfToken = ' . json_encode(App\Support\Csrf::token()) . ';'; ?>

function abrirModalReserva(idMesa) {
    document.getElementById('inputIdMesa').value = idMesa;
    document.getElementById('mesaNumero').textContent = document.querySelector(`[data-id="${idMesa}"] h3`).textContent.replace('Mesa ', '');
    
    // Pre-llenar horarios sugeridos
    var ahora = new Date();
    ahora.setHours(ahora.getHours() + 1);
    document.getElementById('horaInicio').value = ahora.toISOString().slice(0, 16);
    
    var fin = new Date(ahora);
    fin.setHours(fin.getHours() + 2);
    document.getElementById('horaFin').value = fin.toISOString().slice(0, 16);
    
    document.getElementById('modalReserva').classList.add('show');
}

function cerrarModalReserva() {
    document.getElementById('modalReserva').classList.remove('show');
    document.getElementById('formReserva').reset();
}

function guardarReserva(e) {
    e.preventDefault();
    
    var formData = new FormData(document.getElementById('formReserva'));
    formData.append('_csrf', csrfToken);

    fetch('<?php echo htmlspecialchars(app_url('reservar_mesa_api.php'), ENT_QUOTES, 'UTF-8'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('✓ Reserva creada exitosamente');
            cerrarModalReserva();
            location.reload();
        } else {
            alert('✗ Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(e => {
        console.error(e);
        alert('✗ Error al guardar la reserva');
    });
}

window.onclick = function(e) {
    var modal = document.getElementById('modalReserva');
    if (e.target === modal) {
        cerrarModalReserva();
    }
}

// Sync en tiempo real
(function() {
    var filtroEstado = document.getElementById('filtroEstado');
    var filtroZona = document.getElementById('filtroZona');
    var lastUpdate = document.getElementById('lastUpdate');
    var historialList = document.getElementById('historialList');

    function applyFilters() {
        var estado = filtroEstado ? filtroEstado.value : '';
        var zona = filtroZona ? filtroZona.value : '';

        document.querySelectorAll('.mesa-card').forEach(function(card) {
            var cardZona = card.getAttribute('data-zona');
            var cardEstado = card.className.split(' ')[1] || '';
            
            var matchEstado = !estado || cardEstado === estado.toLowerCase();
            var matchZona = !zona || cardZona === zona;
            card.style.display = (matchEstado && matchZona) ? '' : 'none';
        });
    }

    function estadoClass(estado) {
        var e = (estado || '').toLowerCase();
        if (e === 'ocupada') return 'ocupada';
        if (e === 'limpieza') return 'limpieza';
        if (e === 'reservada') return 'reservada';
        return 'libre';
    }

    function syncMesas(data) {
        if (!data || !Array.isArray(data.mesas)) return;

        data.mesas.forEach(function(mesa) {
            var card = document.querySelector('.mesa-card[data-id="' + mesa.id_mesa + '"]');
            if (!card) return;

            card.className = 'mesa-card ' + estadoClass(mesa.estado);
            card.setAttribute('data-zona', mesa.zona);

            var badge = card.querySelector('.mesa-badge');
            if (badge) {
                badge.className = 'mesa-badge ' + estadoClass(mesa.estado);
                badge.textContent = mesa.estado;
            }
        });

        if (data.stats) {
            var libre = document.getElementById('statLibre');
            var ocupada = document.getElementById('statOcupada');
            var limpieza = document.getElementById('statLimpieza');
            if (libre) libre.textContent = data.stats.Libre || 0;
            if (ocupada) ocupada.textContent = data.stats.Ocupada || 0;
            if (limpieza) limpieza.textContent = data.stats.Limpieza || 0;
        }

        if (lastUpdate) {
            lastUpdate.textContent = new Date().toLocaleTimeString();
        }

        applyFilters();
    }

    function poll() {
        fetch('<?php echo htmlspecialchars(app_url('mesas_estado.php'), ENT_QUOTES, 'UTF-8'); ?>', { cache: 'no-store' })
            .then(r => r.json())
            .then(syncMesas)
            .catch(() => {});

        fetch('<?php echo htmlspecialchars(app_url('mesas_historial.php'), ENT_QUOTES, 'UTF-8'); ?>', { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (!historialList || !data || !Array.isArray(data.historial)) return;

                if (data.historial.length === 0) {
                    historialList.innerHTML = '<div class="history-empty">Aún no hay cambios registrados.</div>';
                    return;
                }

                historialList.innerHTML = data.historial.map(item => `
                    <div class="history-item">
                        <div class="history-main">
                            <strong>Mesa ${item.numero}</strong>
                            <span>${item.estado_anterior} → ${item.estado_nuevo}</span>
                        </div>
                        <div class="history-meta">
                            <span>${item.usuario}</span>
                            <span>${item.fecha}</span>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {});
    }

    filtroEstado?.addEventListener('change', applyFilters);
    filtroZona?.addEventListener('change', applyFilters);

    poll();
    setInterval(poll, 3000);
})();
</script>

</body>
</html>

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
