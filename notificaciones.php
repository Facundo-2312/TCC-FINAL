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
        #contenido {
            display: block;
            height: auto;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
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

        .history-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-main {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            color: #fff;
        }

        .history-main span {
            color: #aaa;
            font-size: 13px;
        }

        .btn-action {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        .btn-action.cleaning {
            background: linear-gradient(135deg, #ffd60a, #ffb700);
            color: #000;
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

    <?php if (!empty($mensaje)) { ?>
        <div class="alert <?php echo htmlspecialchars($tipo_mensaje); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php } ?>

    <!-- Grid de Secciones -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">

        <!-- Próximas a Entrar -->
        <div class="history-section">
            <div class="history-header">
                <h2>👋 Próximas a Llegar</h2>
                <span style="color: #26d07c; font-size: 14px;">Próximas 60 minutos</span>
            </div>

            <?php if (empty($proximasEntrada)) { ?>
                <div class="history-empty">No hay reservas próximas</div>
            <?php } else { ?>
                <div>
                    <?php foreach ($proximasEntrada as $r) { ?>
                        <div class="history-item">
                            <div class="history-main" style="margin-bottom: 10px;">
                                <strong>🍽️ Mesa <?php echo (int)$r['mesa_numero']; ?> - <?php echo h($r['nombre_cliente']); ?></strong>
                                <span><?php echo (int)$r['cantidad_personas']; ?> personas | Entrada: <strong><?php echo date('H:i', strtotime($r['hora_inicio'])); ?></strong></span>
                            </div>
                            <form method="post" style="display: flex; gap: 10px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$r['id_reserva']; ?>">
                                <input type="hidden" name="accion" value="notificar">
                                <button type="submit" class="btn-action reserve" style="flex: 1;">
                                    📧 Enviar Email
                                </button>
                                <button type="button" class="btn-action reserve" style="flex: 1;" onclick="alert('SMS enviado a: ' + '<?php echo h($r['telefono'] ?? 'sin teléfono'); ?>');">
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
                <div>
                    <?php foreach ($proximasSalida as $r) { ?>
                        <div class="history-item">
                            <div class="history-main" style="margin-bottom: 10px;">
                                <strong>🍽️ Mesa <?php echo (int)$r['mesa_numero']; ?> - <?php echo h($r['nombre_cliente']); ?></strong>
                                <span><?php echo (int)$r['cantidad_personas']; ?> personas | Salida: <strong><?php echo date('H:i', strtotime($r['hora_fin'])); ?></strong></span>
                            </div>
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$r['id_reserva']; ?>">
                                <input type="hidden" name="accion" value="notificar">
                                <button type="submit" class="btn-action cleaning" style="width: 100%;">
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

            <div style="display: flex; flex-direction: column; gap: 15px; padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <div>
                    <span style="color: #aaa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">📧 Notificaciones por Email</span>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(38, 208, 124, 0.3); color: #26d07c; padding: 6px 12px; border-radius: 4px; font-size: 12px;">
                            ✓ Habilitadas
                        </span>
                    </div>
                </div>

                <p style="font-size: 12px; color: #aaa;">
                    Se enviarán confirmaciones automáticas al email de reserva y recordatorios antes de la llegada.
                </p>

                <a href="#" class="btn-action reserve" style="text-align: center;">
                    ⚙️ Configurar
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px; padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <div>
                    <span style="color: #aaa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">📱 Notificaciones por SMS</span>
                    <div style="margin-top: 10px;">
                        <span style="background: rgba(255, 214, 10, 0.3); color: #ffd60a; padding: 6px 12px; border-radius: 4px; font-size: 12px;">
                            ⚠️ No Configuradas
                        </span>
                    </div>
                </div>

                <p style="font-size: 12px; color: #aaa;">
                    Requiere integración con proveedor SMS (Twilio, AWS SNS, etc). Actualmente simulado.
                </p>

                <a href="#" class="btn-action cleaning" style="text-align: center;">
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

<script>
<?php echo 'var csrfToken = ' . json_encode(App\Support\Csrf::token()) . ';'; ?>

// Auto-actualizar cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>

</body>
</html>
