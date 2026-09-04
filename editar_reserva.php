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

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Reserva</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>✏️ Editar Reserva #<?php echo (int)$idReserva; ?></h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('reportes_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
        </div>
    </div>

    <!-- Mensaje -->
    <?php if (!empty($mensaje)) { ?>
        <div class="alert <?php echo $tipo_mensaje; ?>">
            <?php echo h($mensaje); ?>
        </div>
    <?php } ?>

    <!-- Contenido Principal -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        
        <!-- Formulario -->
        <div class="history-section">
            <div class="history-header">
                <h2>📝 Detalles de la Reserva</h2>
            </div>

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
                            <label for="cantidad_personas"># Cantidad de Personas *</label>
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
                            <label for="hora_inicio">⏰ Hora Entrada *</label>
                            <input type="datetime-local" id="hora_inicio" name="hora_inicio" required 
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($reserva['hora_inicio'])); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora_fin">⏰ Hora Salida *</label>
                            <input type="datetime-local" id="hora_fin" name="hora_fin" required 
                                   value="<?php echo date('Y-m-d\TH:i', strtotime($reserva['hora_fin'])); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notas">📝 Notas Especiales</label>
                        <textarea id="notas" name="notas"><?php echo h($reserva['notas'] ?? ''); ?></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-submit">✓ Guardar Cambios</button>
                    </div>
                </form>
            <?php } ?>
        </div>

        <!-- Información -->
        <div class="history-section">
            <div class="history-header">
                <h2>📊 Información General</h2>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div class="mesa-info" style="display: flex; flex-direction: column;">
                    <div class="mesa-info-item">
                        <span class="label">Mesa Asignada</span>
                        <span class="value" style="font-size: 1.5em;">🍽️ Mesa <?php echo (int)$mesa['numero']; ?></span>
                    </div>

                    <div class="mesa-info-item">
                        <span class="label">Estado de la Reserva</span>
                        <span class="mesa-badge <?php echo strtolower($reserva['estado']); ?>" style="width: fit-content; margin-top: 5px;">
                            <?php echo h($reserva['estado']); ?>
                        </span>
                    </div>

                    <div class="mesa-info-item">
                        <span class="label">Creada el</span>
                        <span class="value"><?php echo date('d/m/Y H:i', strtotime($reserva['fecha_creacion'])); ?></span>
                    </div>

                    <div class="mesa-info-item">
                        <span class="label">Duración</span>
                        <span class="value">
                            <?php 
                            $horas = round((strtotime($reserva['hora_fin']) - strtotime($reserva['hora_inicio'])) / 3600, 1);
                            echo $horas . ' horas';
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Acciones -->
                <?php if ($reserva['estado'] === 'Confirmada') { ?>
                    <form method="post" style="margin-top: 20px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn-action cleaning" onclick="return confirm('¿Estás seguro de que deseas cancelar esta reserva?')" style="width: 100%;">
                            ✗ Cancelar Reserva
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
