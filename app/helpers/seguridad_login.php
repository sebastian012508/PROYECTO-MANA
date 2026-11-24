<?php
declare(strict_types=1);

/**
 * Valida que la contraseña cumpla con:
 * - Mínimo 8 caracteres
 * - Al menos 1 letra mayúscula
 * - Al menos 1 letra minúscula
 * - Al menos 1 número
 * - Al menos 1 signo / carácter especial
 *
 * @return string|null Devuelve null si es válida, o el mensaje de error si no cumple.
 */
function validarPasswordSegura(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'La contraseña debe incluir al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'La contraseña debe incluir al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'La contraseña debe incluir al menos un número.';
    }
    if (!preg_match('/[\W_]/', $password)) {
        return 'La contraseña debe incluir al menos un signo o carácter especial (ej. @, #, $, %, !, *).';
    }
    return null;
}

/**
 * Ruta interna donde se almacenan los estados de bloqueo por usuario (sin modificar la BD).
 */
function rutaEstadoBloqueo(string $identificador): string
{
    $dir = __DIR__ . '/../../storage/login_attempts';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Proteger el directorio frente a acceso web directo en Apache
        file_put_contents($dir . '/.htaccess', "Deny from all\n");
    }
    $clave = hash('sha256', mb_strtolower(trim($identificador), 'UTF-8'));
    return $dir . '/' . $clave . '.json';
}

function leerEstadoBloqueo(string $identificador): array
{
    $default = [
        'fallos'           => 0,
        'bloqueado_hasta'  => 0,
        'bloqueo_total'    => false,
    ];
    if (trim($identificador) === '') {
        return $default;
    }
    $archivo = rutaEstadoBloqueo($identificador);
    if (!file_exists($archivo)) {
        return $default;
    }
    $datos = json_decode((string)file_get_contents($archivo), true);
    return is_array($datos) ? array_merge($default, $datos) : $default;
}

function guardarEstadoBloqueo(string $identificador, array $estado): void
{
    if (trim($identificador) === '') {
        return;
    }
    file_put_contents(rutaEstadoBloqueo($identificador), json_encode($estado, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Verifica si un usuario tiene bloqueo temporal o permanente activo.
 */
function verificarEstadoBloqueo(PDO $pdo, string $identificador): array
{
    $identificador = trim($identificador);
    if ($identificador === '') {
        return ['bloqueado' => false, 'segundos' => 0, 'permanente' => false, 'mensaje' => ''];
    }

    // 1. Verificar en la base de datos si la cuenta ya está desactivada (activo = 0)
    $stmt = $pdo->prepare('SELECT id_usuario, activo FROM usuarios WHERE usuario = :u OR correo = :c LIMIT 1');
    $stmt->execute([':u' => $identificador, ':c' => $identificador]);
    $userDb = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userDb && (int)$userDb['activo'] === 0) {
        return [
            'bloqueado'  => true,
            'segundos'   => 0,
            'permanente' => true,
            'mensaje'    => 'Esta cuenta ha sido bloqueada totalmente por múltiples intentos fallidos o desactivada. Contacta al administrador para habilitarla nuevamente.',
        ];
    }

    // 2. Verificar bloqueo temporal (30 s o 15 min)
    $estado = leerEstadoBloqueo($identificador);
    $ahora  = time();

    if (!empty($estado['bloqueo_total'])) {
        return [
            'bloqueado'  => true,
            'segundos'   => 0,
            'permanente' => true,
            'mensaje'    => 'Esta cuenta está bloqueada totalmente. Solicita al administrador que la habilite de nuevo.',
        ];
    }

    if ((int)$estado['bloqueado_hasta'] > $ahora) {
        $restantes = (int)$estado['bloqueado_hasta'] - $ahora;
        return [
            'bloqueado'  => true,
            'segundos'   => $restantes,
            'permanente' => false,
            'mensaje'    => 'Demasiados intentos fallidos. Acceso bloqueado temporalmente.',
        ];
    }

    return ['bloqueado' => false, 'segundos' => 0, 'permanente' => false, 'mensaje' => ''];
}

/**
 * Registra un intento fallido y aplica la escala progresiva:
 * - 3 fallos  -> 30 segundos
 * - 6 fallos  -> 15 minutos (900 segundos)
 * - 9 fallos  -> Bloqueo total (activo = 0 en la tabla usuarios)
 */
function registrarIntentoFallido(PDO $pdo, string $identificador): array
{
    $identificador = trim($identificador);
    if ($identificador === '') {
        return [
            'bloqueado'  => false,
            'segundos'   => 0,
            'permanente' => false,
            'mensaje'    => 'Usuario o contraseña incorrectos.',
        ];
    }

    // Si ya está desactivado en BD, devolver bloqueo total inmediato
    $actual = verificarEstadoBloqueo($pdo, $identificador);
    if ($actual['bloqueado']) {
        return $actual;
    }

    $estado = leerEstadoBloqueo($identificador);
    $estado['fallos'] = (int)$estado['fallos'] + 1;
    $fallos = $estado['fallos'];

    if ($fallos === 3) {
        // Nivel 1: 30 segundos
        $estado['bloqueado_hasta'] = time() + 30;
        guardarEstadoBloqueo($identificador, $estado);
        return [
            'bloqueado'  => true,
            'segundos'   => 30,
            'permanente' => false,
            'mensaje'    => 'Has fallado 3 veces. El inicio de sesión queda bloqueado durante 30 segundos.',
        ];
    }

    if ($fallos === 6) {
        // Nivel 2: 15 minutos (900 segundos)
        $estado['bloqueado_hasta'] = time() + 900;
        guardarEstadoBloqueo($identificador, $estado);
        return [
            'bloqueado'  => true,
            'segundos'   => 900,
            'permanente' => false,
            'mensaje'    => 'Has vuelto a fallar 3 veces. El inicio de sesión queda bloqueado durante 15 minutos.',
        ];
    }

    if ($fallos >= 9) {
        // Nivel 3: Bloqueo total en BD (activo = 0)
        $estado['bloqueo_total']   = true;
        $estado['bloqueado_hasta'] = 0;
        guardarEstadoBloqueo($identificador, $estado);

        $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE usuario = :u OR correo = :c');
        $stmt->execute([':u' => $identificador, ':c' => $identificador]);

        return [
            'bloqueado'  => true,
            'segundos'   => 0,
            'permanente' => true,
            'mensaje'    => 'Cuenta bloqueada totalmente por superar el límite de intentos fallidos. Un administrador debe habilitar tu cuenta para volver a ingresar.',
        ];
    }

    guardarEstadoBloqueo($identificador, $estado);
    $restantesEnRonda = 3 - ($fallos % 3);
    return [
        'bloqueado'  => false,
        'segundos'   => 0,
        'permanente' => false,
        'mensaje'    => "Usuario o contraseña incorrectos. Te quedan {$restantesEnRonda} intento(s) antes del bloqueo temporal.",
    ];
}

/**
 * Limpia el historial de intentos fallidos (al iniciar sesión con éxito o cuando el admin habilita la cuenta).
 */
function limpiarIntentosLogin(string $identificador): void
{
    if (trim($identificador) === '') {
        return;
    }
    $archivo = rutaEstadoBloqueo($identificador);
    if (file_exists($archivo)) {
        @unlink($archivo);
    }
}