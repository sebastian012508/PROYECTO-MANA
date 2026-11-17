<?php
// Configuración común del proyecto.
if (!defined('APP_NAME')) {
    define('APP_NAME', 'MANA');
}
if (!defined('PYTHON_API_URL')) {
    define('PYTHON_API_URL', 'http://127.0.0.1:5001');
}
if (!defined('API_SECRET')) {
    // Debe coincidir exactamente con detector_api/config.py.
    define('API_SECRET', 'CAMBIA_ESTA_CLAVE_LARGA_EN_PHP_Y_PYTHON_2026');
}

// Calcula la carpeta base incluso si el proyecto se llama distinto en htdocs.
if (!defined('APP_BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('~^(.*?)/(?:views|public)/~', $scriptName, $matches)) {
        $base = $matches[1];
    } else {
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    }
    define('APP_BASE_URL', $base === '/' ? '' : $base);
}
