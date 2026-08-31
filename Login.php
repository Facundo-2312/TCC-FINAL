<?php
require_once __DIR__ . '/app_bootstrap.php';
app_start_session();

$flash = app_get_flash();
$loginCssVersion = @filemtime(__DIR__ . '/estilos/login.css') ?: time();
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login PHP</title>
    <link rel="icon" type="image/jpeg" href="<?php echo htmlspecialchars(app_url('img/logonuevo.jpeg'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" type="text/css" href="estilos/login.css?v=<?php echo $loginCssVersion; ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
  </head>
  <body>

  <header> 

  <h1>Bienvenido a <b>RESTAURANTE-UY</b></h1>
    
 </header>

 <div class="conteiner">

  <div class="login-container">
  <?php if (!empty($flash) && !empty($flash['message'])) { ?>
    <div class="flash flash-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php } ?>
  <img src="img/logonuevo.jpeg" style="height: 95px; width: 95px; border-radius: 100px;">
            <h2>INICIAR SESION</h2>
      
	<form method="post" action="interfazlog.php">
		<?php echo csrf_field(); ?>

		<p>Usuario <input type="text"  name="Usuario" required placeholder="Digite su nombre" /></p>

   
		<p>Contraseña<input type="password"  name="Pass" required placeholder="Digite su Contraseña"  /></p>
  
  
    <p><input type="submit" value="Ingresar" /></p>
    <input id="createID" name="crud" type="hidden" value="3">
	</form>
  </div>
	
</div>					


<footer class="foot">

        
<div class="footer-content">
  <div class="footer-section">
    <h3>Contacto</h3>
    <p><i class="fas fa-map-marker-alt"></i> Dirección: Av.Brasil 1083</p>
    <p><i class="fas fa-envelope"></i> <a href="mailto:info@restaurante-uy.com">info@restaurante-uy.com</a></p>

    <p><i class="fas fa-phone"></i> +1 234 567 890</p>
  </div>
  <div class="footer-section">
  <h3>Síguenos</h3>
  <ul class="social-icons">
    <li><a href="enlace-a-tu-pagina-de-Facebook"><i class="fab fa-facebook fa-2x"></i></a></li>
    <li><a href="enlace-a-tu-pagina-de-X" aria-label="X"><span class="x-brand x-2x">X</span></a></li>
    <li><a href="enlace-a-tu-pagina-de-Instagram"><i class="fab fa-instagram fa-2x"></i></a></li>
  </ul>
</div>
</div>
<div class="derechos">
  <p>&copy; 2026 RESTAURANTE-UY. Todos los derechos reservados.</p>
</div>


</footer>


  </body>
</html>