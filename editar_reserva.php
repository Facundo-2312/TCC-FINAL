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
$idReserva = (int)($_GET['id'] ?? 0);

if ($idReserva <= 0) {
    app_redirect('reportes_reservas.php');
    exit;
}

$reserva = $servicioReserva->obtener($idReserva);
if (!$reserva) {
    app_redirect('reportes_reservas.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    $accion = trim($_POST['accion'] ?? '');

    if ($accion === 'actualizar') {
        // Actualizar datos de la reserva
        $nombreCliente = trim($_POST['nombre_cliente'] ?? '');
        $cantidadPersonas = (int)($_POST['cantidad_personas'] ?? 0);
        $horaInicio = trim($_POST['hora_inicio'] ?? '');
        $horaFin = trim($_POST['hora_fin'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $notas = trim($_POST['notas'] ?? '');

        if (empty($nombreCliente) || $cantidadPersonas <= 0) {
            $mensaje = 'Datos inválidos';
            $tipo_mensaje = 'error';
        } else {
            // Preparar y ejecutar UPDATE
            $stmt = mysqli_prepare($conexion,
                'UPDATE reservas SET nombre_cliente = ?, cantidad_personas = ?, 
                 hora_inicio = ?, hora_fin = ?, telefono = ?, notas = ? WHERE id_reserva = ?'
            );
            
            mysqli_stmt_bind_param($stmt, 'sissssii', 
                $nombreCliente, $cantidadPersonas, 
                $horaInicio, $horaFin, $telefono, $notas, $idReserva
            );

            if (mysqli_stmt_execute($stmt)) {
                $mensaje = '✓ Reserva actualizada exitosamente';
                $tipo_mensaje = 'success';
                $reserva['nombre_cliente'] = $nombreCliente;
                $reserva['cantidad_personas'] = $cantidadPersonas;
                $reserva['hora_inicio'] = $horaInicio;
                $reserva['hora_fin'] = $horaFin;
                $reserva['telefono'] = $telefono;
                $reserva['notas'] = $notas;
            } else {
                $mensaje = 'Error al actualizar: ' . mysqli_error($conexion);
                $tipo_mensaje = 'error';
            }

            mysqli_stmt_close($stmt);
        }

    } elseif ($accion === 'cancelar') {
        if ($servicioReserva->cancelar($idReserva)) {
            $mensaje = '✓ Reserva cancelada';
            $tipo_mensaje = 'success';
            $reserva['estado'] = 'Cancelada';
        } else {
            $mensaje = 'Error al cancelar la reserva';
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener datos de mesa
$stmt = mysqli_prepare($conexion, 'SELECT numero FROM mesas WHERE id_mesa = ?');
mysqli_stmt_bind_param($stmt, 'i', $reserva['id_mesa']);
mysqli_stmt_execute($stmt);
$resultMesa = mysqli_stmt_get_result($stmt);
$mesa = mysqli_fetch_assoc($resultMesa);
mysqli_stmt_close($stmt);

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Reserva</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <style>
        .edit-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            color: #fff;
        }

        .section-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #ff0055;
            border-bottom: 1px solid rgba(255, 0, 85, 0.2);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #fff;
        }

        .form-group input,
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 15px;
            border-left: 3px solid #ff0055;
            border-radius: 5px;
            margin-bottom: 12px;
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
            font-size: 14px;
        }

        .btn-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-save {
            background-color: #ff0055;
            color: #fff;
            border: 1px solid #ff0055;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-save:hover {
            background-color: #ff1a66;
            transform: scale(1.05);
        }

        .btn-cancel {
            background-color: #ff6b6b;
            color: #fff;
            border: 1px solid #ff6b6b;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-cancel:hover {
            background-color: #ff8080;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-left: 4px solid;
        }

        .alert.success {
            background: rgba(38, 208, 124, 0.1);
            border-color: #26d07c;
            color: #26d07c;
        }

        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
            color: #ff6b6b;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #ff0055;
            color: #fff;
        }

        .status-badge.cancelada {
            background: #ff6b6b;
        }

        .status-badge.completada {
            background: #26d07c;
        }

        #contenido {
            min-height: 100vh;
        }
    </style>
</head>

<body>

<header>
    <h1>✏️ EDITAR RESERVA #<?php echo (int)$idReserva; ?></h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('reportes_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">
    <div class="edit-container">

        <?php if (!empty($mensaje)) { ?>
            <div class="alert <?php echo htmlspecialchars($tipo_mensaje); ?>">
                <?php echo h($mensaje); ?>
            </div>
        <?php } ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">

            <!-- Formulario de Edición -->
            <div class="form-section">
                <div class="section-title">📝 DATOS DE LA RESERVA</div>

                <?php if ($reserva['estado'] === 'Cancelada') { ?>
                    <div class="alert error">
                        ⚠️ Esta reserva está cancelada y no puede ser editada.
                    </div>
                <?php } else { ?>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="accion" value="actualizar">

                        <div class="form-group">
                            <label for="nombre_cliente">👤 Nombre del Cliente *</label>
                            <input type="text" id="nombre_cliente" name="nombre_cliente" required 
                                   value="<?php echo h($reserva['nombre_cliente']); ?>" maxlength="100">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="cantidad_personas"># Cantidad *</label>
                                <input type="number" id="cantidad_personas" name="cantidad_personas" required 
                                       value="<?php echo (int)$reserva['cantidad_personas']; ?>" min="1" max="50">
                            </div>
                            <div class="form-group">
                                <label for="telefono">📱 Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" 
                                       value="<?php echo h($reserva['telefono'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hora_inicio">⏰ Entrada *</label>
                                <input type="datetime-local" id="hora_inicio" name="hora_inicio" required 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($reserva['hora_inicio'])); ?>">
                            </div>
                            <div class="form-group">
                                <label for="hora_fin">⏰ Salida *</label>
                                <input type="datetime-local" id="hora_fin" name="hora_fin" required 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($reserva['hora_fin'])); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notas">📝 Notas</label>
                            <textarea id="notas" name="notas" style="min-height: 80px;"><?php echo h($reserva['notas'] ?? ''); ?></textarea>
                        </div>

                        <div class="btn-actions">
                            <button type="submit" class="btn-save">✓ GUARDAR CAMBIOS</button>
                        </div>
                    </form>
                <?php } ?>
            </div>

            <!-- Panel de Información -->
            <div>
                <div class="form-section">
                    <div class="section-title">📊 INFORMACIÓN</div>

                    <div class="info-item">
                        <span class="info-label">Estado</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo strtolower(htmlspecialchars($reserva['estado'])); ?>">
                                <?php echo h($reserva['estado']); ?>
                            </span>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Mesa</span>
                        <span class="info-value">Mesa <?php echo (int)($mesa['numero'] ?? '?'); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Reserva ID</span>
                        <span class="info-value">#<?php echo (int)$idReserva; ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Fecha Creación</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_creacion'])); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Duración Estimada</span>
                        <span class="info-value">
                            <?php
                                $inicio = new DateTime($reserva['hora_inicio']);
                                $fin = new DateTime($reserva['hora_fin']);
                                $duracion = $inicio->diff($fin)->format('%H:%i');
                                echo h($duracion);
                            ?>
                        </span>
                    </div>
                </div>

                <?php if ($reserva['estado'] !== 'Cancelada') { ?>
                    <div class="form-section" style="margin-top: 20px;">
                        <div class="section-title">⚠️ ACCIONES</div>

                        <form method="post" style="width: 100%;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="btn-cancel" style="width: 100%;">
                                ✗ CANCELAR RESERVA
                            </button>
                        </form>

                        <p style="font-size: 12px; color: #aaa; margin-top: 12px; text-align: center;">
                            Esta acción no se puede deshacer
                        </p>
                    </div>
                <?php } ?>
            </div>

        </div>

    </div>
</div>

</body>
</html>
