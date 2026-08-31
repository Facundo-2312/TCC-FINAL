<?php

namespace App\Repositories;

use App\Support\Database;

class UsuarioRepository
{
    private $connection;

    public function __construct($connection = null)
    {
        $this->connection = $connection ?: Database::connect();
    }

    public function buscarAcceso($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT u.id_usuario, u.nombre, u.usuario, u.id_rol, u.password, u.estado, r.nombre AS rol, e.CI FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol LEFT JOIN empleado e ON e.Usuario = u.usuario WHERE u.usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function buscarEmpleadoPorUsuario($usuario)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT Nombre, Apellido, Rol, Usuario, Pass, estado FROM empleado WHERE Usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function idRolPorNombre($rol)
    {
        $stmt = mysqli_prepare($this->connection, 'SELECT id_rol FROM roles WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $rol);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ? (int) $row['id_rol'] : null;
    }

    public function crearDesdeEmpleado($nombre, $usuario, $password, $idRol)
    {
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $usuario, $password, $idRol);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function actualizarPassword($idUsuario, $password)
    {
        $stmt = mysqli_prepare($this->connection, 'UPDATE usuarios SET password = ? WHERE id_usuario = ?');
        mysqli_stmt_bind_param($stmt, 'si', $password, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}