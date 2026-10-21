<?php foreach ($pedidosCliente as $pedido) { ?>
<article class="tracking-item">
    <h3>Pedido #<?php echo (int) $pedido['id_pedido']; ?></h3>
    <ul>
        <?php foreach ($pedido['productos'] as $producto) { ?>
        <li><?php echo (int) $producto['cantidad']; ?> x <?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>:
            $<?php echo number_format((float) $producto['subtotal'], 2, ',', '.'); ?></li>
        <?php } ?>
    </ul>
    <strong>Total: $<?php echo number_format((float) $pedido['total'], 2, ',', '.'); ?></strong>
    <?php if ($pedido['pagado']) { ?>
        <p>Pagado - <?php echo htmlspecialchars($pedido['pago_metodo'], ENT_QUOTES, 'UTF-8'); ?>. Confirmado por Caja.</p>
    <?php } elseif (in_array($pedido['estado'], array('Entregado', 'ArchivadoCocina'), true)) { ?>
        <?php if ($pedido['cobro_metodo']) { ?>
        <p>Cobro solicitado: <?php echo htmlspecialchars($pedido['cobro_metodo'], ENT_QUOTES, 'UTF-8'); ?>.
            Pendiente de confirmacion de Caja. Todavia no esta pagado.</p>
        <?php } else { ?>
        <p>Pendiente de pago.</p>
        <?php } ?>
        <form method="post" action="<?php echo htmlspecialchars(app_url('cliente.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id_pedido" value="<?php echo (int) $pedido['id_pedido']; ?>">
            <label for="pago-metodo-<?php echo (int) $pedido['id_pedido']; ?>">Medio de pago</label>
            <select id="pago-metodo-<?php echo (int) $pedido['id_pedido']; ?>" name="metodo_pago" required>
                <?php foreach (App\Services\PagoService::METODOS_PERMITIDOS as $metodo) { ?>
                <option value="<?php echo $metodo; ?>"<?php echo $pedido['cobro_metodo'] === $metodo ? ' selected' : ''; ?>><?php echo $metodo; ?></option>
                <?php } ?>
            </select>
            <button class="btn primary" style="margin-top:12px" type="submit" name="solicitar_cobro">
                <?php echo $pedido['cobro_metodo'] ? 'Actualizar solicitud' : 'Solicitar cobro'; ?>
            </button>
        </form>
    <?php } elseif ($pedido['estado'] === 'Cancelado') { ?>
        <p>Pedido cancelado. No disponible para cobro.</p>
    <?php } else { ?>
        <p>Podras solicitar el cobro cuando el pedido este entregado.</p>
    <?php } ?>
</article>
<?php } ?>
<?php if (!$pedidosCliente) { ?>
<p class="empty">Todavia no tienes pedidos realizados.</p>
<?php } ?>
