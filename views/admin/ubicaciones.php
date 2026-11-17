<?php
// Endpoint: devuelve en JSON la última ubicación de cada conductor (solo ADMIN).
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/models/UbicacionModel.php';

// Solo lectura de sesión: la liberamos para no bloquear otras peticiones
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $conductores = (new UbicacionModel($pdo))->ultimasPorConductor(360, 30);
    echo json_encode(['ok' => true, 'conductores' => $conductores], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('[VIGIA MAPA] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No se pudo leer ubicaciones. ¿Ya creaste la tabla ubicaciones?'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[VIGIA MAPA] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.'], JSON_UNESCAPED_UNICODE);
}