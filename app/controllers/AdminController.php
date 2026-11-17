<?php
require_once __DIR__ . '/../models/AdminModel.php';

class AdminController
{
    public function __construct(private AdminModel $model) {}

    public function panel(): array
    {
        return [
            'resumen' => $this->model->resumen(),
            'sesiones' => $this->model->sesionesRecientes(),
        ];
    }

    public function conductores(): array
    {
        return $this->model->conductores();
    }

    public function crearConductor(array $datos): string
    {
        $nombre = trim($datos['nombre'] ?? '');
        $usuario = trim($datos['usuario'] ?? '');
        $correo = trim($datos['correo'] ?? '');
        $password = $datos['password'] ?? '';

        if ($nombre === '' || $usuario === '' || strlen($password) < 8) {
            return 'Completa nombre, usuario y una contraseña de al menos 8 caracteres.';
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        try {
            $this->model->insertarConductor($nombre, $usuario, $correo !== '' ? $correo : null, $password);
            return 'Conductor creado correctamente.';
        } catch (PDOException $e) {
            error_log('[VIGIA ADMIN] ' . $e->getMessage());
            return 'No se pudo crear. El usuario o correo podría estar registrado.';
        }
    }
}
