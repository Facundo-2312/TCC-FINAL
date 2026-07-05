<?php

require_once __DIR__ . '/app_bootstrap.php';

class Empleado
{
    private $con;
    private $lastError = '';

    public function __construct()
    {
        $this->connect_db();
    }

    private function setLastError($message)
    {
        $this->lastError = (string) $message;
    }

    private function friendlyDbError($fallbackMessage)
    {
        $errno = (int) mysqli_errno($this->con);
        $error = strtolower((string) mysqli_error($this->con));

        if ($errno === 1062) {
            if (strpos($error, 'usuario') !== false) {
                return 'El usuario ya existe. Ingresa un nombre de usuario diferente.';
            }

            if (strpos($error, 'ci') !== false || strpos($error, 'primary') !== false) {
                return 'La CI ya existe. Verifica el documento ingresado.';
            }

            return 'Ya existe un registro con esos datos. Verifica e intenta nuevamente.';
        }

        if ($errno === 1452) {
            return 'No se pudo asociar el rol indicado. Verifica que el rol exista.';
        }

        if ($errno === 1451) {
            return 'No se puede eliminar este registro porque tiene datos relacionados.';
        }

        return (string) $fallbackMessage;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function connect_db()
    {
        $this->setLastError('');
        $this->con = app_db_connect();

        if (!$this->con) {
            $this->setLastError("Error de conexion: " . mysqli_connect_error());
            die($this->getLastError());
        }

        mysqli_set_charset($this->con, 'utf8mb4');
    }

    private function normalizarNombreUsuario($nombre, $apellido)
    {
        return trim($nombre . ' ' . $apellido);
    }

    private function resolverIdRolPorNombre($rol)
    {
        $rol = trim((string) $rol);
        if ($rol === '') {
            return null;
        }

        $aliasRoles = array(
            'admin' => 'Administrador',
            'administrador' => 'Administrador',
            'caja' => 'Caja',
            'cajero' => 'Caja',
            'mozo' => 'Mozo',
            'cocina' => 'Cocina',
            'cocinero' => 'Cocina',
        );

        $rolNormalizado = strtolower($rol);
        if (isset($aliasRoles[$rolNormalizado])) {
            $rol = $aliasRoles[$rolNormalizado];
        }

        $stmt = mysqli_prepare($this->con, "SELECT id_rol FROM roles WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        if (!$stmt) {
            $this->setLastError($this->friendlyDbError('No se pudo consultar los roles.'));
            return null;
        }

        mysqli_stmt_bind_param($stmt, 's', $rol);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ? (int) $row['id_rol'] : null;
    }

    private function obtenerPasswordEmpleado($usuario)
    {
        $stmt = mysqli_prepare($this->con, "SELECT Pass FROM empleado WHERE Usuario = ? LIMIT 1");
        if (!$stmt) {
            $this->setLastError($this->friendlyDbError('No se pudo obtener la clave actual del funcionario.'));
            return null;
        }

        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row['Pass'] ?? null;
    }

    private function crearUsuarioDesdeEmpleado($usuario)
    {
        $stmt = mysqli_prepare(
            $this->con,
            "SELECT Nombre, Apellido, Rol, Usuario, Pass FROM empleado WHERE Usuario = ? LIMIT 1"
        );

        if (!$stmt) {
            $this->setLastError($this->friendlyDbError('No se pudo leer el funcionario para sincronizar su usuario.'));
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            $this->setLastError('No existe empleado para sincronizar con usuarios.');
            return false;
        }

        $idRol = $this->resolverIdRolPorNombre((string) ($row['Rol'] ?? ''));
        if ($idRol === null) {
            $this->setLastError('No se encontro el rol para sincronizar usuario.');
            return false;
        }

        $nombreCompleto = $this->normalizarNombreUsuario((string) $row['Nombre'], (string) $row['Apellido']);
        $password = (string) $row['Pass'];

        $stmtInsert = mysqli_prepare(
            $this->con,
            "INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)"
        );

        if (!$stmtInsert) {
            $this->setLastError($this->friendlyDbError('No se pudo preparar el registro de acceso del funcionario.'));
            return false;
        }

        mysqli_stmt_bind_param($stmtInsert, 'sssi', $nombreCompleto, $row['Usuario'], $password, $idRol);
        $ok = mysqli_stmt_execute($stmtInsert);
        mysqli_stmt_close($stmtInsert);

        if (!$ok) {
            $this->setLastError($this->friendlyDbError('No se pudo crear el usuario de acceso del funcionario.'));
        }

        return $ok;
    }

    public function create($CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $Pass)
    {
        $this->setLastError('');
        $idRol = $this->resolverIdRolPorNombre($Rol);
        if ($idRol === null) {
            if ($this->getLastError() === '') {
                $this->setLastError('Rol invalido: no existe en tabla roles.');
            }
            return false;
        }

        mysqli_begin_transaction($this->con);

        $passwordHash = password_hash($Pass, PASSWORD_DEFAULT);

        $stmtEmpleado = mysqli_prepare(
            $this->con,
            "INSERT INTO empleado (CI, Nombre, Apellido, Direccion, Rol, Usuario, Pass)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmtEmpleado) {
            $this->setLastError($this->friendlyDbError('No se pudo preparar el alta del funcionario.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmtEmpleado, 'issssss', $CI, $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $passwordHash);
        $okEmpleado = mysqli_stmt_execute($stmtEmpleado);
        mysqli_stmt_close($stmtEmpleado);

        if (!$okEmpleado) {
            $this->setLastError($this->friendlyDbError('No se pudo guardar el funcionario.'));
            mysqli_rollback($this->con);
            return false;
        }

        $nombreCompleto = $this->normalizarNombreUsuario($Nombre, $Apellido);
        $stmtUsuario = mysqli_prepare(
            $this->con,
            "INSERT INTO usuarios (nombre, usuario, password, id_rol) VALUES (?, ?, ?, ?)"
        );

        if (!$stmtUsuario) {
            $this->setLastError($this->friendlyDbError('No se pudo preparar el usuario de acceso.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmtUsuario, 'sssi', $nombreCompleto, $Usuario, $passwordHash, $idRol);
        $okUsuario = mysqli_stmt_execute($stmtUsuario);
        mysqli_stmt_close($stmtUsuario);

        if (!$okUsuario) {
            $this->setLastError($this->friendlyDbError('No se pudo crear el usuario de acceso.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_commit($this->con);

        return true;
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
        $this->setLastError('');
        $CI = (int) $CI;
        $idRol = $this->resolverIdRolPorNombre($Rol);

        if ($idRol === null) {
            if ($this->getLastError() === '') {
                $this->setLastError('Rol invalido: no existe en tabla roles.');
            }
            return false;
        }

        $anterior = $this->BuscarUsuario($CI);
        if (!$anterior || empty($anterior->Usuario)) {
            $this->setLastError('No se encontro funcionario para actualizar.');
            return false;
        }

        mysqli_begin_transaction($this->con);
        $usuarioAnterior = (string) $anterior->Usuario;
        $nombreCompleto = $this->normalizarNombreUsuario($Nombre, $Apellido);

        if ($Pass !== null && $Pass !== '') {
            $passwordHash = password_hash($Pass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE empleado
                 SET Nombre = ?, Apellido = ?, Direccion = ?, Rol = ?, Usuario = ?, Pass = ?
                 WHERE CI = ?"
            );

            if (!$stmt) {
                $this->setLastError($this->friendlyDbError('No se pudo preparar la actualizacion del funcionario.'));
                mysqli_rollback($this->con);
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'ssssssi', $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $passwordHash, $CI);

            $stmtUsuario = mysqli_prepare(
                $this->con,
                "UPDATE usuarios
                 SET nombre = ?, usuario = ?, password = ?, id_rol = ?
                 WHERE usuario = ?"
            );

            if (!$stmtUsuario) {
                mysqli_stmt_close($stmt);
                $this->setLastError($this->friendlyDbError('No se pudo preparar la actualizacion del usuario de acceso.'));
                mysqli_rollback($this->con);
                return false;
            }
        } else {
            $stmt = mysqli_prepare(
                $this->con,
                "UPDATE empleado
                 SET Nombre = ?, Apellido = ?, Direccion = ?, Rol = ?, Usuario = ?
                 WHERE CI = ?"
            );

            if (!$stmt) {
                $this->setLastError($this->friendlyDbError('No se pudo preparar la actualizacion del funcionario.'));
                mysqli_rollback($this->con);
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'sssssi', $Nombre, $Apellido, $Direccion, $Rol, $Usuario, $CI);

            $passwordActual = $this->obtenerPasswordEmpleado($usuarioAnterior);
            if ($passwordActual === null) {
                mysqli_stmt_close($stmt);
                if ($this->getLastError() === '') {
                    $this->setLastError('No se pudo conservar la password actual del funcionario.');
                }
                mysqli_rollback($this->con);
                return false;
            }

            $stmtUsuario = mysqli_prepare(
                $this->con,
                "UPDATE usuarios
                 SET nombre = ?, usuario = ?, password = ?, id_rol = ?
                 WHERE usuario = ?"
            );

            if (!$stmtUsuario) {
                mysqli_stmt_close($stmt);
                $this->setLastError($this->friendlyDbError('No se pudo preparar la actualizacion del usuario de acceso.'));
                mysqli_rollback($this->con);
                return false;
            }

            $passwordHash = $passwordActual;
        }

        $okEmpleado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$okEmpleado) {
            mysqli_stmt_close($stmtUsuario);
            $this->setLastError($this->friendlyDbError('No se pudo actualizar el funcionario.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmtUsuario, 'sssis', $nombreCompleto, $Usuario, $passwordHash, $idRol, $usuarioAnterior);
        $okUsuario = mysqli_stmt_execute($stmtUsuario);
        mysqli_stmt_close($stmtUsuario);

        if (!$okUsuario) {
            $this->setLastError($this->friendlyDbError('No se pudo actualizar el usuario de acceso asociado.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_commit($this->con);
        return true;
    }

    public function delete($CI)
    {
        $this->setLastError('');
        $dato = $this->BuscarUsuario((int) $CI);
        if (!$dato || empty($dato->Usuario)) {
            $this->setLastError('No se encontro funcionario para eliminar.');
            return false;
        }

        $usuario = (string) $dato->Usuario;

        mysqli_begin_transaction($this->con);

        $stmt = mysqli_prepare($this->con, "DELETE FROM empleado WHERE CI = ?");
        if (!$stmt) {
            $this->setLastError($this->friendlyDbError('No se pudo preparar la eliminacion del funcionario.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'i', $CI);
        $okEmpleado = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$okEmpleado) {
            $this->setLastError($this->friendlyDbError('No se pudo eliminar el funcionario.'));
            mysqli_rollback($this->con);
            return false;
        }

        $stmtUsuario = mysqli_prepare($this->con, "DELETE FROM usuarios WHERE usuario = ?");
        if (!$stmtUsuario) {
            $this->setLastError($this->friendlyDbError('No se pudo preparar la eliminacion del usuario de acceso.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_stmt_bind_param($stmtUsuario, 's', $usuario);
        $okUsuario = mysqli_stmt_execute($stmtUsuario);
        mysqli_stmt_close($stmtUsuario);

        if (!$okUsuario) {
            $this->setLastError($this->friendlyDbError('No se pudo eliminar el usuario de acceso asociado.'));
            mysqli_rollback($this->con);
            return false;
        }

        mysqli_commit($this->con);

        return true;
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
            if (!$this->crearUsuarioDesdeEmpleado($Usuario)) {
                return null;
            }

            $stmtRetry = mysqli_prepare(
                $this->con,
                "SELECT u.id_usuario, u.nombre, u.id_rol, r.nombre AS Rol, u.password, e.CI
                 FROM usuarios u
                 INNER JOIN roles r ON u.id_rol = r.id_rol
                 LEFT JOIN empleado e ON e.Usuario = u.usuario
                 WHERE u.usuario = ?
                 LIMIT 1"
            );

            if (!$stmtRetry) {
                return null;
            }

            mysqli_stmt_bind_param($stmtRetry, 's', $Usuario);
            mysqli_stmt_execute($stmtRetry);
            $resRetry = mysqli_stmt_get_result($stmtRetry);
            $user = $resRetry ? mysqli_fetch_assoc($resRetry) : null;
            mysqli_stmt_close($stmtRetry);

            if (!$user) {
                return null;
            }
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
