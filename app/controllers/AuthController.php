<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

/** Valida credenciales y devuelve el usuario desde la base de datos. */
class AuthController
{
    public function __construct(private UsuarioModel $model) {}

    public function autenticar(string $usuario, string $password): ?array
    {
        $row = $this->model->buscarPorUsuario($usuario);
        if (!$row || (int)$row['activo'] !== 1) {
            return null;
        }
        if (!password_verify($password, $row['password_hash'])) {
            return null;
        }
        return $row;
    }
}
