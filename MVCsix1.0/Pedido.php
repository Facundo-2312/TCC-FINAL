<?php

	class Pedido{
		
		//FUNCION DE CONEXION 
		private $dbhost="localhost";
		private $dbuser="root";
		private $dbpass="";
		private $dbname="ProyectoMagnus";
		
		function __construct(){
			$this->connect_db();
		}
		public function connect_db(){
			$this->con = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);
			if(mysqli_connect_error()){
				die("Conexión a la base de datos falló " . mysqli_connect_error() . mysqli_connect_errno());
			}
		}
		
		//*********************************************************************CRUD
		

		//FUNCION CREATE
		public function create($Observaciones,$cedula, $Mesa){
			$sql = "INSERT INTO `Pedido` (Observaciones, estado, CI, Mesa, Fecha )
			VALUES ('$Observaciones', 1, '$cedula', '$Mesa', now() )";
			$res = mysqli_query($this->con, $sql);
			if($res){

				
				return true;
			}else{
			return false;
		}
		}	

		

		//FUNCION READ O SELECT		
		public function read(){
			$sql = "SELECT * FROM Pedido where estado=4";
			$res = mysqli_query($this->con, $sql);
			if(!$res){
		    die("Error SQL: " . mysqli_error($this->con));
		}

			$datos = mysqli_fetch_all($res, MYSQLI_ASSOC);
			$json = json_encode($datos);
			return $json;
		}
			public function TraerID(){
			$sql = "SELECT MAX(IDPedido) AS id FROM Pedido";
			$res = mysqli_query($this->con, $sql);
			while ($row=mysqli_fetch_object($res)){
			$id=$row->id;
			return $id;			
			}
			
		}

		public function Traercantidad (){
			$sql = "SELECT count(*) as cantidad_mesas_atendidas from Pedido where CI= 13465879;";
			$res = mysqli_query($this->con, $sql);
			return $res;
		}
			
		
		//FUNCION UPDATE O ACTUALIZAR
		
		public function update($Observacion){
      /* echo "hola mundo"; die();*/
			$sql = "UPDATE Pedido SET Observacion = '$Observacion'WHERE IDPedido=$IDPedido";
			$res = mysqli_query($this->con, $sql);
			if($res){
				return true;
			}else{
				return false;
			}
		}

		//FUNCION UPDATe para la factura
		public function Facturar($IDPedido, $TipoPago, $total){
			$sql = "UPDATE Pedido SET TipoPago = '$TipoPago',  estado= 4, total='$total' WHERE IDPedido=$IDPedido";
			echo $sql;
			$res = mysqli_query($this->con, $sql);
			if($res){
				return true;
			}else{
				return false;
			}
		}
		
		
		//FUNCION DELETE O ELIMINAR
		public function delete($IDPedido){
			$sql = "DELETE FROM Pedido WHERE IDPedido=$IDPedido";
			$res = mysqli_query($this->con, $sql);
			if($res){
			return true;
			}else{
			return false;
			}
		}

		public function BuscarPedido($IDPedido){
			$sql = "SELECT * FROM Pedido where IDPedido=$IDPedido";
			$res = mysqli_query($this->con, $sql);
			$return = mysqli_fetch_object($res );
			return $return ;
		}


		public function actualizarEstado($idPedido, $estado){

			$sql = "UPDATE pedido SET estado = '$estado' WHERE IDPedido=$idPedido";

    		$res = mysqli_query($this->con, $sql);

			return $res;

		}
	
				
}

?>
