<?php

class Pedido {

    private $con;
    private $lastError = '';
    private $lastInsertId = 0;

    private $dbhost = "localhost";
    private $dbuser = "root";
    private $dbpass = "";
    private $dbname = "ProyectoMagnus";

    private $pedidoTable = '';
    private $pedidoIdColumn = '';

    function __construct() {
        $this->connect_db();
    }

    public function connect_db() {
        $this->con = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);
        if (mysqli_connect_error()) {
            die("Conexion a la base de datos fallo " . mysqli_connect_error() . mysqli_connect_errno());
        }

        mysqli_set_charset($this->con, 'utf8mb4');
        $this->resolverTablaPedidos();
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function tableExists($tableName) {
        $tableName = mysqli_real_escape_string($this->con, (string) $tableName);
        $sql = "SHOW TABLES LIKE '" . $tableName . "'";
        $res = mysqli_query($this->con, $sql);
        if (!$res) {
            return false;
        }

        return mysqli_num_rows($res) > 0;
    }

    private function resolverTablaPedidos() {
        if ($this->tableExists('Pedido')) {
            $this->pedidoTable = 'Pedido';
            $this->pedidoIdColumn = 'IDPedido';
            return;
        }

        if ($this->tableExists('pedidos')) {
            $this->pedidoTable = 'pedidos';
            $this->pedidoIdColumn = 'id_pedido';
            return;
        }

        $this->lastError = 'No existe tabla de pedidos (Pedido/pedidos) en la base actual.';
    }

    private function resolverIdUsuarioPorCedula($cedula) {
        $cedula = (int) $cedula;

        $stmt = mysqli_prepare(
            $this->con,
            "SELECT u.id_usuario
             FROM usuarios u
             INNER JOIN empleado e ON e.Usuario = u.usuario
             WHERE e.CI = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'i', $cedula);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return (int) ($row['id_usuario'] ?? 0);
    }

    public function create($Observaciones, $cedula, $Mesa) {
        $this->lastError = '';
        $this->lastInsertId = 0;

        $cedula = (int) $cedula;
        $Mesa = (int) $Mesa;
        $obs = (string) $Observaciones;

        if ($Mesa <= 0) {
            $this->lastError = 'La mesa es obligatoria y debe ser mayor a 0.';
            return false;
        }

        if ($cedula <= 0) {
            $this->lastError = 'No se pudo identificar el mozo (CI). Vuelve a iniciar sesion.';
            return false;
        }

        if ($this->pedidoTable === '') {
            $this->resolverTablaPedidos();
            if ($this->pedidoTable === '') {
                if ($this->lastError === '') {
                    $this->lastError = 'No existe tabla de pedidos (Pedido/pedidos) en la base actual.';
                }
                return false;
            }
        }

        if ($this->pedidoTable === 'Pedido') {
            $stmt = mysqli_prepare(
                $this->con,
                "INSERT INTO `Pedido` (Observaciones, estado, CI, Mesa, Fecha) VALUES (?, 1, ?, ?, NOW())"
            );

            if (!$stmt) {
                $this->lastError = 'No se pudo preparar el pedido: ' . mysqli_error($this->con);
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'sii', $obs, $cedula, $Mesa);
            $ok = mysqli_stmt_execute($stmt);
            if (!$ok) {
                $this->lastError = 'No se pudo guardar el pedido: ' . mysqli_error($this->con);
            }
            $this->lastInsertId = (int) mysqli_insert_id($this->con);
            mysqli_stmt_close($stmt);

            return (bool) $ok;
        }

        $idUsuario = $this->resolverIdUsuarioPorCedula($cedula);
        if ($idUsuario <= 0) {
            $this->lastError = 'No se pudo mapear la CI del mozo a un usuario del sistema.';
            return false;
        }

        $stmt = mysqli_prepare(
            $this->con,
            "INSERT INTO `pedidos` (id_mesa, id_usuario, fecha, estado, total) VALUES (?, ?, NOW(), 'Pendiente', 0)"
        );

        if (!$stmt) {
            $this->lastError = 'No se pudo preparar el pedido: ' . mysqli_error($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ii', $Mesa, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $this->lastError = 'No se pudo guardar el pedido: ' . mysqli_error($this->con);
        }
        $this->lastInsertId = (int) mysqli_insert_id($this->con);
        mysqli_stmt_close($stmt);

        return (bool) $ok;
    }

    public function read() {
        if ($this->pedidoTable === '') {
            $this->resolverTablaPedidos();
        }

        if ($this->pedidoTable === 'Pedido') {
            $sql = "SELECT * FROM Pedido WHERE estado = 4";
        } else {
            $sql = "SELECT * FROM pedidos WHERE estado = 'Entregado'";
        }

        $res = mysqli_query($this->con, $sql);
        if (!$res) {
            die("Error SQL: " . mysqli_error($this->con));
        }

        $datos = mysqli_fetch_all($res, MYSQLI_ASSOC);
        return json_encode($datos);
    }

    public function TraerID() {
        if ($this->lastInsertId > 0) {
            return $this->lastInsertId;
        }

        if ($this->pedidoTable === '') {
            $this->resolverTablaPedidos();
        }

        if ($this->pedidoTable === 'Pedido') {
            $sql = "SELECT MAX(IDPedido) AS id FROM Pedido";
        } else {
            $sql = "SELECT MAX(id_pedido) AS id FROM pedidos";
        }

        $res = mysqli_query($this->con, $sql);
        if (!$res) {
            return 0;
        }

        $row = mysqli_fetch_assoc($res);
        return (int) ($row['id'] ?? 0);
    }

    public function Traercantidad() {
        if ($this->pedidoTable === 'Pedido') {
            $sql = "SELECT count(*) as cantidad_mesas_atendidas FROM Pedido WHERE CI = 13465879";
        } else {
            $sql = "SELECT count(*) as cantidad_mesas_atendidas FROM pedidos";
        }

        return mysqli_query($this->con, $sql);
    }

    public function update($Observacion) {
        $this->lastError = 'Metodo update no implementado en esta version.';
        return false;
    }

    public function Facturar($IDPedido, $TipoPago, $total) {
        if ($this->pedidoTable === 'Pedido') {
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE Pedido SET TipoPago = ?, estado = 4, total = ? WHERE IDPedido = ?"
            );

            if (!$stmt) {
                return false;
            }

            $IDPedido = (int) $IDPedido;
            $total = (float) $total;
            mysqli_stmt_bind_param($stmt, 'sdi', $TipoPago, $total, $IDPedido);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return (bool) $ok;
        }

        $estado = 'Entregado';
        $stmt = mysqli_prepare(
            $this->con,
            "UPDATE pedidos SET estado = ?, total = ? WHERE id_pedido = ?"
        );

        if (!$stmt) {
            return false;
        }

        $IDPedido = (int) $IDPedido;
        $total = (float) $total;
        mysqli_stmt_bind_param($stmt, 'sdi', $estado, $total, $IDPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool) $ok;
    }

    public function delete($IDPedido) {
        if ($this->pedidoTable === 'Pedido') {
            $stmt = mysqli_prepare($this->con, "DELETE FROM Pedido WHERE IDPedido = ?");
        } else {
            $stmt = mysqli_prepare($this->con, "DELETE FROM pedidos WHERE id_pedido = ?");
        }

        if (!$stmt) {
            return false;
        }

        $IDPedido = (int) $IDPedido;
        mysqli_stmt_bind_param($stmt, 'i', $IDPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool) $ok;
    }

    public function BuscarPedido($IDPedido) {
        if ($this->pedidoTable === 'Pedido') {
            $stmt = mysqli_prepare($this->con, "SELECT * FROM Pedido WHERE IDPedido = ?");
        } else {
            $stmt = mysqli_prepare($this->con, "SELECT * FROM pedidos WHERE id_pedido = ?");
        }

        if (!$stmt) {
            return null;
        }

        $IDPedido = (int) $IDPedido;
        mysqli_stmt_bind_param($stmt, 'i', $IDPedido);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_object($res) : null;
        mysqli_stmt_close($stmt);

        return $row;
    }

    public function actualizarEstado($idPedido, $estado) {
        if ($this->pedidoTable === 'Pedido') {
            $stmt = mysqli_prepare($this->con, "UPDATE Pedido SET estado = ? WHERE IDPedido = ?");
        } else {
            $stmt = mysqli_prepare($this->con, "UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        }

        if (!$stmt) {
            return false;
        }

        $idPedido = (int) $idPedido;
        $estado = (string) $estado;
        mysqli_stmt_bind_param($stmt, 'si', $estado, $idPedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool) $ok;
    }
}

?>