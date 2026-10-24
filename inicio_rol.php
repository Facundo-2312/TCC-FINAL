<?php
require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php', ['1', '2', '3', '4']);

$rol = (string) ($_SESSION['Rol'] ?? '');
if ($rol === '1') {
    app_redirect('Principal.php');
}
$config = array(
    '2' => array(
        'titulo' => 'Caja',
        'subtitulo' => 'Cobros, mesas y reservas del salón.',
        'icono' => 'fa-coins',
        'modulos' => array(
            array('CAJA.php', 'Caja', 'Cobrar pedidos y registrar pagos', 'fa-coins'),
            array('mesas.php', 'Mesas', 'Estado del salón en tiempo real', 'fa-chair'),
            array('centro_reservas.php', 'Reservas', 'Reservas del día y resumen', 'fa-calendar-check')
        )
    ),
    '3' => array(
        'titulo' => 'Mozo',
        'subtitulo' => 'Toma pedidos, consulta la carta y sigue lo que prepara la cocina.',
        'icono' => 'fa-concierge-bell',
        'modulos' => array(
            array('pedidos.php', 'Pedidos', 'Tomar y consultar pedidos de las mesas', 'fa-shopping-cart'),
            array('Cocina2.php', 'Cocina', 'Solo lectura: lo que emite la cocina', 'fa-utensils'),
            array('productos_consulta.php', 'Productos', 'Carta, precios y stock (solo lectura)', 'fa-hamburger'),
            array('mesas.php', 'Mesas', 'Estado de las mesas y reservas', 'fa-chair')
        )
    ),
    '4' => array(
        'titulo' => 'Cocina',
        'subtitulo' => 'Gestiona la preparación de los pedidos y consulta la carta.',
        'icono' => 'fa-utensils',
        'modulos' => array(
            array('Cocina2.php', 'Pedidos de cocina', 'Pendientes, en preparación y entregados', 'fa-fire'),
            array('productos_consulta.php', 'Productos', 'Carta y stock disponible (solo lectura)', 'fa-hamburger')
        )
    )
);
$inicio = $config[$rol];

$resumen = null;
if ($rol === '3' || $rol === '4') {
    $vista = (new App\Controllers\PedidoController())->listarCocina();
    $resumen = $vista['resumen'];
}

function ir_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo ir_h($inicio['titulo']); ?> · Restaurante-UY</title>
<link rel="icon" type="image/jpeg" href="<?php echo ir_h(app_url('img/logonuevo.jpeg')); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
:root{--bg:#0d0f14;--panel:#16181f;--panel-2:#1d2029;--accent:#ff2d6f;--accent-2:#ff5c8f;--text:#f3f4f8;--muted:#a9adba;--line:rgba(255,255,255,.09);--warn:#f5a623;--info:#3b82f6;--ok:#2fbf71}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:radial-gradient(1100px 450px at 10% -8%,rgba(255,45,111,.14),transparent 60%),radial-gradient(800px 400px at 105% 0,rgba(59,130,246,.10),transparent 55%),var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,Arial,sans-serif}
.top{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 28px;background:#101218;border-bottom:1px solid var(--line)}
.brand{display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:.3px}
.brand img{width:38px;height:38px;border-radius:10px;object-fit:cover}
.user{color:var(--muted);font-size:.9rem}
.user b{color:var(--accent-2)}
.btn{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:10px;border:1px solid var(--line);background:#23262f;color:var(--text);text-decoration:none;font-weight:600;font-size:.9rem}
.btn:hover{filter:brightness(1.2)}
main{max-width:1100px;margin:0 auto;padding:34px 22px 60px}
.hero{display:flex;align-items:center;gap:18px;margin-bottom:26px}
.hero .ico{width:64px;height:64px;border-radius:18px;display:grid;place-items:center;font-size:1.7rem;background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:0 8px 22px rgba(255,45,111,.35)}
.hero h1{margin:0;font-size:clamp(1.6rem,3vw,2.3rem)}
.hero p{margin:4px 0 0;color:var(--muted)}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:26px}
.stat{background:linear-gradient(180deg,var(--panel),var(--panel-2));border:1px solid var(--line);border-radius:14px;padding:16px 18px;border-left:4px solid var(--warn)}
.stat.prep{border-left-color:var(--info)}.stat.ok{border-left-color:var(--ok)}
.stat span{display:block;color:var(--muted);font-size:.85rem}
.stat strong{font-size:1.9rem}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.card{display:flex;flex-direction:column;gap:10px;padding:22px;border-radius:16px;text-decoration:none;color:var(--text);background:linear-gradient(180deg,var(--panel),var(--panel-2));border:1px solid var(--line);transition:transform .18s,border-color .18s,box-shadow .18s}
.card:hover{transform:translateY(-4px);border-color:rgba(255,45,111,.5);box-shadow:0 14px 30px rgba(0,0,0,.45)}
.card i{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;font-size:1.2rem;background:rgba(255,45,111,.15);color:var(--accent-2)}
.card h2{margin:4px 0 0;font-size:1.1rem}
.card p{margin:0;color:var(--muted);font-size:.88rem;line-height:1.4}
@media(max-width:700px){.stats{grid-template-columns:1fr}.top{padding:12px 16px}.user{display:none}}
</style>
</head>
<body>
<div class="top">
    <div class="brand"><img src="<?php echo ir_h(app_url('img/logonuevo.jpeg')); ?>" alt=""><span>RESTAURANTE-UY</span></div>
    <span class="user">Sesión: <b><?php echo ir_h($_SESSION['Usuario'] ?? ''); ?></b></span>
    <a class="btn" href="<?php echo ir_h(app_url('Salir.php')); ?>">Salir <i class="fas fa-sign-out-alt"></i></a>
</div>
<main>
    <section class="hero">
        <div class="ico"><i class="fas <?php echo ir_h($inicio['icono']); ?>"></i></div>
        <div>
            <h1>Panel de <?php echo ir_h($inicio['titulo']); ?></h1>
            <p><?php echo ir_h($inicio['subtitulo']); ?></p>
        </div>
    </section>
    <?php if ($resumen) { ?>
    <section class="stats" aria-label="Estado de la cocina">
        <div class="stat"><span>Pendientes</span><strong><?php echo (int) $resumen['Pendiente']; ?></strong></div>
        <div class="stat prep"><span>En preparación</span><strong><?php echo (int) $resumen['Preparando']; ?></strong></div>
        <div class="stat ok"><span>Entregados</span><strong><?php echo (int) $resumen['Entregado']; ?></strong></div>
    </section>
    <?php } ?>
    <section class="cards">
        <?php foreach ($inicio['modulos'] as $modulo) { ?>
        <a class="card" href="<?php echo ir_h(app_url($modulo[0])); ?>">
            <i class="fas <?php echo ir_h($modulo[3]); ?>"></i>
            <h2><?php echo ir_h($modulo[1]); ?></h2>
            <p><?php echo ir_h($modulo[2]); ?></p>
        </a>
        <?php } ?>
    </section>
</main>
</body>
</html>
