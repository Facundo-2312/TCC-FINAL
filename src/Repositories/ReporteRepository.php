<?php

namespace App\Repositories;

use App\Support\Database;

class ReporteRepository
{
    private $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Database::connect();
    }

    public function resumenPagosHoy()
    {
        $result = mysqli_query($this->connection, 'SELECT COALESCE(SUM(monto), 0) AS recaudado, COALESCE(SUM(propina), 0) AS propina, COUNT(*) AS pagos FROM pagos WHERE fecha_dia = CURDATE()');
        return $result ? mysqli_fetch_assoc($result) : null;
    }
}