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

$servicioNotificacion = new App\Services\NotificacionService();

// Obtener próximas reservas
$proximasEntrada = $servicioNotificacion->obtenerProximas(60);
$proximasSalida = $servicioNotificacion->obtenerProximasASalir(60);

$mensaje = '';
$tipo_mensaje = '';

// Procesar envío manual de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    csrf_verify_or_die();
    
    $idReserva = (int)($_POST['id_reserva'] ?? 0);
    $accion = trim($_POST['accion']);

    if ($accion === 'notificar' && $idReserva > 0) {
        $resultado = $servicioNotificacion->notificarConfirmacion($idReserva);
        if ($resultado['ok']) {
            $mensaje = '✓ Notificación enviada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = '✗ Error: ' . ($resultado['error'] ?? 'Error desconocido');
            $tipo_mensaje = 'error';
        }
    }
}

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🔔 Centro de Notificaciones</h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>">← Mesas</a>
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
        </div>
    </div>

    <!-- Mensaje -->
    <?php if (!empty($mensaje)) { ?>
        <div class="alert <?php echo $tipo_mensaje; ?>">
            <?php echo h($mensaje); ?>
        </div>
    <?php } ?>

    <!-- Grid de Secciones -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">

        <!-- Próximas a Entrar -->
        <div class="history-section">
            <div class="history-header">
                <h2>👋 Próximas a Llegar</h2>
                <span style="color: #26d07c; font-size: 14px;">Próximas 60 minutos</span>
            </div>

            <?php if (empty($proximasEntrada)) { ?>
                <div class="history-empty">No hay reservas próximas</div>
            <?php } else { ?>
                <div class="history-list" style="max-height: none;">
                    <?php foreach ($proximasEntrada as $r) { ?>
                        <div class="history-item" style="flex-direction: column; align-items: flex-start;">
                            <div class="history-main" style="width: 100%; margin-bottom: 10px;">
                                <strong>🍽️ Mesa <?php echo (int)$r['mesa_numero']; ?> - <?php echo h($r['nombre_cliente']); ?></strong>
                                <span><?php echo (int)$r['cantidad_personas']; ?> personas | Entrada: <strong><?php echo date('H:i', strtotime($r['hora_inicio'])); ?></strong></span>
                            </div>
                            <form method="post" style="width: 100%; display: flex; gap: 10px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$r['id_reserva']; ?>">
                                <input type="hidden" name="accion" value="notificar">
                                <button type="submit" class="btn-action reserve" style="flex: 1; padding: 8px 12px; font-size: 12px;">
                                    📧 Enviar Email
                                </button>
                                <button type="button" class="btn-action reserve" style="flex: 1; padding: 8px 12px; font-size: 12px;" onclick="alert('SMS enviado a: ' + '<?php echo h($r['telefono'] ?? 'sin teléfono'); ?>');">
                                    📱 Enviar SMS
                                </button>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <!-- Próximas a Salir -->
        <div class="history-section">
            <div class="history-header">
                <h2>👋 Próximas a Partir</h2>
                <span style="color: #ffd60a; font-size: 14px;">Próximas 60 minutos</span>
            </div>

            <?php if (empty($proximasSalida)) { ?>
                <div class="history-empty">No hay reservas próximas a terminar</div>
            <?php } else { ?>
                <div class="history-list" style="max-height: none;">
                    <?php foreach ($proximasSalida as $r) { ?>
                        <div class="history-item" style="flex-direction: column; align-items: flex-start;">
                            <div class="history-main" style="width: 100%; margin-bottom: 10px;">
                                <strong>🍽️ Mesa <?php echo (int)$r['mesa_numero']; ?> - <?php echo h($r['nombre_cliente']); ?></strong>
                                <span><?php echo (int)$r['cantidad_personas']; ?> personas | Salida: <strong><?php echo date('H:i', strtotime($r['hora_fin'])); ?></strong></span>
                            </div>
                            <form method="post" style="width: 100%; display: flex; gap: 10px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$r['id_reserva']; ?>">
                                <input type="hidden" name="accion" value="notificar">
                                <button type="submit" class="btn-action cleaning" style="flex: 1; padding: 8px 12px; font-size: 12px;">
                                    📧 Recordatorio
                                </button>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

    </div>

    <!-- Configuración de Notificaciones -->
    <div class="history-section" style="margin-top: 30px;">
        <div class="history-header">
            <h2>⚙️ Configuración de Notificaciones</h2>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <div class="mesa-info" style="display: flex; flex-direction: column; gap: 15px; padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <div class="mesa-info-item">
                    <span class="label">📧 Notificaciones por Email</span>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(38, 208, 124, 0.3); color: #26d07c; padding: 6px 12px; border-radius: 4px; font-size: 12px;">
                            ✓ Habilitadas
                        </span>
                    </div>
                </div>

                <p style="font-size: 12px; color: #aaa;">
                    Se enviarán confirmaciones automáticas al email de reserva y recordatorios antes de la llegada.
                </p>

                <a href="#" class="btn-action reserve" style="padding: 8px 12px; text-align: center; font-size: 12px; text-decoration: none;">
                    ⚙️ Configurar
                </a>
            </div>

            <div class="mesa-info" style="display: flex; flex-direction: column; gap: 15px; padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <div class="mesa-info-item">
                    <span class="label">📱 Notificaciones por SMS</span>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(255, 214, 10, 0.3); color: #ffd60a; padding: 6px 12px; border-radius: 4px; font-size: 12px;">
                            ⚠️ No Configuradas
                        </span>
                    </div>
                </div>

                <p style="font-size: 12px; color: #aaa;">
                    Requiere integración con proveedor SMS (Twilio, AWS SNS, etc). Actualmente simulado.
                </p>

                <a href="#" class="btn-action cleaning" style="padding: 8px 12px; text-align: center; font-size: 12px; text-decoration: none;">
                    ⚙️ Configurar
                </a>
            </div>

        </div>

        <div style="margin-top: 20px; padding: 15px; background: rgba(90, 24, 154, 0.1); border-left: 4px solid #5a189a; border-radius: 4px;">
            <p style="font-weight: 600; color: #5a189a; margin-bottom: 10px;">💡 Consejo:</p>
            <p style="font-size: 13px; color: #aaa;">
                Las notificaciones automáticas se envían 30 minutos antes de cada reserva confirmada. 
                Puedes enviar notificaciones manuales desde aquí en cualquier momento.
            </p>
        </div>
    </div>

</div>

</body>
</html>
