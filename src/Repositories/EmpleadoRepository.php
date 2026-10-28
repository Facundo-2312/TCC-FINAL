<?php

namespace App\Repositories;

use App\Support\Database;

class EmpleadoRepository
{
    private $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Database::connect();
    }

    public function connection()
    {
        return $this->connection;
    }

    private static $ubicacionDisponible = null;

    /**
     * Production databases may predate migration 015; add the location columns
     * on demand and degrade gracefully when ALTER is not permitted.
     */
    private function tieneUbicacion()
    {
        if (self::$ubicacionDisponible !== null) {
            return self::$ubicacionDisponible;
        }

        $faltantes = array();
        foreach (array('direccion_latitud', 'direccion_longitud') as $columna) {
            try {
                $res = mysqli_query($this->connection, "SHOW COLUMNS FROM empleado LIKE '" . $columna . "'");
            } catch (\mysqli_sql_exception $e) {
                error_log('No se pudieron comprobar las columnas de ubicacion de empleado: ' . $e->getMessage());
                return self::$ubicacionDisponible = false;
            }

            if (!$res) {
                error_log('No se pudieron comprobar las columnas de ubicacion de empleado: ' . mysqli_error($this->connection));
                return self::$ubicacionDisponible = false;
            }
            if (mysqli_num_rows($res) === 0) {
                $faltantes[] = $columna;
            }
        }

        foreach ($faltantes as $columna) {
            $despues = $columna === 'direccion_latitud' ? 'Direccion' : 'direccion_latitud';
            try {
                if (!mysqli_query($this->connection, 'ALTER TABLE empleado ADD COLUMN ' . $columna . ' DECIMAL(10,7) NULL AFTER ' . $despues)) {
                    error_log('No se pudo agregar la columna ' . $columna . ' a empleado: ' . mysqli_error($this->connection));
                    return self::$ubicacionDisponible = false;
                }
            } catch (\mysqli_sql_exception $e) {
                error_log('No se pudo agregar la columna ' . $columna . ' a empleado: ' . $e->getMessage());
                return self::$ubicacionDisponible = false;
            }
        }
        return self::$ubicacionDisponible = true;
    }

    private function columnasUbicacion()
    {
        return $this->tieneUbicacion()
            ? 'direccion_latitud, direccion_longitud'
            : 'NULL AS direccion_latitud, NULL AS direccion_longitud';
    }

    public function listar()
    {
        $result = mysqli_query($this->connection, 'SELECT CI, Nombre, Apellido, Direccion, ' . $this->columnasUbicacion() . ', Usuario, Rol FROM empleado ORDER BY Nombre ASC');
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : array();
    }

    public function buscar($ci)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT CI, Nombre, Apellido, Direccion, ' . $this->columnasUbicacion() . ', Rol, Usuario, Pass FROM empleado WHERE CI = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $ci);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function buscarPorUsuario($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT CI, Nombre, Apellido, Direccion, Rol, Usuario, Pass FROM empleado WHERE Usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function idRol($nombre)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_rol FROM roles WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $nombre);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_rol'] : null;
    }

    public function crearEmpleado($ci, $nombre, $apellido, $direccion, $latitud, $longitud, $rol, $usuario, $password)
    {
        if (!$this->tieneUbicacion()) {
            $stmt = mysqli_prepare($this->connection, 'INSERT INTO empleado (CI, Nombre, Apellido, Direccion, Rol, Usuario, Pass) VALUES (?, ?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'issssss', $ci, $nombre, $apellido, $direccion, $rol, $usuario, $password);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        }
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO empleado (CI, Nombre, Apellido, Direccion, direccion_latitud, direccion_longitud, Rol, Usuario, Pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'isssddsss', $ci, $nombre, $apellido, $direccion, $latitud, $longitud, $rol, $usuario, $password);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function crearUsuario($nombre, $usuario, $password, $idRol)
    {
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $usuario, $password, $idRol);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function actualizarEmpleado($ci, $nombre, $apellido, $direccion, $latitud, $longitud, $rol, $usuario, $password = null)
    {
        if (!$this->tieneUbicacion()) {
            if ($password !== null) {
                $stmt = mysqli_prepare($this->connection, 'UPDATE empleado SET Nombre=?, Apellido=?, Direccion=?, Rol=?, Usuario=?, Pass=? WHERE CI=?');
                mysqli_stmt_bind_param($stmt, 'ssssssi', $nombre, $apellido, $direccion, $rol, $usuario, $password, $ci);
            } else {
                $stmt = mysqli_prepare($this->connection, 'UPDATE empleado SET Nombre=?, Apellido=?, Direccion=?, Rol=?, Usuario=? WHERE CI=?');
                mysqli_stmt_bind_param($stmt, 'sssssi', $nombre, $apellido, $direccion, $rol, $usuario, $ci);
            }
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $ok;
        }
        if ($password !== null) {
            $stmt = mysqli_prepare($this->connection, 'UPDATE empleado SET Nombre=?, Apellido=?, Direccion=?, direccion_latitud=?, direccion_longitud=?, Rol=?, Usuario=?, Pass=? WHERE CI=?');
            mysqli_stmt_bind_param($stmt, 'sssddsssi', $nombre, $apellido, $direccion, $latitud, $longitud, $rol, $usuario, $password, $ci);
        } else {
            $stmt = mysqli_prepare($this->connection, 'UPDATE empleado SET Nombre=?, Apellido=?, Direccion=?, direccion_latitud=?, direccion_longitud=?, Rol=?, Usuario=? WHERE CI=?');
            mysqli_stmt_bind_param($stmt, 'sssddssi', $nombre, $apellido, $direccion, $latitud, $longitud, $rol, $usuario, $ci);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function actualizarUsuario($usuarioAnterior, $nombre, $usuario, $password, $idRol)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE usuarios SET nombre=?, usuario=?, password=?, id_rol=? WHERE usuario=?');
        mysqli_stmt_bind_param($stmt, 'sssis', $nombre, $usuario, $password, $idRol, $usuarioAnterior);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminarEmpleado($ci)
    {
        $stmt = mysqli_prepare($this->connection, 'DELETE FROM empleado WHERE CI = ?');
        mysqli_stmt_bind_param($stmt, 'i', $ci);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function eliminarUsuario($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'DELETE FROM usuarios WHERE usuario = ?');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function buscarAcceso($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT u.id_usuario, u.nombre, u.id_rol, r.nombre AS Rol, u.password, e.CI FROM usuarios u INNER JOIN roles r ON u.id_rol=r.id_rol LEFT JOIN empleado e ON e.Usuario=u.usuario WHERE u.usuario=? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function actualizarPasswordUsuario($idUsuario, $password)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE usuarios SET password = ? WHERE id_usuario = ?');
        mysqli_stmt_bind_param($stmt, 'si', $password, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}