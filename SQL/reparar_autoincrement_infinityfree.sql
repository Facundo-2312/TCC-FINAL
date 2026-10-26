-- Para una importacion que perdio AUTO_INCREMENT. Respaldar la base antes de ejecutar.
-- Compatible con install_infinityfree.sql: conserva IDs existentes, incluso el ID 0,
-- y no elimina filas ni reinicia contadores. No ejecutar sobre otro esquema.
SET @qa_sql_mode_anterior = @@SESSION.sql_mode;
SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'NO_AUTO_VALUE_ON_ZERO');

ALTER TABLE roles MODIFY id_rol INT NOT NULL AUTO_INCREMENT;
ALTER TABLE usuarios MODIFY id_usuario INT NOT NULL AUTO_INCREMENT;
ALTER TABLE categorias MODIFY id_categoria INT NOT NULL AUTO_INCREMENT;
ALTER TABLE productos MODIFY id_producto INT NOT NULL AUTO_INCREMENT;
ALTER TABLE mesas MODIFY id_mesa INT NOT NULL AUTO_INCREMENT;
ALTER TABLE mesas_historial MODIFY id_historial INT NOT NULL AUTO_INCREMENT;
ALTER TABLE reservas MODIFY id_reserva INT NOT NULL AUTO_INCREMENT;
ALTER TABLE caja MODIFY id_caja INT NOT NULL AUTO_INCREMENT;
ALTER TABLE pedidos MODIFY id_pedido INT NOT NULL AUTO_INCREMENT;
ALTER TABLE detalle_pedido MODIFY id_detalle INT NOT NULL AUTO_INCREMENT;
ALTER TABLE pagos MODIFY id_pago INT NOT NULL AUTO_INCREMENT;
ALTER TABLE movimientos_caja MODIFY id_movimiento INT NOT NULL AUTO_INCREMENT;
ALTER TABLE intentos_login MODIFY id INT NOT NULL AUTO_INCREMENT;

SET SESSION sql_mode = @qa_sql_mode_anterior;

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND EXTRA LIKE '%auto_increment%'
ORDER BY TABLE_NAME;
