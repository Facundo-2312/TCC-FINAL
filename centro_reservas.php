<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

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
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .module-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #ff0055;
            border-radius: 10px;
            padding: 25px;
            color: #fff;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 320px;
        }

        .module-card:hover {
            background: rgba(255, 0, 85, 0.1);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 0, 85, 0.2);
            border-color: #ff0055;
        }

        .module-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
        }

        .module-title {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #fff;
        }

        .module-desc {
            font-size: 13px;
            color: #aaa;
            margin-bottom: 20px;
            flex: 1;
        }

        .module-link {
            background-color: #ff0055;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            display: block;
        }

        .module-link:hover {
            background-color: #ff1a66;
            transform: scale(1.05);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stat-card {
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
            font-size: 2em;
            font-weight: bold;
            color: #ff0055;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.02);
            border-top: 2px solid #ff0055;
            border-bottom: 2px solid #ff0055;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            color: #fff;
        }

        .welcome-section h2 {
            margin: 0;
            font-size: 1.8em;
            color: #ff0055;
        }

        .welcome-section p {
            margin: 10px 0 0 0;
            color: #aaa;
            font-size: 14px;
        }

        #contenido {
            min-height: 100vh;
        }
    </style>
</head>

<body>

<header>
    <h1>🎯 CENTRO DE RESERVAS</h1>
    <a class="salir" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Volver
    </a>
</header>

<div id="contenido">

    <!-- Bienvenida -->
    <div class="welcome-section">
        <h2>Gestión Integral de Reservas</h2>
        <p>Accede a todas las herramientas para administrar reservas, mesas y notificaciones de clientes</p>
    </div>

    <!-- Estadísticas -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Mesas Disponibles</div>
            <div class="stat-value" id="mesasDisponibles">--</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Reservas Activas</div>
            <div class="stat-value" id="reservasActivas">--</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sistema</div>
            <div class="stat-value" style="font-size: 1.2em;">✓ Operativo</div>
        </div>
    </div>

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

<script>
// Simular estadísticas
(function() {
    // Aquí podrías hacer fetch a APIs reales
    document.getElementById('mesasDisponibles').textContent = Math.floor(Math.random() * 8) + 5;
    document.getElementById('reservasActivas').textContent = Math.floor(Math.random() * 12) + 3;
})();
</script>

</body>
</html>
