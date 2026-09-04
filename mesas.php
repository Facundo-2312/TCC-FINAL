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
$servicioReserva = new App\Services\ReservaService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_mesa'])) {
    csrf_verify_or_die('mesas.php');

    $idMesa = (int) ($_POST['id_mesa'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    $usuarioAccion = (string) ($_SESSION['Usuario'] ?? 'sistema');

    $resultado = $mesaController->cambiarEstado($idMesa, $estado, $usuarioAccion);
    if (!$resultado['ok'] && $resultado['codigo'] === 'occupied') {
        app_redirect('mesas.php?msg=occupied');
    }

    if ($resultado['ok'] && $estado === 'Libre') {
        // El cliente se retiró: cerrar cualquier reserva ligada a esta mesa
        $servicioReserva->completarPorMesa($idMesa);
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
$reservasActivas = $servicioReserva->listarActivas();
$reservasPorMesa = array();
foreach ($reservasActivas as $r) {
    $id = (int)$r['id_mesa'];
    if (!isset($reservasPorMesa[$id])) {
        $reservasPorMesa[$id] = array();
    }
    $reservasPorMesa[$id][] = $r;
}

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mesas en Tiempo Real</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <style>
        .modal-content *,
        .modal-content *::before,
        .modal-content *::after {
            box-sizing: border-box;
        }
        .mesas-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .mesa-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            color: #fff;
            transition: all 0.3s ease;
            min-height: 420px;
            display: flex;
            flex-direction: column;
        }

        .mesa-card:hover {
            background: rgba(255, 0, 85, 0.1);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 0, 85, 0.2);
        }

        .mesa-card.libre {
            border-color: #26d07c;
        }

        .mesa-card.libre:hover {
            background: rgba(38, 208, 124, 0.1);
        }

        .mesa-card.ocupada {
            border-color: #ff6b6b;
        }

        .mesa-card.ocupada:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        .mesa-card.limpieza {
            border-color: #ffd60a;
        }

        .mesa-card.limpieza:hover {
            background: rgba(255, 214, 10, 0.1);
        }

        .mesa-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .mesa-head h3 {
            margin: 0;
            font-size: 1.5em;
            font-weight: bold;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            background: #ff0055;
            color: #fff;
            text-transform: uppercase;
        }

        .badge.libre {
            background: #26d07c;
        }

        .badge.ocupada {
            background: #ff6b6b;
        }

        .badge.limpieza {
            background: #ffd60a;
            color: #000;
        }

        .mesa-info {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
            font-size: 13px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #ff0055;
        }

        .info-label {
            color: #aaa;
            font-size: 11px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }

        .info-value {
            color: #fff;
            font-weight: bold;
        }

        .mesa-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: auto;
        }

        .btn-mesa {
            padding: 10px;
            border: 1px solid;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            color: #fff;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            text-transform: uppercase;
            flex: 1;
        }

        .btn-mesa:hover {
            transform: scale(1.05);
        }

        .btn-reservar {
            background-color: #5a189a;
            border-color: #5a189a;
        }

        .btn-ocupar {
            background-color: #ff0055;
            border-color: #ff0055;
        }

        .btn-limpiar {
            background-color: #ffd60a;
            border-color: #ffd60a;
            color: #000;
        }

        .btn-liberar {
            background-color: #26d07c;
            border-color: #26d07c;
        }

        .btn-row {
            display: flex;
            gap: 8px;
        }

        .btn-row button,
        .btn-row form {
            flex: 1;
            display: flex;
        }

        .btn-row button {
            margin: 0;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            max-width: 1400px;
            margin: 30px auto;
        }

        .stat-box {
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
            font-size: 2.5em;
            font-weight: bold;
            color: #ff0055;
        }

        .filters-container {
            display: flex;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            flex-wrap: wrap;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-item label {
            color: #aaa;
            font-weight: bold;
        }

        .filter-item select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #ff0055;
            border-radius: 5px;
            color: #fff;
            padding: 8px 12px;
            cursor: pointer;
        }

        .history-container {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            max-width: 1400px;
            margin: 30px auto;
            color: #fff;
        }

        .history-container h3 {
            margin-top: 0;
            font-size: 1.2em;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 13px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .alert {
            padding: 15px 20px;
            margin: 20px auto;
            max-width: 1400px;
            border-radius: 10px;
            border-left: 4px solid;
        }

        .alert.warning {
            background: rgba(255, 214, 10, 0.1);
            border-color: #ffd60a;
            color: #ffd60a;
        }

        #contenido {
            height: auto;
            min-height: 100vh;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #1a1a1a;
            border: 2px solid #ff0055;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            color: #fff;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.3em;
        }

        .close-btn {
            background: none;
            border: none;
            color: #ff0055;
            font-size: 28px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #ff0055;
            border-radius: 5px;
            color: #fff;
            font-family: inherit;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #666;
        }

        .form-group select option {
            color: #000;
            background: #fff;
        }

        .form-group select option:disabled {
            color: #999;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            flex: 1;
            padding: 10px;
            border: 1px solid;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 12px;
        }

        .btn-submit {
            background-color: #ff0055;
            color: white;
            border-color: #ff0055;
        }

        .btn-cancel {
            background-color: rgba(255, 255, 255, 0.1);
            color: #aaa;
            border-color: #666;
        }

        @media (max-width: 900px) {
            .mesas-container {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                padding: 12px;
                gap: 14px;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                padding: 12px;
                gap: 10px;
            }

            .filters-container {
                padding: 12px;
                gap: 12px;
            }

            .history-container {
                margin: 20px 12px;
                padding: 15px;
            }
        }

        @media (max-width: 600px) {
            header h1 {
                font-size: 1.1em;
            }

            .mesas-container {
                grid-template-columns: 1fr;
                padding: 10px;
                gap: 12px;
            }

            .mesa-card {
                min-height: auto;
                padding: 15px;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .stat-box {
                padding: 12px;
            }

            .stat-value {
                font-size: 1.8em;
            }

            .filters-container {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .filter-item {
                justify-content: space-between;
            }

            .mesa-info {
                grid-template-columns: 1fr 1fr;
                font-size: 12px;
            }

            .modal-content {
                padding: 20px;
                width: 95%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>🍽️ MESAS EN TIEMPO REAL</h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'occupied') { ?>
        <div class="alert warning">
            ⚠️ No se pudo reservar: la mesa ya fue ocupada por otro usuario.
        </div>
    <?php } ?>

    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-label">Mesas Libres</div>
            <div class="stat-value" id="statLibre"><?php echo $libres; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Mesas Ocupadas</div>
            <div class="stat-value" id="statOcupada"><?php echo $ocupadas; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">En Limpieza</div>
            <div class="stat-value" id="statLimpieza"><?php echo $limpieza; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Última Actualización</div>
            <div class="stat-value" id="lastUpdate" style="font-size: 1em;">--:--:--</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-container">
        <div class="filter-item">
            <label for="filtroEstado">📊 Filtrar estado:</label>
            <select id="filtroEstado">
                <option value="">Todos</option>
                <option value="Libre">Libre</option>
                <option value="Ocupada">Ocupada</option>
                <option value="Limpieza">Limpieza</option>
            </select>
        </div>

        <div class="filter-item">
            <label for="filtroZona">🏠 Filtrar zona:</label>
            <select id="filtroZona">
                <option value="">Todas</option>
                <option value="Salón A">Salón A</option>
                <option value="Salón B">Salón B</option>
            </select>
        </div>
    </div>

    <!-- Mesas Grid -->
    <div class="mesas-container" id="mesasGrid">
        <?php foreach ($mesas as $mesa) { ?>
            <?php
                $estado = (string) $mesa['estado'];
                $estadoClass = strtolower($estado);
                $idMesa = (int)$mesa['id_mesa'];
                $tieneReserva = isset($reservasPorMesa[$idMesa]) && count($reservasPorMesa[$idMesa]) > 0;
                $reserva = $tieneReserva ? $reservasPorMesa[$idMesa][0] : null;
            ?>
            <div class="mesa-card <?php echo h($estadoClass); ?>" data-id="<?php echo $idMesa; ?>" data-zona="<?php echo h($mesa['zona']); ?>">
                <div class="mesa-head">
                    <h3>Mesa <?php echo (int) $mesa['numero']; ?></h3>
                    <span class="badge <?php echo h($estadoClass); ?>"><?php echo h($estado); ?></span>
                </div>

                <div class="mesa-info">
                    <div class="info-item">
                        <span class="info-label">Zona</span>
                        <span class="info-value"><?php echo h($mesa['zona']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Capacidad</span>
                        <span class="info-value"><?php echo (int)($mesa['capacidad'] ?? 4); ?> 👥</span>
                    </div>
                    
                    <?php if ($tieneReserva) { ?>
                        <div class="info-item">
                            <span class="info-label">Cliente</span>
                            <span class="info-value"><?php echo h($reserva['nombre_cliente']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Personas</span>
                            <span class="info-value"><?php echo (int)$reserva['cantidad_personas']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Entrada</span>
                            <span class="info-value"><?php echo date('H:i', strtotime($reserva['hora_inicio'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Salida</span>
                            <span class="info-value"><?php echo date('H:i', strtotime($reserva['hora_fin'])); ?></span>
                        </div>
                    <?php } ?>
                </div>

                <div class="mesa-actions">
                    <?php if ($estado === 'Libre') { ?>
                        <button class="btn-mesa btn-reservar" onclick="abrirModalReserva(<?php echo $idMesa; ?>)">
                            📅 HACER RESERVA
                        </button>
                    <?php } else { ?>
                        <button class="btn-mesa btn-reservar" disabled title="La mesa debe estar libre para reservar" style="opacity: 0.5; cursor: not-allowed;">
                            📅 NO DISPONIBLE PARA RESERVA
                        </button>
                    <?php } ?>

                    <div class="btn-row">
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="accion_mesa" value="1">
                            <input type="hidden" name="id_mesa" value="<?php echo $idMesa; ?>">
                            <input type="hidden" name="estado" value="Ocupada">
                            <button type="submit" class="btn-mesa btn-ocupar">✓ OCUPAR</button>
                        </form>
                    </div>

                    <div class="btn-row">
                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="accion_mesa" value="1">
                            <input type="hidden" name="id_mesa" value="<?php echo $idMesa; ?>">
                            <input type="hidden" name="estado" value="Limpieza">
                            <button type="submit" class="btn-mesa btn-limpiar">🧹 LIMPIAR</button>
                        </form>

                        <form method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="accion_mesa" value="1">
                            <input type="hidden" name="id_mesa" value="<?php echo $idMesa; ?>">
                            <input type="hidden" name="estado" value="Libre">
                            <button type="submit" class="btn-mesa btn-liberar">✔ LIBERAR</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Historial -->
    <div class="history-container">
        <h3>📋 HISTORIAL DE CAMBIOS</h3>
        <div id="historialList">
            <?php if (!empty($historial)) { ?>
                <?php foreach ($historial as $item) { ?>
                    <div class="history-item">
                        <div>
                            <strong>Mesa <?php echo (int) $item['numero']; ?>:</strong>
                            <?php echo h($item['estado_anterior']); ?> → <?php echo h($item['estado_nuevo']); ?>
                        </div>
                        <div style="text-align: right; color: #aaa;">
                            <div><?php echo h($item['usuario']); ?></div>
                            <div><?php echo h($item['fecha']); ?></div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div style="text-align: center; color: #aaa; padding: 20px;">
                    Aún no hay cambios registrados.
                </div>
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
                    <label for="cantidadPersonas"># Cantidad *</label>
                    <input type="number" id="cantidadPersonas" name="cantidad_personas" required min="1" max="50" value="1">
                </div>
                <div class="form-group">
                    <label for="telefono">📱 Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="123-456-7890">
                </div>
            </div>

            <div class="form-group">
                <label for="horarioSlot">⏰ Horario de la Reserva *</label>
                <select id="horarioSlot" name="horario_slot" required>
                    <option value="">Cargando horarios...</option>
                </select>
                <input type="hidden" id="horaInicio" name="hora_inicio">
                <input type="hidden" id="horaFin" name="hora_fin">
                <small id="horarioAyuda" style="display: block; margin-top: 6px; color: #aaa;"></small>
            </div>

            <div class="form-group">
                <label for="notas">📝 Notas</label>
                <textarea id="notas" name="notas" placeholder="Observaciones..." style="min-height: 80px;"></textarea>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-submit" id="btnGuardarReserva">✓ GUARDAR</button>
                <button type="button" class="btn-cancel" onclick="cerrarModalReserva()">CANCELAR</button>
            </div>
        </form>
    </div>
</div>

<script>
<?php echo 'var csrfToken = ' . json_encode(App\Support\Csrf::token()) . ';'; ?>

function cargarHorarios(idMesa) {
    var select = document.getElementById('horarioSlot');
    var ayuda = document.getElementById('horarioAyuda');
    var btnGuardar = document.getElementById('btnGuardarReserva');

    select.innerHTML = '<option value="">Cargando horarios...</option>';
    select.disabled = true;
    btnGuardar.disabled = true;
    ayuda.textContent = '';

    fetch('<?php echo htmlspecialchars(app_url('horarios_disponibles.php'), ENT_QUOTES, 'UTF-8'); ?>?id_mesa=' + idMesa, { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            select.innerHTML = '';

            if (!data.ok) {
                select.innerHTML = '<option value="">No se pudieron cargar los horarios</option>';
                return;
            }

            if (!data.mesa_libre) {
                select.innerHTML = '<option value="">Mesa no disponible</option>';
                ayuda.textContent = 'Esta mesa ya está ocupada o reservada.';
                return;
            }

            var hayDisponibles = false;
            select.innerHTML = '<option value="">Seleccioná un horario</option>';

            data.slots.forEach(function (slot) {
                var option = document.createElement('option');
                option.value = slot.hora_inicio + '|' + slot.hora_fin;
                option.textContent = slot.etiqueta + (slot.disponible ? '' : ' (no disponible)');
                option.disabled = !slot.disponible;
                option.dataset.inicio = slot.hora_inicio;
                option.dataset.fin = slot.hora_fin;
                if (slot.disponible) hayDisponibles = true;
                select.appendChild(option);
            });

            if (!hayDisponibles) {
                ayuda.textContent = 'No quedan turnos disponibles por hoy para esta mesa.';
            } else {
                ayuda.textContent = 'La mesa quedará marcada como Ocupada hasta que se libere.';
                select.disabled = false;
                btnGuardar.disabled = false;
            }
        })
        .catch(function () {
            select.innerHTML = '<option value="">Error al cargar horarios</option>';
        });
}

document.getElementById('horarioSlot').addEventListener('change', function () {
    var opcion = this.options[this.selectedIndex];
    document.getElementById('horaInicio').value = opcion.dataset.inicio || '';
    document.getElementById('horaFin').value = opcion.dataset.fin || '';
});

function abrirModalReserva(idMesa) {
    document.getElementById('inputIdMesa').value = idMesa;
    var mesaNumero = document.querySelector(`[data-id="${idMesa}"] h3`).textContent.replace('Mesa ', '');
    document.getElementById('mesaNumero').textContent = mesaNumero;

    document.getElementById('horaInicio').value = '';
    document.getElementById('horaFin').value = '';

    cargarHorarios(idMesa);

    document.getElementById('modalReserva').classList.add('show');
}

function cerrarModalReserva() {
    document.getElementById('modalReserva').classList.remove('show');
    document.getElementById('formReserva').reset();
}

function guardarReserva(e) {
    e.preventDefault();

    if (!document.getElementById('horaInicio').value || !document.getElementById('horaFin').value) {
        alert('✗ Elegí un horario disponible antes de guardar la reserva');
        return;
    }

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

// Sincronización en tiempo real
(function() {
    var filtroEstado = document.getElementById('filtroEstado');
    var filtroZona = document.getElementById('filtroZona');
    var lastUpdate = document.getElementById('lastUpdate');
    var historialList = document.getElementById('historialList');

    function applyFilters() {
        var estado = filtroEstado.value;
        var zona = filtroZona.value;

        document.querySelectorAll('[data-id]').forEach(function(card) {
            var cardZona = card.getAttribute('data-zona');
            var cardEstado = card.className.split(' ')[1] || '';
            
            var matchEstado = !estado || cardEstado === estado.toLowerCase();
            var matchZona = !zona || cardZona === zona;
            card.style.display = (matchEstado && matchZona) ? '' : 'none';
        });
    }

    function estadoClass(estado) {
        return (estado || '').toLowerCase();
    }

    function syncMesas(data) {
        if (!data || !Array.isArray(data.mesas)) return;

        data.mesas.forEach(function(mesa) {
            var card = document.querySelector(`[data-id="${mesa.id_mesa}"]`);
            if (!card) return;

            card.className = 'mesa-card ' + estadoClass(mesa.estado);
            card.setAttribute('data-zona', mesa.zona);

            var badge = card.querySelector('.badge');
            if (badge) {
                badge.className = 'badge ' + estadoClass(mesa.estado);
                badge.textContent = mesa.estado;
            }
        });

        if (data.stats) {
            document.getElementById('statLibre').textContent = data.stats.Libre || 0;
            document.getElementById('statOcupada').textContent = data.stats.Ocupada || 0;
            document.getElementById('statLimpieza').textContent = data.stats.Limpieza || 0;
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
                    historialList.innerHTML = '<div style="text-align: center; color: #aaa; padding: 20px;">Aún no hay cambios registrados.</div>';
                    return;
                }

                historialList.innerHTML = data.historial.map(item => `
                    <div class="history-item">
                        <div>
                            <strong>Mesa ${item.numero}:</strong>
                            ${item.estado_anterior} → ${item.estado_nuevo}
                        </div>
                        <div style="text-align: right; color: #aaa;">
                            <div>${item.usuario}</div>
                            <div>${item.fecha}</div>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {});
    }

    filtroEstado.addEventListener('change', applyFilters);
    filtroZona.addEventListener('change', applyFilters);

    poll();
    setInterval(poll, 3000);
})();
</script>

</body>
</html>
