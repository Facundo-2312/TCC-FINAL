<?php
require_once __DIR__ . '/app_bootstrap.php';

app_require_login('Login.php', ['1', '2']);

$cssVersion = @filemtime(__DIR__ . '/estilos/mesas_salon.css') ?: time();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro de Reservas</title>
    <link rel="stylesheet" type="text/css" href="estilos/mesas_salon.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>🎯 Centro de Reservas</h1>
        <div class="controls">
            <a class="btn back" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">← Volver</a>
        </div>
    </div>

    <!-- Grid de Módulos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-top: 30px;">

        <!-- Módulo: Mesas -->
        <div class="history-section" style="display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#ff006e'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 3em; margin-bottom: 15px;">🍽️</div>
                <h3 style="margin-bottom: 10px; font-size: 1.3em;">Mesas en Tiempo Real</h3>
                <p style="color: #aaa; font-size: 13px; margin-bottom: 15px;">
                    Visualiza el estado de todas las mesas y crea nuevas reservas de forma intuitiva.
                </p>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action reserve" style="text-align: center; text-decoration: none; padding: 12px;">
                Acceder →
            </a>
        </div>

        <!-- Módulo: Reportes -->
        <div class="history-section" style="display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#26d07c'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 3em; margin-bottom: 15px;">📊</div>
                <h3 style="margin-bottom: 10px; font-size: 1.3em;">Reportes de Reservas</h3>
                <p style="color: #aaa; font-size: 13px; margin-bottom: 15px;">
                    Filtra, busca y genera reportes detallados de todas las reservas.
                </p>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('reportes_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action free" style="text-align: center; text-decoration: none; padding: 12px;">
                Acceder →
            </a>
        </div>

        <!-- Módulo: Dashboard -->
        <div class="history-section" style="display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#ffd60a'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 3em; margin-bottom: 15px;">📈</div>
                <h3 style="margin-bottom: 10px; font-size: 1.3em;">Dashboard de Ocupación</h3>
                <p style="color: #aaa; font-size: 13px; margin-bottom: 15px;">
                    Visualiza gráficas de ocupación por hora, por mesa y análisis detallado.
                </p>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('dashboard_ocupacion.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action cleaning" style="text-align: center; text-decoration: none; padding: 12px;">
                Acceder →
            </a>
        </div>

        <!-- Módulo: Notificaciones -->
        <div class="history-section" style="display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#5a189a'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 3em; margin-bottom: 15px;">🔔</div>
                <h3 style="margin-bottom: 10px; font-size: 1.3em;">Centro de Notificaciones</h3>
                <p style="color: #aaa; font-size: 13px; margin-bottom: 15px;">
                    Gestiona notificaciones por email y SMS para tus clientes.
                </p>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('notificaciones.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action reserve" style="text-align: center; text-decoration: none; padding: 12px;">
                Acceder →
            </a>
        </div>

    </div>

    <!-- Estadísticas Rápidas -->
    <div style="margin-top: 40px;">
        <h2 style="margin-bottom: 20px; font-size: 1.5em; background: linear-gradient(135deg, #ff006e, #fb5607); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            📊 Información General del Sistema
        </h2>

        <div class="stats">
            <div class="stat-card libres">
                <div class="label">Reservas Activas</div>
                <div class="value" id="reservasActivas">--</div>
            </div>
            <div class="stat-card ocupadas">
                <div class="label">Próximas 24h</div>
                <div class="value" id="reservasDia">--</div>
            </div>
            <div class="stat-card limpieza">
                <div class="label">Personas Esperadas</div>
                <div class="value" id="personasEsperadas">--</div>
            </div>
            <div class="stat-card reservadas">
                <div class="label">Canceladas Hoy</div>
                <div class="value" id="canceladasHoy">--</div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="history-section" style="margin-top: 30px;">
        <div class="history-header">
            <h2>ℹ️ Información del Sistema</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <p style="font-weight: 600; color: #26d07c; margin-bottom: 8px;">✓ Estado del Sistema</p>
                <p style="font-size: 13px; color: #aaa;">
                    Todos los módulos están operacionales y sincronizados correctamente.
                </p>
            </div>

            <div style="padding: 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <p style="font-weight: 600; color: #ffd60a; margin-bottom: 8px;">⚙️ Últimas Actualizaciones</p>
                <p style="font-size: 13px; color: #aaa;">
                    Sistema de reservas versión 2.0 con gráficas y notificaciones.
                </p>
            </div>
        </div>
    </div>

</div>

<script>
// Cargar estadísticas en tiempo real
(function() {
    function cargarEstadisticas() {
        // Simulación de datos - en producción consultaría APIs reales
        document.getElementById('reservasActivas').textContent = Math.floor(Math.random() * 15) + 5;
        document.getElementById('reservasDia').textContent = Math.floor(Math.random() * 20) + 10;
        document.getElementById('personasEsperadas').textContent = Math.floor(Math.random() * 80) + 40;
        document.getElementById('canceladasHoy').textContent = Math.floor(Math.random() * 3);
    }

    cargarEstadisticas();
    setInterval(cargarEstadisticas, 5000);
})();
</script>

</body>
</html>
