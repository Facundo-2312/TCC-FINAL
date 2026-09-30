<?php
require_once __DIR__ . '/app_bootstrap.php';
app_require_login('Login.php', ['2', '3', '4']);

$rol = (string) ($_SESSION['Rol'] ?? '');
$config = array(
    '2' => array('titulo' => 'Inicio de Caja', 'modulos' => array(array('caja.php', 'Panel de Caja', 'fa-coins'), array('mesas.php', 'Mesas', 'fa-chair'), array('centro_reservas.php', 'Reservas', 'fa-calendar-check'))),
    '3' => array('titulo' => 'Inicio de Mozo', 'modulos' => array(array('pedidos.php', 'Pedidos', 'fa-shopping-cart'), array('cliente.php', 'Cliente', 'fa-user-plus'), array('mesas.php', 'Mesas', 'fa-chair'))),
    '4' => array('titulo' => 'Inicio de Cocina', 'modulos' => array(array('cocina.php', 'Cocina', 'fa-utensils')))
);
$inicio = $config[$rol];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo htmlspecialchars($inicio['titulo'], ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="estilos/Principal.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<header><h1><?php echo htmlspecialchars($inicio['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1><a class="salir" href="<?php echo htmlspecialchars(app_url('Salir.php'), ENT_QUOTES, 'UTF-8'); ?>">Salir</a></header>
<main id="contenido"><div class="botones">
<?php foreach ($inicio['modulos'] as $modulo) { ?><div class="Links"><a href="<?php echo htmlspecialchars(app_url($modulo[0]), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas <?php echo htmlspecialchars($modulo[2], ENT_QUOTES, 'UTF-8'); ?>" style="margin-right:5px"></i><?php echo htmlspecialchars($modulo[1], ENT_QUOTES, 'UTF-8'); ?></a></div><?php } ?>
</div></main>
</body>
</html>