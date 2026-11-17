<?php
require_once __DIR__ . '/../../config/app.php';

/** Comunicación PHP -> API Flask. */
class DetectorController
{
    public function estado(): string
    {
        $ch = curl_init(PYTHON_API_URL . '/status');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) return 'API Python no disponible';
        $data = json_decode($response, true) ?: [];
        return !empty($data['running']) ? 'Detector en ejecución' : 'API Python disponible';
    }

    public function ejecutar(string $accion, int $idUsuario = 0): array
    {
        if (!in_array($accion, ['start', 'stop'], true)) {
            return ['ok' => false, 'message' => 'Acción no válida.'];
        }
        $payload = $accion === 'start' ? ['user_id' => $idUsuario] : (object)[];
        $ch = curl_init(PYTHON_API_URL . '/' . $accion);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-KEY: ' . API_SECRET,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'message' => 'No se pudo contactar con Python: ' . $error];
        }
        $data = json_decode($response, true) ?: [];
        if ($http < 200 || $http >= 300) {
            return ['ok' => false, 'message' => $data['message'] ?? ('La API respondió HTTP ' . $http . '.')];
        }
        return ['ok' => !empty($data['ok']), 'message' => $data['message'] ?? 'Solicitud enviada.'];
    }
}
