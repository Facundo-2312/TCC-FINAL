<?php

namespace App\Services;

use App\Repositories\EmpleadoRepository;

class EmpleadoService
{
    private $repositorio;
    private $lastError = '';

    public function __construct(EmpleadoRepository $repositorio = null)
    {
        $this->repositorio = $repositorio ?: new EmpleadoRepository();
    }

    public function ultimoError() { return $this->lastError; }
    private function error($message) { $this->lastError = $message; return false; }
    private function nombreCompleto($nombre, $apellido) { return trim($nombre . ' ' . $apellido); }
    private function rolCanonico($rol)
    {
        $roles = array('admin' => 'Administrador', 'administrador' => 'Administrador', 'caja' => 'Caja', 'cajero' => 'Caja', 'mozo' => 'Mozo', 'cocina' => 'Cocina', 'cocinero' => 'Cocina');
        $clave = strtolower(trim((string) $rol));
        return isset($roles[$clave]) ? $roles[$clave] : trim((string) $rol);
    }

    public function listar() { return $this->repositorio->listar(); }
    public function buscar($ci) { return $this->repositorio->buscar((int) $ci); }

    public function crear($ci, $nombre, $apellido, $direccion, $rol, $usuario, $password)
    {
        $this->lastError = '';
        if ((int) $ci <= 0 || strlen((string) $password) < 8) return $this->error('La contrasena debe tener al menos 8 caracteres.');
        $rol = $this->rolCanonico($rol);
        $idRol = $this->repositorio->idRol($rol);
        if (!$idRol) return $this->error('Rol invalido: no existe en tabla roles.');
        $con = $this->repositorio->connection();
        mysqli_begin_transaction($con);
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (!$this->repositorio->crearEmpleado($ci, $nombre, $apellido, $direccion, $rol, $usuario, $hash) || !$this->repositorio->crearUsuario($this->nombreCompleto($nombre, $apellido), $usuario, $hash, $idRol)) throw new \RuntimeException();
            mysqli_commit($con);
            return true;
        } catch (\Throwable $exception) {
            mysqli_rollback($con);
            return $this->error(mysqli_errno($con) === 1062 ? 'La CI o el usuario ya existe.' : 'No se pudo guardar el funcionario.');
        }
    }

    public function actualizar($nombre, $apellido, $direccion, $rol, $usuario, $password, $ci)
    {
        $this->lastError = '';
        if ($password !== null && $password !== '' && strlen((string) $password) < 8) return $this->error('La contrasena debe tener al menos 8 caracteres.');
        $anterior = $this->buscar($ci);
        if (!$anterior) return $this->error('No se encontro funcionario para actualizar.');
        $rol = $this->rolCanonico($rol);
        $idRol = $this->repositorio->idRol($rol);
        if (!$idRol) return $this->error('Rol invalido: no existe en tabla roles.');
        $con = $this->repositorio->connection();
        mysqli_begin_transaction($con);
        try {
            $hash = $password !== null && $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : $anterior['Pass'];
            if (!$this->repositorio->actualizarEmpleado((int) $ci, $nombre, $apellido, $direccion, $rol, $usuario, $password !== null && $password !== '' ? $hash : null) || !$this->repositorio->actualizarUsuario($anterior['Usuario'], $this->nombreCompleto($nombre, $apellido), $usuario, $hash, $idRol)) throw new \RuntimeException();
            mysqli_commit($con);
            return true;
        } catch (\Throwable $exception) {
            mysqli_rollback($con);
            return $this->error(mysqli_errno($con) === 1062 ? 'La CI o el usuario ya existe.' : 'No se pudo actualizar el funcionario.');
        }
    }

    public function eliminar($ci)
    {
        $this->lastError = '';
        $anterior = $this->buscar($ci);
        if (!$anterior) return $this->error('No se encontro funcionario para eliminar.');
        $con = $this->repositorio->connection();
        mysqli_begin_transaction($con);
        try {
            if (!$this->repositorio->eliminarEmpleado((int) $ci) || !$this->repositorio->eliminarUsuario($anterior['Usuario'])) throw new \RuntimeException();
            mysqli_commit($con);
            return true;
        } catch (\Throwable $exception) {
            mysqli_rollback($con);
            return $this->error('No se pudo eliminar el funcionario porque tiene datos relacionados.');
        }
    }

    public function autenticar($usuario, $password)
    {
        $this->lastError = '';
        $user = $this->repositorio->buscarAcceso($usuario);
        if (!$user) {
            $empleado = $this->repositorio->buscarPorUsuario($usuario);
            if (!$empleado) return null;
            $idRol = $this->repositorio->idRol($this->rolCanonico($empleado['Rol']));
            if (!$idRol || !$this->repositorio->crearUsuario($this->nombreCompleto($empleado['Nombre'], $empleado['Apellido']), $empleado['Usuario'], $empleado['Pass'], $idRol)) return null;
            $user = $this->repositorio->buscarAcceso($usuario);
            if (!$user) return null;
        }
        $hash = (string) $user['password'];
        $valido = password_verify($password, $hash) || $hash === md5($password) || $hash === $password;
        if (!$valido) return null;
        if (!password_verify($password, $hash)) $this->repositorio->actualizarPasswordUsuario((int) $user['id_usuario'], password_hash($password, PASSWORD_DEFAULT));
        return (object) array('id_usuario' => (int) $user['id_usuario'], 'nombre' => $user['nombre'], 'id_rol' => (int) $user['id_rol'], 'Rol' => $user['Rol'], 'CI' => isset($user['CI']) ? (int) $user['CI'] : null);
    }
}