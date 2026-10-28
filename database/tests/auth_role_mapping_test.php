<?php

require_once __DIR__ . '/../../src/Support/Autoload.php';

function verificar($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class AuthRoleMappingRepositoryTest extends App\Repositories\UsuarioRepository
{
    private $cuenta;

    public function __construct($rol, $idRol)
    {
        $this->cuenta = array(
            'id_usuario' => 1,
            'nombre' => 'Cuenta de prueba',
            'id_rol' => $idRol,
            'rol' => $rol,
            'password' => password_hash('prueba-segura', PASSWORD_DEFAULT),
            'estado' => 'Activo',
            'CI' => null
        );
    }

    public function buscarAcceso($usuario)
    {
        return $this->cuenta;
    }
}

$roles = array(
    'Administrador' => 1,
    'Caja' => 2,
    'Cajero' => 2,
    'Mozo' => 3,
    'Cocina' => 4,
    'Cocinero' => 4,
    'Cliente' => 5
);

foreach ($roles as $nombre => $idEsperado) {
    $repositorio = new AuthRoleMappingRepositoryTest($nombre, 90 + $idEsperado);
    $cuenta = (new App\Services\AuthService($repositorio))->autenticar('prueba', 'prueba-segura');
    verificar($cuenta !== null && $cuenta->id_rol === $idEsperado, 'Rol canonico incorrecto: ' . $nombre);
}

echo "PASS roles se traducen a permisos canonicos independientemente del ID de BD\n";
