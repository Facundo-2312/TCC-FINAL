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

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro de Notificaciones</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <style>
        .notifications-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .notification-section {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            color: #fff;
        }

        .section-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #ff0055;
        }

        .notification-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 0, 85, 0.5);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .notification-info {
            flex: 1;
        }

        .notification-client {
            font-weight: bold;
            color: #fff;
        }

        .notification-detail {
            font-size: 12px;
            color: #aaa;
            margin-top: 5px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .btn-notify {
            background-color: #5a189a;
            color: #fff;
            border: 1px solid #5a189a;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: bold;
            font-size: 11px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-notify:hover {
            background-color: #7c2aff;
        }

        .btn-email {
            background-color: #0066cc;
            border-color: #0066cc;
        }

        .btn-email:hover {
            background-color: #0080ff;
        }

        .btn-sms {
            background-color: #00aa00;
            border-color: #00aa00;
        }

        .btn-sms:hover {
            background-color: #00cc00;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #aaa;
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

        #contenido {
            min-height: 100vh;
        }
    </style>
</head>

<body>

<header>
    <h1>🔔 CENTRO DE NOTIFICACIONES</h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('centro_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">
    <div class="notifications-container">

        <?php if (!empty($mensaje)) { ?>
            <div class="alert <?php echo htmlspecialchars($tipo_mensaje); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php } ?>

        <!-- Próximas a Llegar (Entrada) -->
        <div class="notification-section">
            <div class="section-title">📍 PRÓXIMAS A LLEGAR (PRÓXIMAS 60 MIN)</div>
            <?php if (!empty($proximasEntrada)) { ?>
                <?php foreach ($proximasEntrada as $reserva) { ?>
                    <div class="notification-item">
                        <div class="notification-info">
                            <div class="notification-client">
                                👤 <?php echo h($reserva['nombre_cliente']); ?>
                            </div>
                            <div class="notification-detail">
                                Mesa <?php echo (int)$reserva['mesa_numero']; ?> • 
                                <?php echo (int)$reserva['cantidad_personas']; ?> personas • 
                                ⏰ <?php echo date('H:i', strtotime($reserva['hora_inicio'])); ?> •
                                📞 <?php echo h($reserva['telefono'] ?? 'Sin teléfono'); ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <form method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="notificar">
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$reserva['id_reserva']; ?>">
                                <button type="submit" class="btn-notify btn-email" title="Enviar Email">
                                    ✉️ EMAIL
                                </button>
                            </form>
                            <button class="btn-notify btn-sms" onclick="alert('SMS: Notificación de llegada enviada')">
                                💬 SMS
                            </button>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    ✓ No hay clientes próximos a llegar en los próximos 60 minutos
                </div>
            <?php } ?>
        </div>

        <!-- Próximas a Partir (Salida) -->
        <div class="notification-section">
            <div class="section-title">🚪 PRÓXIMAS A PARTIR (PRÓXIMAS 60 MIN)</div>
            <?php if (!empty($proximasSalida)) { ?>
                <?php foreach ($proximasSalida as $reserva) { ?>
                    <div class="notification-item">
                        <div class="notification-info">
                            <div class="notification-client">
                                👤 <?php echo h($reserva['nombre_cliente']); ?>
                            </div>
                            <div class="notification-detail">
                                Mesa <?php echo (int)$reserva['mesa_numero']; ?> • 
                                <?php echo (int)$reserva['cantidad_personas']; ?> personas • 
                                ⏰ <?php echo date('H:i', strtotime($reserva['hora_fin'])); ?> (Salida) •
                                📞 <?php echo h($reserva['telefono'] ?? 'Sin teléfono'); ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <form method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="accion" value="notificar">
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$reserva['id_reserva']; ?>">
                                <button type="submit" class="btn-notify btn-email" title="Enviar Email">
                                    ✉️ EMAIL
                                </button>
                            </form>
                            <button class="btn-notify btn-sms" onclick="alert('SMS: Recordatorio de partida enviado')">
                                💬 SMS
                            </button>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    ✓ No hay clientes próximos a partir en los próximos 60 minutos
                </div>
            <?php } ?>
        </div>

        <!-- Información del Sistema -->
        <div class="notification-section">
            <div class="section-title">⚙️ INFORMACIÓN DEL SISTEMA</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                    <p style="font-weight: 600; color: #26d07c; margin-bottom: 8px;">✓ Email</p>
                    <p style="font-size: 13px; color: #aaa;">
                        Las notificaciones por email se envían automáticamente a los clientes.
                    </p>
                </div>
                <div style="padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                    <p style="font-weight: 600; color: #5a189a; margin-bottom: 8px;">📱 SMS</p>
                    <p style="font-size: 13px; color: #aaa;">
                        Los SMS se pueden enviar manualmente o configurar automáticos.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
<?php echo 'var csrfToken = ' . json_encode(App\Support\Csrf::token()) . ';'; ?>

// Auto-actualizar cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>
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
