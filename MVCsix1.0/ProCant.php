<?php

class Pepc{
	
		public $ID;
		public $Cant;
		public function getID()
		{
			return $this->ID;
		}
		public function getCat()
		{
			return $this->Cant;
		}
		
		function setID($ID){
			$this->ID = $ID;
		}
 
		function setCant($Cant){
			$this->Cant = $Cant;
		}
}



?>