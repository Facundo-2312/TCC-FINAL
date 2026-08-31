<?php

namespace App\Support;

// DB-backed brute-force throttle, keyed by usuario+IP (survives across requests/processes; no filesystem locking needed).
class LoginThrottle
{
    const MAX_INTENTOS = 5;
    const BLOQUEO_SEGUNDOS = 900; // 15 minutos

    private static function tabla($con)
    {
        @mysqli_query($con, "
            CREATE TABLE IF NOT EXISTS intentos_login (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(191) NOT NULL,
                intentos INT NOT NULL DEFAULT 0,
                bloqueado_hasta DATETIME NULL,
                actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_clave (clave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private static function clave($usuario)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        return strtolower(trim((string) $usuario)) . '|' . $ip;
    }

    // Returns remaining lockout seconds (0 = not locked out).
    public static function segundosBloqueado($usuario)
    {
        $con = Database::connect();
        self::tabla($con);
        $clave = self::clave($usuario);

        $stmt = mysqli_prepare($con, 'SELECT bloqueado_hasta FROM intentos_login WHERE clave = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $clave);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row || empty($row['bloqueado_hasta'])) {
            return 0;
        }

        $restante = strtotime($row['bloqueado_hasta']) - time();
        return $restante > 0 ? $restante : 0;
    }

    public static function registrarFallo($usuario)
    {
        $con = Database::connect();
        self::tabla($con);
        $clave = self::clave($usuario);

        $stmt = mysqli_prepare($con, 'SELECT intentos FROM intentos_login WHERE clave = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $clave);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        $intentos = $row ? ((int) $row['intentos'] + 1) : 1;
        $bloqueadoHasta = null;

        if ($intentos >= self::MAX_INTENTOS) {
            $bloqueadoHasta = date('Y-m-d H:i:s', time() + self::BLOQUEO_SEGUNDOS);
            SecurityLog::log('login_bloqueado', array('usuario' => $usuario));
        } else {
            SecurityLog::log('login_fallido', array('usuario' => $usuario, 'intentos' => $intentos));
        }

        $stmt = mysqli_prepare(
            $con,
            'INSERT INTO intentos_login (clave, intentos, bloqueado_hasta) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE intentos = VALUES(intentos), bloqueado_hasta = VALUES(bloqueado_hasta)'
        );
        mysqli_stmt_bind_param($stmt, 'sis', $clave, $intentos, $bloqueadoHasta);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    public static function limpiar($usuario)
    {
        $con = Database::connect();
        self::tabla($con);
        $clave = self::clave($usuario);

        $stmt = mysqli_prepare($con, 'DELETE FROM intentos_login WHERE clave = ?');
        mysqli_stmt_bind_param($stmt, 's', $clave);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
