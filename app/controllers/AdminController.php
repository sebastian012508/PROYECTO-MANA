<?php
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../helpers/seguridad_login.php';

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

        if ($nombre === '' || $usuario === '') {
            return 'Completa nombre y usuario.';
        }

        // === INICIO VALIDACIÓN CONTRASEÑA SEGURA ===
        $errorPassword = validarPasswordSegura($password);
        if ($errorPassword !== null) {
            return $errorPassword;
        }
        // === FIN VALIDACIÓN CONTRASEÑA SEGURA ===

        if ($correo === '') {
            return 'El correo es obligatorio.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        try {
            $this->model->insertarConductor($nombre, $usuario, $correo !== '' ? $correo : null, $password);

            // Si escribieron un correo, le enviamos sus datos al conductor
            if ($correo !== '') {
                $asunto = 'Tu cuenta en MANA fue creada';

                $texto = "Hola " . $nombre . ", tu cuenta de conductor en MANA fue creada.\n\n";
                $texto .= "Estos son tus datos para iniciar sesion:\n\n";
                $texto .= "Nombre: " . $nombre . "\n";
                $texto .= "Usuario: " . $usuario . "\n";
                $texto .= "Correo: " . $correo . "\n";
                $texto .= "Contraseña: " . $password . "\n";

                $cabeceras = "From: proyectogrupomana@gmail.com\r\n";
                $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (mail($correo, $asunto, $texto, $cabeceras)) {
                    return 'Conductor creado correctamente. Se le envio un correo con sus datos.';
                } else {
                    return 'Conductor creado correctamente, pero no se pudo enviar el correo.';
                }
            }

            return 'Conductor creado correctamente.';
        } catch (PDOException $e) {
            error_log('[VIGIA ADMIN] ' . $e->getMessage());
            return 'No se pudo crear. El usuario o correo podría estar registrado.';
        }
    }

    // === INICIO HABILITAR / DESHABILITAR CUENTA ===
    public function actualizarEstadoConductor(int $idUsuario, int $nuevoEstado): string
    {
        $nuevoEstado = $nuevoEstado === 1 ? 1 : 0;
        $user = $this->model->cambiarEstadoUsuario($idUsuario, $nuevoEstado);

        if (!$user) {
            return 'No se encontró el conductor seleccionado.';
        }

        if ($nuevoEstado === 1) {
            limpiarIntentosLogin((string)$user['usuario']);
            if (!empty($user['correo'])) {
                limpiarIntentosLogin((string)$user['correo']);
            }
            return 'Cuenta habilitada correctamente. El usuario ya puede iniciar sesión.';
        }

        return 'Cuenta desactivada correctamente.';
    }
    // === FIN HABILITAR / DESHABILITAR CUENTA ===
}