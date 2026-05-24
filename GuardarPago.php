<?php

include "MVCsix1.0/Pedido.php"; // ✅ mejor incluir directo

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jsonObject = file_get_contents("php://input");
    $pedido = json_decode($jsonObject, true);

    if (
        isset($pedido['TipoPago']) &&
        isset($pedido['id_pedido']) &&
        isset($pedido['total'])
    ) {

        $tipoPago = $pedido['TipoPago'];
        $id_pedido = (int) $pedido['id_pedido'];
        $total = (float) $pedido['total'];

        // 🔥 CONEXIÓN
        $con = mysqli_connect("localhost", "root", "", "ProyectoMagnus");

        if (!$con) {
            echo json_encode(["error" => "Error de conexión"]);
            exit;
        }

        mysqli_set_charset($con, 'utf8mb4');

        $metodosPermitidos = ['Efectivo', 'Tarjeta', 'Transferencia'];
        if (!in_array($tipoPago, $metodosPermitidos, true) || $id_pedido <= 0 || $total <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Datos inválidos"]);
            exit;
        }

        mysqli_begin_transaction($con);

        try {
            // ✅ 1. GUARDAR PAGO
            $stmtPago = mysqli_prepare($con, 'INSERT INTO pagos (id_pedido, metodo_pago, monto) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmtPago, 'isd', $id_pedido, $tipoPago, $total);
            mysqli_stmt_execute($stmtPago);
            mysqli_stmt_close($stmtPago);

            // ✅ 2. ACTUALIZAR PEDIDO (entregado/facturable)
            $estadoEntregado = 'Entregado';
            $stmtPedido = mysqli_prepare($con, 'UPDATE pedidos SET estado = ? WHERE id_pedido = ?');
            mysqli_stmt_bind_param($stmtPedido, 'si', $estadoEntregado, $id_pedido);
            mysqli_stmt_execute($stmtPedido);
            mysqli_stmt_close($stmtPedido);

            // ✅ 3. LIBERAR MESA
            $estadoLibre = 'Libre';
            $stmtMesa = mysqli_prepare(
                $con,
                'UPDATE mesas SET estado = ? WHERE id_mesa = (SELECT id_mesa FROM pedidos WHERE id_pedido = ? LIMIT 1)'
            );
            mysqli_stmt_bind_param($stmtMesa, 'si', $estadoLibre, $id_pedido);
            mysqli_stmt_execute($stmtMesa);
            mysqli_stmt_close($stmtMesa);

            mysqli_commit($con);
            echo json_encode(["success" => "Pago registrado correctamente"]);
        } catch (Throwable $e) {
            mysqli_rollback($con);
            http_response_code(500);
            echo json_encode(["error" => "No se pudo registrar el pago"]);
        }

    } else {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}
?>