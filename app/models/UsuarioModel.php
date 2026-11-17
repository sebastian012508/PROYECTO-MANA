<?php
/** Consultas de autenticación. */
class UsuarioModel
{
    public function __construct(private PDO $db) {}

    public function buscarPorUsuario(string $usuario): ?array
    {
        $st = $this->db->prepare(
            'SELECT id_usuario, nombre, usuario, password_hash, rol, activo
             FROM usuarios WHERE usuario = ? LIMIT 1'
        );
        $st->execute([$usuario]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
