CREATE TABLE IF NOT EXISTS solicitudes_cobro (
    id_pedido INT NOT NULL PRIMARY KEY,
    metodo_pago ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL,
    solicitada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_solicitudes_cobro_pedido FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
