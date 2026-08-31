<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;

class AuthService
{
    private $usuarios;

    public function __construct(UsuarioRepository $usuarios = null)
    {
        $this->usuarios = $usuarios ?: new UsuarioRepository();
    }

    public function autenticar($usuario, $password)
    {
        $usuario = trim((string) $usuario);
        $password = (string) $password;
        if ($usuario === '' || $password === '') return null;

        $cuenta = $this->usuarios->buscarAcceso($usuario);
        if (!$cuenta) {
            $empleado = $this->usuarios->buscarEmpleadoPorUsuario($usuario);
            if (!$empleado || (($empleado['estado'] ?? 'Activo') !== 'Activo')) return null;
            $rol = $this->rolCanonico($empleado['Rol']);
            $idRol = $this->usuarios->idRolPorNombre($rol);
            if (!$idRol || !$this->usuarios->crearDesdeEmpleado(trim($empleado['Nombre'] . ' ' . $empleado['Apellido']), $empleado['Usuario'], $empleado['Pass'], $idRol)) return null;
            $cuenta = $this->usuarios->buscarAcceso($usuario);
            if (!$cuenta) return null;
        }

        if (($cuenta['estado'] ?? 'Activo') !== 'Activo') return null;
        $hash = (string) $cuenta['password'];
        $valido = password_verify($password, $hash) || $hash === md5($password) || hash_equals($hash, $password);
        if (!$valido) return null;

        if (!password_verify($password, $hash)) {
            $this->usuarios->actualizarPassword((int) $cuenta['id_usuario'], password_hash($password, PASSWORD_DEFAULT));
        }

        return (object) array(
            'id_usuario' => (int) $cuenta['id_usuario'],
            'nombre' => $cuenta['nombre'],
            'id_rol' => (int) $cuenta['id_rol'],
            'Rol' => $cuenta['rol'],
            'CI' => isset($cuenta['CI']) ? (int) $cuenta['CI'] : null
        );
    }

    private function rolCanonico($rol)
    {
        $roles = array('admin' => 'Administrador', 'administrador' => 'Administrador', 'caja' => 'Caja', 'cajero' => 'Caja', 'mozo' => 'Mozo', 'cocina' => 'Cocina', 'cocinero' => 'Cocina');
        $clave = strtolower(trim((string) $rol));
        return $roles[$clave] ?? trim((string) $rol);
    }
}