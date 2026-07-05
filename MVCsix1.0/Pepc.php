<?php

class Pepc {

    private $con;
    private $dbhost = "localhost";
    private $dbuser = "root";
    private $dbpass = "";
    private $dbname = "ProyectoMagnus";
    private $detalleTable = '';

    function __construct() {
        $this->connect_db();
    }

    public function connect_db() {
        $this->con = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);

        if(mysqli_connect_error()) {
            die("Conexión a la base de datos falló: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->con, 'utf8mb4');

        $this->detalleTable = $this->resolverTablaDetalle();
        if ($this->detalleTable === 'Pepc') {
            $this->asegurarColumnasPersonalizacion();
        }
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

    private function resolverTablaDetalle() {
        if ($this->tableExists('Pepc')) {
            return 'Pepc';
        }

        if ($this->tableExists('detalle_pedido')) {
            return 'detalle_pedido';
        }

        return '';

        $this->asegurarColumnasPersonalizacion();
    }

    private function existeColumna($tabla, $columna) {
        $tabla = mysqli_real_escape_string($this->con, (string) $tabla);
        $columna = mysqli_real_escape_string($this->con, (string) $columna);
        $sql = "SHOW COLUMNS FROM `" . $tabla . "` LIKE '" . $columna . "'";
        $res = mysqli_query($this->con, $sql);
        if (!$res) {
            return false;
        }
        return mysqli_num_rows($res) > 0;
    }

    private function asegurarColumnasPersonalizacion() {
        if (!$this->existeColumna('Pepc', 'SinIngredientes')) {
            mysqli_query($this->con, "ALTER TABLE Pepc ADD COLUMN SinIngredientes VARCHAR(255) NULL");
        }

        if (!$this->existeColumna('Pepc', 'ExtraIngredientes')) {
            mysqli_query($this->con, "ALTER TABLE Pepc ADD COLUMN ExtraIngredientes VARCHAR(255) NULL");
        }
    }

    // ✅ GUARDAR PRODUCTOS DEL PEDIDO
    public function create($IDPedido, $IDProducto, $Cantidad, $SinIngredientes = '', $ExtraIngredientes = '') {
        if ($this->detalleTable === '') {
            die("Error en detalle de pedido: no existe tabla Pepc ni detalle_pedido.");
        }

        $IDPedido = (int) $IDPedido;
        $IDProducto = (int) $IDProducto;
        $Cantidad = (int) $Cantidad;
        $sin = trim((string) $SinIngredientes);
        $extra = trim((string) $ExtraIngredientes);

        if ($this->detalleTable === 'Pepc') {
            $stmt = mysqli_prepare(
                $this->con,
                "INSERT INTO Pepc (IDPedido, IDProducto, Cantidad, SinIngredientes, ExtraIngredientes)
                 VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                die("Error en Pepc: " . mysqli_error($this->con));
            }

            mysqli_stmt_bind_param($stmt, 'iiiss', $IDPedido, $IDProducto, $Cantidad, $sin, $extra);
            $res = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if(!$res){
                die("Error en Pepc: " . mysqli_error($this->con));
            }

            return true;
        }

        $stmtPrecio = mysqli_prepare($this->con, "SELECT precio FROM productos WHERE id_producto = ? LIMIT 1");
        if (!$stmtPrecio) {
            die("Error en detalle_pedido: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmtPrecio, 'i', $IDProducto);
        mysqli_stmt_execute($stmtPrecio);
        $resPrecio = mysqli_stmt_get_result($stmtPrecio);
        $rowPrecio = $resPrecio ? mysqli_fetch_assoc($resPrecio) : null;
        mysqli_stmt_close($stmtPrecio);

        $precio = (float) ($rowPrecio['precio'] ?? 0);
        $subtotal = $precio * max(1, $Cantidad);

        $stmt = mysqli_prepare(
            $this->con,
            "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            die("Error en detalle_pedido: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmt, 'iiidd', $IDPedido, $IDProducto, $Cantidad, $precio, $subtotal);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if(!$res){
            die("Error en detalle_pedido: " . mysqli_error($this->con));
        }

        return true;
    }

    // ✅ ESTA ES LA QUE TE FALTABA (ARREGLADA)
    public function obtenerPedidos() {
        $sql = "SELECT * FROM pedidos WHERE estado = 'Pendiente' OR estado = 'Preparando'";

        $res = mysqli_query($this->con, $sql);

        if(!$res){
            die("Error en Pedido: " . mysqli_error($this->con));
        }

        $pedidos = array();

        while($pedido = mysqli_fetch_assoc($res)) {

            $IDPedido = (int) ($pedido['id_pedido'] ?? 0);

            // Traer productos de ese pedido
            if ($this->detalleTable === 'Pepc') {
                $sqlProd = "SELECT pr.nombre AS Nombre, pe.Cantidad, pe.SinIngredientes, pe.ExtraIngredientes
                            FROM Pepc pe
                            JOIN productos pr ON pe.IDProducto = pr.id_producto
                            WHERE pe.IDPedido = $IDPedido";
            } else {
                $sqlProd = "SELECT pr.nombre AS Nombre, dp.cantidad AS Cantidad, '' AS SinIngredientes, '' AS ExtraIngredientes
                            FROM detalle_pedido dp
                            JOIN productos pr ON dp.id_producto = pr.id_producto
                            WHERE dp.id_pedido = $IDPedido";
            }

            $resProd = mysqli_query($this->con, $sqlProd);

            if(!$resProd){
                die("Error en productos: " . mysqli_error($this->con));
            }

            $productos = array();

            while($prod = mysqli_fetch_assoc($resProd)) {
                $productos[] = $prod;
            }

            $pedidos[] = array(
                "IDPedido" => $IDPedido,
                "Mesa" => $pedido['id_mesa'] ?? null,
                "CI" => null,
                "Estado" => $pedido['estado'],
                "Fecha" => $pedido['fecha'],
                "Productos" => $productos
            );
        }

        return $pedidos;
    }

}
?>