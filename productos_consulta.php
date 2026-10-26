<?php
require_once __DIR__ . '/app_bootstrap.php';

// Consulta de productos (solo lectura) para mozo y cocina; el admin los gestiona en productos.php.
app_require_login('Login.php', ['1', '3', '4']);

function pc_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pc_imagen($producto)
{
    $subida = App\Support\ProductImage::uploadedPath($producto['img'] ?? '');
    if ($subida !== null) {
        return $subida;
    }
    $catalogo = App\Support\ProductImage::catalogPath($producto['nombre'] ?? '');
    if ($catalogo !== null) {
        return $catalogo;
    }
    $normalizada = ltrim(str_replace('\\', '/', trim((string) ($producto['img'] ?? ''))), '/');
    $base = basename($normalizada);
    $candidatas = array($normalizada, 'MVCsix1.0/' . $normalizada, 'MVCsix1.0/img/' . $base, 'MVCsix1.0/files/' . $base);
    foreach ($candidatas as $candidata) {
        if ($base !== '' && strpos($candidata, '..') === false && is_file(__DIR__ . '/' . $candidata)) {
            return $candidata;
        }
    }
    return 'MVCsix1.0/img/default_comida_hq.jpg';
}

$productoController = new App\Controllers\ProductoController();
$productos = $productoController->listar();
$esCocina = (string) ($_SESSION['Rol'] ?? '') === '4';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Productos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
:root{--bg:#0d0f14;--panel:#16181f;--card:#14161d;--accent:#ff2d6f;--accent-2:#ff5c8f;--text:#f3f4f8;--muted:#a9adba;--line:rgba(255,255,255,.09);--ok:#2fbf71;--bad:#ef4444}
*{box-sizing:border-box}
body{margin:0;background:radial-gradient(1000px 400px at 10% -8%,rgba(255,45,111,.13),transparent 60%),var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,Arial,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;padding:14px 28px;background:#101218;border-bottom:1px solid var(--line)}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;border:1px solid var(--line);background:#23262f;color:var(--text);text-decoration:none;font-weight:600;font-size:.92rem}
.btn:hover{filter:brightness(1.15)}
main{max-width:1280px;margin:0 auto;padding:24px 22px 60px}
h1{margin:0 0 6px;font-size:clamp(1.6rem,3vw,2.2rem)}
.sub{color:var(--muted);margin:0 0 20px}
.badge-ro{display:inline-block;margin-left:10px;padding:3px 10px;border-radius:999px;background:rgba(59,130,246,.18);color:#93c5fd;font-size:.72rem;font-weight:700;vertical-align:middle}
.tools{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.tools input,.tools select{padding:11px 13px;border-radius:10px;border:1px solid #2b2f3a;background:#101218;color:var(--text);font:inherit;font-size:.92rem}
.tools input{flex:1;min-width:220px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.product{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
.product img{width:100%;height:150px;object-fit:cover;background:#0a0c10}
.info{padding:13px 14px 15px;display:flex;flex-direction:column;flex:1;gap:6px}
.info h3{margin:0;font-size:1rem}
.info p{margin:0;color:var(--muted);font-size:.82rem;line-height:1.35;min-height:34px}
.row{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
.price{font-weight:800}
.stock{font-size:.75rem;font-weight:700;padding:3px 10px;border-radius:999px;background:rgba(47,191,113,.16);color:#7ee2a8}
.stock.out{background:rgba(239,68,68,.16);color:#fca5a5}
.stock.off{background:rgba(169,173,186,.16);color:var(--muted)}
.empty{color:var(--muted);padding:30px;text-align:center}
</style>
</head>
<body>
<div class="top">
    <a class="btn" href="<?php echo pc_h(app_url(((string) ($_SESSION['Rol'] ?? '')) === '1' ? 'Principal.php' : 'inicio_rol.php')); ?>"><i class="fas fa-arrow-left"></i> Volver</a>
    <a class="btn" href="<?php echo pc_h(app_url('Salir.php')); ?>">Salir <i class="fas fa-sign-out-alt"></i></a>
</div>
<main>
    <h1>Productos <span class="badge-ro">Solo lectura</span></h1>
    <p class="sub">Fotos ilustrativas; la presentacion y el envase pueden variar.
    <a href="<?php echo pc_h(app_url('MVCsix1.0/img/catalogo/creditos.html')); ?>">Creditos de fotografias</a></p>
    <p class="sub"><?php echo $esCocina ? 'Consulta de la carta y el stock disponible para la cocina.' : 'Consulta de la carta, precios y disponibilidad para atender a los clientes.'; ?></p>
    <div class="tools">
        <input id="buscar" type="search" placeholder="Buscar producto...">
        <select id="filtro">
            <option value="">Todos</option>
            <option value="disp">Disponibles</option>
            <option value="out">Agotados</option>
            <option value="off">Inactivos</option>
        </select>
    </div>
    <div class="grid" id="lista">
    <?php foreach ($productos as $p) {
        $activo = ($p['estado'] ?? 'Inactivo') === 'Activo';
        $stock = (int) ($p['stock'] ?? 0);
        $estado = !$activo ? 'off' : ($stock > 0 ? 'disp' : 'out');
        $etiqueta = !$activo ? 'Inactivo' : ($stock > 0 ? 'Stock: ' . $stock : 'Agotado');
    ?>
        <article class="product" data-estado="<?php echo $estado; ?>" data-nombre="<?php echo pc_h(mb_strtolower((string) $p['nombre'], 'UTF-8')); ?>">
            <?php $imagen = pc_imagen($p); $version = filemtime(__DIR__ . '/' . $imagen); ?>
            <img src="<?php echo pc_h(app_url($imagen . '?v=' . $version)); ?>" alt="<?php echo pc_h($p['nombre']); ?>">
            <div class="info">
                <h3><?php echo pc_h($p['nombre']); ?></h3>
                <p><?php echo pc_h($p['descripcion'] ?? ''); ?></p>
                <div class="row">
                    <span class="price">$<?php echo number_format((float) $p['precio'], 2, ',', '.'); ?></span>
                    <span class="stock<?php echo $estado === 'disp' ? '' : ' ' . $estado; ?>"><?php echo pc_h($etiqueta); ?></span>
                </div>
            </div>
        </article>
    <?php } ?>
    </div>
    <div class="empty" id="vacio" style="display:none">No hay productos que coincidan.</div>
</main>
<script>
(function () {
    var buscar = document.getElementById('buscar'), filtro = document.getElementById('filtro');
    var items = document.querySelectorAll('#lista .product'), vacio = document.getElementById('vacio');
    function aplicar() {
        var q = buscar.value.trim().toLowerCase(), f = filtro.value, visibles = 0;
        items.forEach(function (el) {
            var ok = (!q || el.dataset.nombre.indexOf(q) !== -1) && (!f || el.dataset.estado === f);
            el.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        vacio.style.display = visibles ? 'none' : 'block';
    }
    buscar.addEventListener('input', aplicar);
    filtro.addEventListener('change', aplicar);
    aplicar();
})();
</script>
</body>
</html>
