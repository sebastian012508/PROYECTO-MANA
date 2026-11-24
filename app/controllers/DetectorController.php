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

        if ($accion === 'start') {
            if (!$this->apiDisponible() && !$this->encenderApi()) {
                return ['ok' => false, 'message' => 'No se pudo encender el detector de Python. Verifica que exista detector_api/.venv.'];
            }
            return $this->enviar('start', $idUsuario);
        }

        $resultado = $this->apiDisponible()
            ? $this->enviar('stop')
            : ['ok' => true, 'message' => 'El detector ya estaba apagado.'];
        if ($this->apagarApi()) {
            return ['ok' => true, 'message' => 'Monitoreo detenido y cámara apagada.'];
        }
        return $resultado;
    }

    private function archivoPid(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mana_detector.pid';
    }

    private function apiDisponible(): bool
    {
        $ch = curl_init(PYTHON_API_URL . '/status');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 1, CURLOPT_CONNECTTIMEOUT => 1]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false;
    }

    private function encenderApi(): bool
    {
        $carpeta = realpath(__DIR__ . '/../../detector_api');
        $python = $carpeta ? $carpeta . '\\.venv\\Scripts\\pythonw.exe' : '';
        if ($python === '' || !is_file($python)) {
            return false;
        }

        $comillas = static fn(string $texto): string => "'" . str_replace("'", "''", $texto) . "'";
        $script = '$p = Start-Process -FilePath ' . $comillas($python)
            . ' -ArgumentList ' . $comillas('app.py')
            . ' -WorkingDirectory ' . $comillas($carpeta)
            . ' -WindowStyle Hidden -PassThru; $p.Id';
        $pid = (int)trim((string)shell_exec('powershell -NoProfile -NonInteractive -Command "' . $script . '"'));
        if ($pid <= 0) {
            return false;
        }
        file_put_contents($this->archivoPid(), (string)$pid);

        set_time_limit(90);
        $limite = time() + 60;
        while (time() < $limite) {
            if ($this->apiDisponible()) {
                return true;
            }
            usleep(500000);
        }
        return false;
    }

    private function apagarApi(): bool
    {
        $archivo = $this->archivoPid();
        $pid = is_file($archivo) ? (int)trim((string)file_get_contents($archivo)) : 0;
        if ($pid <= 0) {
            return false;
        }
        $proceso = (string)shell_exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH');
        if (stripos($proceso, 'pythonw.exe') !== false) {
            shell_exec('taskkill /PID ' . $pid . ' /T /F');
        }
        @unlink($archivo);
        return true;
    }

    private function enviar(string $accion, int $idUsuario = 0): array
    {
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
