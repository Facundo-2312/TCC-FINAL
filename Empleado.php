<?php

class Empleado
{
    private $con;
    private $dbhost = "localhost";
    private $dbuser = "root";
    private $dbpass = "";
    private $dbname = "proyectomagnus";

    function __construct()
    {
        $this->connect_db();
    }

    public function connect_db()
    {
        $this->con = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpass, $this->dbname);
        if (mysqli_connect_error()) {
            die("Error de conexión: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->con, 'utf8mb4');
    }

    public function create($CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass)
    {
        $passwordHash = password_hash($Pass, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $this->con,
            "INSERT INTO empleado (CI, Nombre, Apellido, Direccion, Rol, Usuario, Pass)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'issssss', $CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $passwordHash);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    public function ListarEmpleado()
    {
        $sql = "SELECT CI, Nombre, Apellido, Direccion, Usuario, Rol FROM empleado ORDER BY Nombre ASC";
        $res = mysqli_query($this->con, $sql);

        if (!$res) {
            die("Error al listar empleados: " . mysqli_error($this->con));
        }

        $datos = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $datos[] = $row;
        }

        return $datos;
    }

    public function BuscarUsuario($CI)
    {
        $stmt = mysqli_prepare($this->con, "SELECT CI, Nombre, Apellido, Direccion, Rol, Usuario FROM empleado WHERE CI = ?");
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $CI);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_object($res) : null;
        mysqli_stmt_close($stmt);

        return $row;
    }

    public function update($Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass, $CI)
    {
        $CI = (int) $CI;

        if ($Pass !== null && $Pass !== '') {
            $passwordHash = password_hash($Pass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE empleado
                 SET Nombre = ?, Apellido = ?, Direccion = ?, Rol = ?, Usuario = ?, Pass = ?
                 WHERE CI = ?"
            );

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'ssssssi', $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $passwordHash, $CI);
        } else {
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE empleado
                 SET Nombre = ?, Apellido = ?, Direccion = ?, Rol = ?, Usuario = ?
                 WHERE CI = ?"
            );

            if (!$stmt) {
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'sssssi', $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $CI);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    public function delete($CI)
    {
        $stmt = mysqli_prepare($this->con, "DELETE FROM empleado WHERE CI = ?");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'i', $CI);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    private function actualizarPasswordUsuario($idUsuario, $nuevoHash)
    {
        $stmt = mysqli_prepare($this->con, "UPDATE usuarios SET password = ? WHERE id_usuario = ?");
        if (!$stmt) {
            return;
        }

        mysqli_stmt_bind_param($stmt, 'si', $nuevoHash, $idUsuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    public function Login($Usuario, $Pass)
    {
        $stmt = mysqli_prepare(
            $this->con,
            "SELECT u.id_usuario, u.nombre, u.id_rol, r.nombre AS Rol, u.password, e.CI
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id_rol
             LEFT JOIN empleado e ON e.Usuario = u.usuario
             WHERE u.usuario = ?
             LIMIT 1"
        );

        if (!$stmt) {
            die("Error en login: " . mysqli_error($this->con));
        }

        mysqli_stmt_bind_param($stmt, 's', $Usuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$user) {
            return null;
        }

        $passwordGuardado = (string) $user['password'];
        $loginOk = false;

        if (password_verify($Pass, $passwordGuardado)) {
            $loginOk = true;
        } elseif ($passwordGuardado === md5($Pass) || $passwordGuardado === $Pass) {
            $loginOk = true;
            $nuevoHash = password_hash($Pass, PASSWORD_DEFAULT);
            $this->actualizarPasswordUsuario((int) $user['id_usuario'], $nuevoHash);
        }

        if (!$loginOk) {
            return null;
        }

        return (object) array(
            'id_usuario' => (int) $user['id_usuario'],
            'nombre' => $user['nombre'],
            'id_rol' => (int) $user['id_rol'],
            'Rol' => $user['Rol'],
            'CI' => isset($user['CI']) ? (int) $user['CI'] : null
        );
    }
}

?>
