<?php
require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php', ['1']);

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();
$chatbotJsVersion = @filemtime(__DIR__ . '/chatbot.js') ?: time();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inicio</title>
    <link rel="icon" type="image/jpeg" href="<?php echo htmlspecialchars(app_url('img/logonuevo.jpeg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>

<body>

<header>
    <h1>RESTAURANTE-UY</h1> 
    <a class="salir" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <b>Salir</b> 
        <i class="fas fa-sign-out-alt" style="margin-left: 5px;"></i>
    </a>
</header>

<div id="contenido">

    <div class="imagen">
        <img class="img" src="img/logonuevo.jpeg">
    </div>

    <div class="botones">

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('EmpleadoI.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-user" style="margin-right: 5px;"></i>Funcionarios
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('productos.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-plus" style="margin-right: 5px;"></i>Productos
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('informes.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-chart-bar" style="margin-right: 5px;"></i>Informes
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('pedidos.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i>Pedidos
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('cocina.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-utensils" style="margin-right: 5px;"></i>Cocina
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('caja.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-coins" style="margin-right: 5px;"></i>Caja
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('mesas.php'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-chair" style="margin-right: 5px;"></i>Mesas
            </a> 
        </div>

        <div class="Links"> 
            <a href="<?php echo htmlspecialchars(app_url('centro_reservas.php'), ENT_QUOTES, 'UTF-8'); ?>" style="background: linear-gradient(135deg, #ff006e, #fb5607);">
                <i class="fas fa-calendar-check" style="margin-right: 5px;"></i>Centro de Reservas
            </a> 
        </div>

    </div>

</div>

<footer class="foot">

<div class="footer-content">

  <div class="footer-section">
    <h3>Contacto</h3>
    <p><i class="fas fa-map-marker-alt"></i> Dirección: Av.Brasil 1024</p>
    <p><i class="fas fa-envelope"></i> 
        <a href="mailto:info@restaurante-uy.com">info@restaurante-uy.com</a>
    </p>
    <p><i class="fas fa-phone"></i> +1 234 567 890</p>
  </div>

  <div class="footer-section">
    <h3>Síguenos</h3>
    <ul class="social-icons">
      <li><a href="#"><i class="fab fa-facebook fa-3x"></i></a></li>
    <li><a href="#" aria-label="X"><span class="x-brand x-3x">X</span></a></li>
      <li><a href="#"><i class="fab fa-instagram fa-3x"></i></a></li>
    </ul>
  </div>

</div>

<div class="derechos">
  <p>&copy; 2026 Restaurante-UY. Todos los derechos reservados.</p>
</div>

</footer>

<button id="chatbot-toggle" class="chatbot-toggle" type="button" aria-label="Abrir asistente">
    <i class="fas fa-comments" aria-hidden="true"></i>
    <span>Asistente</span>
</button>

<section id="chatbot-panel" class="chatbot-panel" aria-label="Asistente del sistema">
    <header class="chatbot-header">
        <h3>Asistente</h3>
        <button id="chatbot-close" class="chatbot-close" type="button" aria-label="Cerrar asistente">x</button>
    </header>

    <div id="chatbot-messages" class="chatbot-messages"></div>
    <div id="chatbot-quick" class="chatbot-quick"></div>

    <form id="chatbot-form" class="chatbot-form" autocomplete="off">
        <input id="chatbot-input" type="text" maxlength="280" placeholder="Escribe tu pregunta..." required>
        <button type="submit">Enviar</button>
    </form>
</section>

<script src="<?php echo htmlspecialchars(app_url('chatbot.js'), ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo $chatbotJsVersion; ?>"></script>

</body>
</html>