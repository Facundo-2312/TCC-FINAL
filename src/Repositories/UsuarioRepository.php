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

    public function crearCliente($nombre, $usuario, $password)
    {
        $stmt = mysqli_prepare($this->connection, "SELECT id_rol FROM roles WHERE LOWER(nombre) = 'cliente' LIMIT 1");
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rol = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if (!$rol) return array('ok' => false, 'error' => 'El rol de cliente no esta habilitado. Ejecuta las migraciones de la base de datos.');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $idRol = (int) $rol['id_rol'];
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $usuario, $hash, $idRol);
        $ok = mysqli_stmt_execute($stmt);
        $error = mysqli_stmt_errno($stmt) === 1062 ? 'Ese usuario ya existe.' : 'No se pudo crear el registro.';
        mysqli_stmt_close($stmt);
        return array('ok' => $ok, 'error' => $ok ? '' : $error);
    }

    public function crearClienteInvitado($nombre)
    {
        $rol = $this->idRolPorNombre('Cliente');
        if (!$rol) return null;

        $usuario = 'cliente_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($this->connection, 'INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $usuario, $password, $rol);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok ? $usuario : null;
    }
}