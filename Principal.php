<?php

session_start();

$principalCssVersion = @filemtime(__DIR__ . '/estilos/Principal.css') ?: time();

$Usuario = $_SESSION['Usuario'];

if(!isset($Usuario)){
    header("location: /proj/Login.php");
    session_destroy();
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inicio</title>
    <link rel="stylesheet" type="text/css" href="estilos/Principal.css?v=<?php echo $principalCssVersion; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="/proj/no-popups.js"></script>
</head>

<body>

<header>
    <h1>RESTAURANTE-UY</h1> 
    <a class="salir" href='/proj/Salir.php'>
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
            <a href="/proj/EmpleadoI.php">
                <i class="fas fa-user" style="margin-right: 5px;"></i>Funcionarios
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/productos.php">
                <i class="fas fa-plus" style="margin-right: 5px;"></i>Productos
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/informes.php">
                <i class="fas fa-chart-bar" style="margin-right: 5px;"></i>Informes
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/pedidos.php">
                <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i>Pedidos
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/cocina.php">
                <i class="fas fa-utensils" style="margin-right: 5px;"></i>Cocina
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/caja.php">
                <i class="fas fa-coins" style="margin-right: 5px;"></i>Caja
            </a> 
        </div>

        <div class="Links"> 
            <a href="/proj/mesas.php">
                <i class="fas fa-chair" style="margin-right: 5px;"></i>Mesas
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

</body>
</html>