<?php
// Endpoint: el conductor envía su ubicación (JSON por POST).
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/models/UbicacionModel.php';

// Por si auth.php no inició la sesión
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $codigo, bool $ok, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(['ok' => $ok, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, false, 'Método no permitido.');
}

$datos = json_decode(file_get_contents('php://input'), true);
if (
    !is_array($datos)
    || !isset($datos['lat'], $datos['lng'])
    || !is_numeric($datos['lat'])
    || !is_numeric($datos['lng'])
) {
    responder(400, false, 'Ubicación inválida.');
}

$lat = (float)$datos['lat'];
$lng = (float)$datos['lng'];
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    responder(400, false, 'Coordenadas fuera de rango.');
}
$precision = (isset($datos['precision']) && is_numeric($datos['precision']))
    ? (float)$datos['precision']
    : null;

// Evita saturar la base si alguien envía demasiado seguido (mínimo 5 s entre envíos).
$ahora = time();
if (isset($_SESSION['ult_ubicacion']) && ($ahora - (int)$_SESSION['ult_ubicacion']) < 5) {
    responder(429, false, 'Demasiado rápido, espera unos segundos.');
}

try {
    (new UbicacionModel($pdo))->guardar((int)$_SESSION['id_usuario'], $lat, $lng, $precision);
    // Se marca el tiempo solo si el guardado fue exitoso
    $_SESSION['ult_ubicacion'] = $ahora;
    responder(200, true, 'Ubicación guardada.');
} catch (PDOException $e) {
    error_log('[VIGIA UBICACION] ' . $e->getMessage());
    responder(500, false, 'No se pudo guardar la ubicación. ¿Ya creaste la tabla ubicaciones?');
} catch (Throwable $e) {
    error_log('[VIGIA UBICACION] ' . $e->getMessage());
    responder(500, false, 'Error interno del servidor.');
}