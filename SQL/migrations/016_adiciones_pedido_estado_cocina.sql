ALTER TABLE pedidos
    MODIFY estado ENUM(
        'Pendiente',
        'Preparando',
        'Pronto para entregar',
        'Entregado',
        'Cancelado',
        'ArchivadoCocina'
    ) NOT NULL DEFAULT 'Pendiente';

ALTER TABLE detalle_pedido
    ADD COLUMN IF NOT EXISTS estado_cocina ENUM(
        'Pendiente',
        'Preparando',
        'Pronto para entregar',
        'Entregado'
    ) NOT NULL DEFAULT 'Pendiente';

UPDATE detalle_pedido dp
INNER JOIN pedidos p ON p.id_pedido = dp.id_pedido
SET dp.estado_cocina = CASE
    WHEN p.estado = 'Preparando' THEN 'Preparando'
    WHEN p.estado = 'Pronto para entregar' THEN 'Pronto para entregar'
    WHEN p.estado IN ('Entregado', 'ArchivadoCocina') THEN 'Entregado'
    ELSE 'Pendiente'
END;
