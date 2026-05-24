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
    }

    // ✅ GUARDAR PRODUCTOS DEL PEDIDO
    public function create($IDPedido, $IDProducto, $Cantidad) {

        $sql = "INSERT INTO Pepc (IDPedido, IDProducto, Cantidad)
                VALUES ('$IDPedido', '$IDProducto', '$Cantidad')";

        $res = mysqli_query($this->con, $sql);

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
            $sqlProd = "SELECT pr.Nombre, pe.Cantidad
                        FROM Pepc pe
                        JOIN Producto pr ON pe.IDProducto = pr.IDProducto
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