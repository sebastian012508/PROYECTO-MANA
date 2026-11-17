<?php
// Inicia una única sesión PHP con parámetros más seguros.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['id_usuario'])) {
        require_once __DIR__ . '/../../config/app.php';
        header('Location: ' . APP_BASE_URL . '/views/auth/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (($_SESSION['rol'] ?? '') !== $role) {
        http_response_code(403);
        exit('No tienes permisos para acceder a esta sección.');
    }
}
