<?php $idPedido = (int) $pedido['id_pedido']; ?>
<article class="order<?php echo $pedido['cobro_metodo'] ? ' requested' : ''; ?>" data-pedido="<?php echo $idPedido; ?>">
    <div class="order-head">
        <h3>Pedido #<?php echo $idPedido; ?></h3>
        <span class="badge"><?php echo $pedido['cobro_metodo'] ? 'Cobro solicitado' : 'Por cobrar'; ?></span>
    </div>
    <p class="order-meta">Mesa <?php echo $pedido['mesa_numero'] !== null ? (int) $pedido['mesa_numero'] : 'sin asignar'; ?> · <?php echo h($pedido['fecha']); ?></p>
    <p class="solicitud-cobro" role="status"><?php echo $pedido['cobro_metodo'] ? 'Cliente solicita cobro: ' . h($pedido['cobro_metodo']) : 'Sin solicitud del cliente'; ?></p>
    <ul>
        <?php foreach ($detallePorPedido[$idPedido] as $d) { ?>
        <li><span><?php echo (int) $d['cantidad']; ?> x <?php echo h($d['nombre']); ?></span><strong>$<?php echo number_format((float) $d['subtotal'], 2, ',', '.'); ?></strong></li>
        <?php } ?>
    </ul>
    <div class="order-head"><strong>Total del pedido</strong><strong class="pedido-total">$<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></strong></div>
    <form method="post" action="<?php echo h(app_url('CAJA.php')); ?>" class="payment-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id_pedido" value="<?php echo $idPedido; ?>">
        <label for="metodo-<?php echo $idPedido; ?>">Medio de pago
            <select id="metodo-<?php echo $idPedido; ?>" name="metodo_pago" required>
                <option value="">Selecciona un medio</option>
                <?php foreach (App\Services\PagoService::METODOS_PERMITIDOS as $medio) { ?>
                <option value="<?php echo $medio; ?>"<?php echo $pedido['cobro_metodo'] === $medio ? ' selected' : ''; ?>><?php echo $medio; ?></option>
                <?php } ?>
            </select>
        </label>
        <label class="monto-cobro" for="monto-cobro-<?php echo $idPedido; ?>">Monto a cobrar
            <input id="monto-cobro-<?php echo $idPedido; ?>" class="monto-total" type="text"
                value="$<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?>" readonly>
        </label>
        <button class="facturar" type="submit" name="facturar">Confirmar pago recibido</button>
    </form>
    <p class="order-note">Total automatico, sin cargos adicionales.</p>
</article>
