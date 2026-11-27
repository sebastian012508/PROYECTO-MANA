<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$ultimoId = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : -1;

try {
    // Primera carga: devolver solo el ID máximo actual para no disparar alertas viejas
    if ($ultimoId < 0) {
        $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id_alerta), 0) FROM alertas")->fetchColumn();
        $totalAlertas = (int)$pdo->query("SELECT COUNT(*) FROM alertas")->fetchColumn();

        echo json_encode([
            'ok'            => true,
            'ultimo_id'     => $maxId,
            'total_alertas' => $totalAlertas,
            'nuevas'        => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Detectar columnas reales de la tabla alertas para evitar error 500 por nombres distintos
    $columnasAlertas = $pdo->query("DESCRIBE alertas")->fetchAll(PDO::FETCH_COLUMN);

    $colFecha = null;
    foreach (['fecha_hora', 'fecha_alerta', 'created_at', 'fecha', 'fecha_registro'] as $candidata) {
        if (in_array($candidata, $columnasAlertas, true)) {
            $colFecha = $candidata;
            break;
        }
    }

    $colDuracion = null;
    foreach (['duracion_segundos', 'segundos_cerrados', 'duracion', 'tiempo_cierre'] as $candidata) {
        if (in_array($candidata, $columnasAlertas, true)) {
            $colDuracion = $candidata;
            break;
        }
    }

    $selectFecha    = $colFecha ? "a.`{$colFecha}` AS fecha_raw" : "NOW() AS fecha_raw";
    $selectDuracion = $colDuracion ? "COALESCE(a.`{$colDuracion}`, 0) AS duracion_raw" : "0 AS duracion_raw";

    $sql = "SELECT
                a.id_alerta,
                {$selectFecha},
                {$selectDuracion},
                u.nombre AS nombre_conductor,
                COALESCE(v.placa, 'Sin vehículo') AS placa_vehiculo
            FROM alertas a
            INNER JOIN sesiones s ON s.id_sesion = a.id_sesion
            INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
            LEFT JOIN vehiculos v ON v.id_cliente_seleccion = u.id_usuario
            WHERE a.id_alerta > ?
            ORDER BY a.id_alerta ASC
            LIMIT 10";

    $st = $pdo->prepare($sql);
    $st->execute([$ultimoId]);
    $filas = $st->fetchAll(PDO::FETCH_ASSOC);

    $nuevas = [];
    $nuevoMaxId = $ultimoId;

    foreach ($filas as $fila) {
        $idAlerta = (int)$fila['id_alerta'];
        $duracion = (float)$fila['duracion_raw'];

        if ($duracion >= 2.0) {
            $nivel = 'Microsueño (Alto)';
        } elseif ($duracion > 0) {
            $nivel = 'Somnolencia (' . round($duracion, 1) . ' s)';
        } else {
            $nivel = 'Alerta de somnolencia activada';
        }

        $nuevas[] = [
            'id_alerta'        => $idAlerta,
            'nombre_conductor' => (string)$fila['nombre_conductor'],
            'placa_vehiculo'   => (string)$fila['placa_vehiculo'],
            'nivel_alerta'     => $nivel,
            'hora_formateada'  => date('H:i:s', strtotime((string)$fila['fecha_raw'])),
        ];

        if ($idAlerta > $nuevoMaxId) {
            $nuevoMaxId = $idAlerta;
        }
    }

    $totalAlertas = (int)$pdo->query("SELECT COUNT(*) FROM alertas")->fetchColumn();

    echo json_encode([
        'ok'            => true,
        'ultimo_id'     => $nuevoMaxId,
        'total_alertas' => $totalAlertas,
        'nuevas'        => $nuevas,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[MANA REALTIME ALERTAS] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => 'Error al consultar alertas en tiempo real.',
        'detalle' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}