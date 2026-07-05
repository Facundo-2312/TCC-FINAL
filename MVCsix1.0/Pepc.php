<?php

class Pepc {

    private $con;
    private $dbhost = "localhost";
    private $dbuser = "root";
    private $dbpass = "";
    private $dbname = "ProyectoMagnus";

    function __construct() {
        $this->connect_db();
    }

    public function connect_db() {
        $this->con = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);

        if(mysqli_connect_error()) {
            die("Conexión a la base de datos falló: " . mysqli_connect_error());
        }

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
        $stmt = mysqli_prepare(
            $this->con,
            "INSERT INTO Pepc (IDPedido, IDProducto, Cantidad, SinIngredientes, ExtraIngredientes)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            die("Error en Pepc: " . mysqli_error($this->con));
        }

        $sin = trim((string) $SinIngredientes);
        $extra = trim((string) $ExtraIngredientes);
        mysqli_stmt_bind_param($stmt, 'iiiss', $IDPedido, $IDProducto, $Cantidad, $sin, $extra);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if(!$res){
            die("Error en Pepc: " . mysqli_error($this->con));
        }

        return true;
    }

    // ✅ ESTA ES LA QUE TE FALTABA (ARREGLADA)
    public function obtenerPedidos() {

        // Traer pedidos activos (estado 1 = creado / 2 = cocina)
        $sql = "SELECT * FROM pedidos WHERE estado = 1 OR estado = 2";

        $res = mysqli_query($this->con, $sql);

        if(!$res){
            die("Error en Pedido: " . mysqli_error($this->con));
        }

        $pedidos = array();

        while($pedido = mysqli_fetch_assoc($res)) {

            $IDPedido = $pedido['IDPedido'];

            // Traer productos de ese pedido
            $sqlProd = "SELECT pr.Nombre, pe.Cantidad, pe.SinIngredientes, pe.ExtraIngredientes
                        FROM Pepc pe
                        JOIN productos pr ON pe.IDProducto = pr.id_producto
                        WHERE pe.IDPedido = $IDPedido";

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
                "Mesa" => $pedido['Mesa'],
                "CI" => $pedido['CI'],
                "Estado" => $pedido['estado'],
                "Fecha" => $pedido['Fecha'],
                "Productos" => $productos
            );
        }

        return $pedidos;
    }

}
?>