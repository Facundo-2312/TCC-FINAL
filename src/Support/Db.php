<?php

namespace App\Support;

// Replaces the previous pattern of `die("... " . mysqli_error($con))`, which leaked DB engine
// details (table/column names, SQL fragments) directly to the browser. Logs the real detail and
// shows a generic message instead.
class Db
{
    public static function fail($mensajePublico, $detalleTecnico = '')
    {
        SecurityLog::log('error_bd', array('detalle' => (string) $detalleTecnico));
        die((string) $mensajePublico);
    }
}
