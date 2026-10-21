<?php
$idPedido = (int) $pedido['id_pedido'];
$estado = (string) $pedido['estado'];
$clase = strtolower($estado);
$minutos = max(0, (int) floor((time() - strtotime($pedido['fecha'])) / 60));
$acciones = array(
    'Pendiente' => array('Preparando', 'Comenzar preparacion'),
    'Preparando' => array('Entregado', 'Marcar entregado'),
    'Entregado' => array('Pendiente', 'Volver a pendientes')
);
?>
<article class="order <?php echo h($clase); ?>" data-order-id="<?php echo $idPedido; ?>" data-state="<?php echo h($estado); ?>">
    <div class="order-head"><h3>Pedido #<?php echo $idPedido; ?></h3><span class="badge <?php echo h($clase); ?>"><?php echo h($estado); ?></span></div>
    <p class="mesa">Mesa <?php echo $pedido['mesa_numero'] !== null ? (int) $pedido['mesa_numero'] : 'sin asignar'; ?></p>
    <p class="order-meta"><time datetime="<?php echo h(str_replace(' ', 'T', $pedido['fecha'])); ?>"><?php echo h($pedido['fecha']); ?></time><br>
        <span class="<?php echo $minutos >= 20 && $estado !== 'Entregado' ? 'waiting' : ''; ?>">Hace <?php echo $minutos; ?> min · desde la creacion</span></p>
    <ul class="products">
    <?php foreach ($pedido['productos'] as $producto) { ?>
        <li><div><strong class="quantity"><?php echo (int) $producto['cantidad']; ?> x</strong> <?php echo h($producto['nombre']); ?></div>
            <?php if (!empty($producto['sin_ingredientes'])) { ?><p class="customization">Sin: <?php echo h($producto['sin_ingredientes']); ?></p><?php } ?>
            <?php if (!empty($producto['extra_ingredientes'])) { ?><p class="customization">Agregar: <?php echo h($producto['extra_ingredientes']); ?></p><?php } ?>
        </li>
    <?php } ?>
    <?php if (!$pedido['productos']) { ?><li class="order-meta">Sin productos registrados.</li><?php } ?>
    </ul>
    <?php if (trim((string) $pedido['observaciones']) !== '') { ?>
    <div class="kitchen-notes"><strong>Notas para cocina</strong><p><?php echo nl2br(h($pedido['observaciones'])); ?></p></div>
    <?php } ?>
    <p class="order-meta">Total del pedido <strong>$<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></strong></p>
    <?php if (!$soloLectura) { ?>
    <form method="post" action="<?php echo h(app_url('Cocina2.php')); ?>" class="kitchen-action">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_pedido" value="<?php echo $idPedido; ?>">
        <input type="hidden" name="estado_anterior" value="<?php echo h($estado); ?>">
        <button class="action-btn<?php echo $estado === 'Entregado' ? ' secondary' : ''; ?>" name="estado" value="<?php echo $acciones[$estado][0]; ?>" type="submit"><?php echo $acciones[$estado][1]; ?></button>
        <button class="archive-btn" name="estado" value="ArchivadoCocina" type="submit">Retirar de cocina</button>
    </form>
    <p class="order-note">Retirar lo oculta del panel; no elimina el pedido ni registra un pago.</p>
    <?php } else { ?>
    <p class="order-note">Solo lectura · los estados los actualiza Cocina.</p>
    <?php } ?>
</article>
