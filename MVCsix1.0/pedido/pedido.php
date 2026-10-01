<?php
require_once __DIR__ . '/../../app_bootstrap.php';

app_require_login('../../Login.php', ['1', '3']);

$Usuario = $_SESSION['Usuario'] ?? null;
$Rol = $_SESSION['Rol'] ?? null;

?>

<?php
  require_once '../InterfazProducto.php';
  $pedidoCssVersion = @filemtime(__DIR__ . '/style.css') ?: time();
  $products = ListarProductos();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Lista de Productos</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="style.css?v=<?php echo $pedidoCssVersion; ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <script src="<?php echo htmlspecialchars(app_url('no-popups.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>
  
  <header>
  <?php 

if ($Rol== "Admin"){

?>
<a class="Atras" href="<?php echo htmlspecialchars(app_url('Principal.php'), ENT_QUOTES, 'UTF-8'); ?>">
<i class="fas fa-arrow-circle-left" style="margin-left: 5px;"></i>
</a> 
<h1 class="titulo-pedido">Haga su pedido</h1> 

<?php 

}

?>

<?php 

if ($Rol== "Mozo"){

?>
 <h1 class="titulo-pedido titulo-pedido-mozo">Haga su pedido</h1> 

<?php 

}

?>
  
  
    <a class="salir" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>"><b>Salir</b> <i class="fas fa-sign-out-alt" style="margin-left: 5px;"></i></a>
   
  </header>

  <div id="searchContainer">
  <i id="searchIcon" class="fas fa-search"></i>
  <input id="searchInput" type="text" placeholder="Buscar Productos...">
</div>


  
  <div class="products-carousel" aria-label="Catalogo de productos">
    <button class="carousel-arrow carousel-prev" type="button" aria-label="Productos anteriores" onclick="document.getElementById('productsContainer').scrollLeft -= document.getElementById('productsContainer').clientWidth * 0.8">&#10094;</button>
    <div class="products-container" id="productsContainer"></div>
    <button class="carousel-arrow carousel-next" type="button" aria-label="Mas productos" onclick="document.getElementById('productsContainer').scrollLeft += document.getElementById('productsContainer').clientWidth * 0.8">&#10095;</button>
  </div>
  
  <section id="pedidoSection">
  <h2><i class="fas fa-shopping-cart" style="margin-right: 5px;"></i>PEDIDO</h2>
    <div id="pedidoContainer">
      <ul id="orderList"></ul>
      <div id="orderSummary" aria-live="polite">
        <span id="orderItemsCount">0 productos</span>
        <strong id="orderTotalValue">$ 0</strong>
      </div>
      <div id="currencyRow">
        <label for="currencySelect">Moneda</label>
        <select id="currencySelect" name="currency">
          <option value="UYU" selected>Pesos uruguayos (UYU)</option>
          <option value="BRL">Reales brasileños (BRL)</option>
        </select>
      </div>
      <p id="exchangeRateHint">Cotización</p>
      <input class="mesa" type="text" id="Mesa" name="Mesa" placeholder="Numero de Mesa">
      <textarea placeholder="Ingrese una observación aquí" name="" id="observaciones" cols="30" rows="9"></textarea>
    </div>
    <button onclick="enviar()" id="sendPedido">Hacer Pedido</button>
    <p id="res" class="respuesta"></p>
  </section>
</body>
</html>
<script>
    const products = <?php echo $products; ?>;
    const cedula = <?php echo json_encode($_SESSION["CI"] ?? null); ?>;
    const csrfToken = <?php echo json_encode(App\Support\Csrf::token()); ?>;
    const contextId = <?php echo json_encode(App\Support\Auth::contextId()); ?>;
    console.log(cedula);

</script>
<script src="script.js"></script>
