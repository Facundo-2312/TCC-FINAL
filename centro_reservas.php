<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$reservasActivas = (new App\Services\ReservaService())->listarActivas();
$mesasCentro = (new App\Repositories\MesaRepository())->listar();
$mesasDisponibles = count(array_filter($mesasCentro, function ($mesa) {
    return ($mesa['estado'] ?? '') === 'Libre';
}));
$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

function cr_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro de Reservas</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <style>
        body {
            margin: 0;
            color: #f3f4f8;
            font-family: 'Segoe UI', system-ui, Arial, sans-serif;
            background:
                radial-gradient(900px 420px at 0 0, rgba(255, 45, 111, .14), transparent 65%),
                linear-gradient(160deg, #0f1115, #171a21);
        }

        .center-header {
            min-height: 76px;
            height: auto;
            padding: 14px clamp(16px, 4vw, 38px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            position: relative;
            top: auto;
        }

        .center-header h1 {
            margin: 0;
            text-align: left;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            letter-spacing: .3px;
        }

        .center-header .salir {
            flex: 0 0 auto;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            padding: 12px 0;
            max-width: 1280px;
            margin: 0 auto;
        }

        .module-card {
            background: linear-gradient(180deg, rgba(24, 27, 35, .96), rgba(29, 32, 41, .96));
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 16px;
            padding: 22px;
            color: #fff;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 250px;
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(0, 0, 0, .3);
            border-color: rgba(255, 45, 111, .65);
        }

        .module-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: rgba(255, 45, 111, .14);
            font-size: 1.6rem;
            margin-bottom: 18px;
        }

        .module-title {
            font-size: 1.08rem;
            font-weight: bold;
            margin-bottom: 9px;
            color: #fff;
        }

        .module-desc {
            font-size: .9rem;
            color: #a9adba;
            line-height: 1.5;
            margin-bottom: 18px;
            flex: 1;
        }

        .module-link {
            background: linear-gradient(135deg, #ff2d6f, #e2115a);
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            transition: filter .2s ease, transform .2s ease;
            display: inline-flex;
            justify-content: center;
        }

        .module-link:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 8px 0 18px;
            max-width: 1280px;
            margin: 0 auto;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(24, 27, 35, .96), rgba(29, 32, 41, .96));
            border: 1px solid rgba(255, 255, 255, .1);
            border-left: 4px solid #ff2d6f;
            border-radius: 13px;
            padding: 17px 20px;
            color: #f3f4f8;
        }

        .stat-label {
            color: #a9adba;
            font-size: .82rem;
            margin-bottom: 7px;
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: bold;
            color: #ff5c8f;
        }

        .active-reservations {
            max-width: 1280px;
            margin: 0 auto 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 15px;
            background: rgba(24, 27, 35, .9);
        }

        .active-reservations h2 {
            margin: 0 0 14px;
            color: #f3f4f8;
            font-size: 1.1rem;
        }

        .reservation-list {
            display: grid;
            gap: 10px;
        }

        .reservation-row {
            display: grid;
            grid-template-columns: minmax(150px, 1.4fr) repeat(3, minmax(110px, 1fr));
            gap: 12px;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 10px;
            background: rgba(255, 255, 255, .025);
            color: #dfe1e8;
            font-size: .9rem;
        }

        .reservation-row strong {
            color: #fff;
        }

        .empty-reservations {
            margin: 0;
            color: #a9adba;
            font-size: .92rem;
        }

        #contenido {
            display: block;
            height: auto;
            min-height: 0;
            padding: 22px clamp(16px, 4vw, 38px) 40px;
            text-align: left;
            box-sizing: border-box;
        }

        #contenido * {
            box-sizing: border-box;
        }

        @media (max-width: 760px) {
            .stats-row {
                grid-template-columns: 1fr;
            }

            .reservation-row {
                grid-template-columns: 1fr 1fr;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header class="center-header">
    <h1>🎯 CENTRO DE RESERVAS</h1>
    <a class="salir" href="<?php echo cr_h(app_url('inicio_rol.php')); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">

    <!-- Estadísticas -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Mesas Disponibles</div>
            <div class="stat-value"><?php echo $mesasDisponibles; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Reservas Activas</div>
            <div class="stat-value"><?php echo count($reservasActivas); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sistema</div>
            <div class="stat-value" style="font-size: 1.2em;">✓ Operativo</div>
        </div>
    </div>

    <section class="active-reservations" aria-labelledby="active-reservations-title">
        <h2 id="active-reservations-title">Reservas activas</h2>
        <?php if ($reservasActivas) { ?>
            <div class="reservation-list">
                <?php foreach ($reservasActivas as $reserva) { ?>
                    <div class="reservation-row">
                        <strong><?php echo cr_h($reserva['nombre_cliente']); ?></strong>
                        <span>Mesa <?php echo (int)$reserva['mesa_numero']; ?></span>
                        <span><?php echo cr_h(date('d/m/Y H:i', strtotime($reserva['hora_inicio']))); ?></span>
                        <span><?php echo (int)$reserva['cantidad_personas']; ?> persona(s)</span>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p class="empty-reservations">No hay reservas activas por el momento.</p>
        <?php } ?>
    </section>

    <!-- Grid de Módulos -->
    <div class="modules-grid">

        <!-- Módulo: Mesas -->
        <div class="module-card">
            <div>
                <div class="module-icon">🍽️</div>
                <div class="module-title">Mesas en Tiempo Real</div>
                <div class="module-desc">
                    Visualiza el estado de todas las mesas en tiempo real y crea nuevas reservas de forma intuitiva.
                </div>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>" class="module-link">
                ACCEDER →
            </a>
        </div>

        <!-- Módulo: Reportes -->
        <div class="module-card">
            <div>
                <div class="module-icon">📊</div>
                <div class="module-title">Reportes de Reservas</div>
                <div class="module-desc">
                    Filtra, busca y exporta reservas. Genera reportes con estadísticas detalladas de ocupación.
                </div>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('reportes_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>" class="module-link">
                ACCEDER →
            </a>
        </div>

        <!-- Módulo: Dashboard -->
        <div class="module-card">
            <div>
                <div class="module-icon">📈</div>
                <div class="module-title">Dashboard de Ocupación</div>
                <div class="module-desc">
                    Visualiza gráficos de ocupación por hora, mesa y cliente. Analiza patrones de ocupación.
                </div>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('dashboard_ocupacion.php'), ENT_QUOTES, 'UTF-8'); ?>" class="module-link">
                ACCEDER →
            </a>
        </div>

        <!-- Módulo: Notificaciones -->
        <div class="module-card">
            <div>
                <div class="module-icon">🔔</div>
                <div class="module-title">Centro de Notificaciones</div>
                <div class="module-desc">
                    Gestiona confirmaciones de llegada, recordatorios de salida y comunicación con clientes.
                </div>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('notificaciones.php'), ENT_QUOTES, 'UTF-8'); ?>" class="module-link">
                ACCEDER →
            </a>
        </div>

    </div>

</div>

</body>
</html>
