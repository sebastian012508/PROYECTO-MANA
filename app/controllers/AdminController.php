<?php
require_once __DIR__ . '/../models/AdminModel.php';
require_once __DIR__ . '/../helpers/seguridad_login.php';

class AdminController
{
    public const LICENCIAS_PERMITIDAS = [
        'Categoría P (Particular)',
        'Categoría A (Profesional - Hasta 3.5 ton)',
        'Categoría B (Profesional - Carga / Pasajeros)',
        'Categoría C (Profesional - Transporte Pesado)',
        'Categoría T (Maquinaria / Especial)',
    ];

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
        $nombre       = trim($datos['nombre'] ?? '');
        $usuario      = trim($datos['usuario'] ?? '');
        $correo       = trim($datos['correo'] ?? '');
        $tipoLicencia = trim($datos['tipo_licencia'] ?? '');
        $password     = $datos['password'] ?? '';

        if ($nombre === '' || $usuario === '') {
            return 'Completa nombre y usuario.';
        }

        if ($tipoLicencia === '' || !in_array($tipoLicencia, self::LICENCIAS_PERMITIDAS, true)) {
            return 'Selecciona un tipo de licencia válido.';
        }

        $errorPassword = validarPasswordSegura($password);
        if ($errorPassword !== null) {
            return $errorPassword;
        }

        if ($correo === '') {
            return 'El correo es obligatorio.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        try {
            $this->model->insertarConductor(
                $nombre,
                $usuario,
                $correo,
                $tipoLicencia,
                $password
            );

            $asunto = 'Tu cuenta en MANA fue creada';

            $texto = "Hola " . $nombre . ", tu cuenta de conductor en MANA fue creada.\n\n";
            $texto .= "Estos son tus datos para iniciar sesión:\n\n";
            $texto .= "Nombre: " . $nombre . "\n";
            $texto .= "Usuario: " . $usuario . "\n";
            $texto .= "Correo: " . $correo . "\n";
            $texto .= "Tipo de licencia: " . $tipoLicencia . "\n";
            $texto .= "Contraseña: " . $password . "\n";

            $cabeceras = "From: proyectogrupomana@gmail.com\r\n";
            $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($correo, $asunto, $texto, $cabeceras)) {
                return 'Conductor creado correctamente. Se le envió un correo con sus datos.';
            } else {
                return 'Conductor creado correctamente, pero no se pudo enviar el correo.';
            }
        } catch (PDOException $e) {
            error_log('[VIGIA ADMIN] ' . $e->getMessage());
            return 'No se pudo crear. El usuario o correo podría estar registrado.';
        }
    }

    public function editarConductor(array $datos): string
    {
        $idUsuario    = (int)($datos['id_usuario'] ?? 0);
        $nombre       = trim($datos['nombre'] ?? '');
        $usuario      = trim($datos['usuario'] ?? '');
        $correo       = trim($datos['correo'] ?? '');
        $tipoLicencia = trim($datos['tipo_licencia'] ?? '');
        $activo       = ((int)($datos['activo'] ?? 1)) === 1 ? 1 : 0;
        $password     = $datos['password'] ?? '';

        if ($idUsuario <= 0) {
            return 'Identificador de conductor no válido.';
        }
        if ($nombre === '' || $usuario === '' || $correo === '') {
            return 'Nombre, usuario y correo son obligatorios.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        if ($tipoLicencia !== '' && !in_array($tipoLicencia, self::LICENCIAS_PERMITIDAS, true)) {
            return 'Selecciona un tipo de licencia válido.';
        }

        if ($password !== '') {
            $errorPassword = validarPasswordSegura($password);
            if ($errorPassword !== null) {
                return $errorPassword;
            }
        }

        try {
            $this->model->actualizarConductor(
                $idUsuario,
                $nombre,
                $usuario,
                $correo,
                $tipoLicencia !== '' ? $tipoLicencia : null,
                $activo,
                $password !== '' ? $password : null
            );

            if ($activo === 1) {
                limpiarIntentosLogin($usuario);
                limpiarIntentosLogin($correo);
            }

            return 'Datos del conductor actualizados correctamente.';
        } catch (PDOException $e) {
            error_log('[VIGIA ADMIN EDIT] ' . $e->getMessage());
            return 'No se pudo actualizar. Verifica que el usuario o correo no pertenezcan a otra cuenta.';
        }
    }

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

    public function eliminarConductor(array $datos): string
    {
        $idUsuario      = (int)($datos['id_usuario'] ?? 0);
        $passwordCuenta = $datos['password_confirmacion'] ?? '';

        if ($idUsuario <= 0) {
            return 'Identificador de cuenta no válido.';
        }
        if ($passwordCuenta === '') {
            return 'Debes ingresar la contraseña de la cuenta para confirmar su eliminación.';
        }

        $resultado = $this->model->eliminarConductorConPassword($idUsuario, $passwordCuenta);

        if ($resultado['ok']) {
            if (!empty($resultado['usuario'])) {
                limpiarIntentosLogin($resultado['usuario']);
            }
            if (!empty($resultado['correo'])) {
                limpiarIntentosLogin($resultado['correo']);
            }
        }

        return $resultado['mensaje'];
    }
}