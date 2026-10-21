<?php if (!$ultimosPagos) { ?>
<p class="empty-state">Todavia no hay pagos confirmados hoy.</p>
<?php } else { ?>
<table>
    <thead><tr><th scope="col">Operacion</th><th scope="col">Pedido / Mesa</th><th scope="col">Fecha y hora</th><th scope="col">Medio de pago</th><th scope="col">Monto</th><th scope="col">Estado</th></tr></thead>
    <tbody>
    <?php foreach ($ultimosPagos as $pago) { ?>
        <tr>
            <td>#<?php echo (int) $pago['id_pago']; ?></td>
            <td>Pedido #<?php echo (int) $pago['id_pedido']; ?> · Mesa <?php echo $pago['mesa_numero'] !== null ? (int) $pago['mesa_numero'] : '-'; ?></td>
            <td><?php echo h($pago['fecha']); ?></td>
            <td><?php echo h($pago['metodo_pago']); ?></td>
            <td><strong>$<?php echo number_format((float) $pago['monto'], 2, ',', '.'); ?></strong></td>
            <td class="confirmed">Confirmado</td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
