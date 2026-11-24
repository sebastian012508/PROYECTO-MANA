<?php
require_once __DIR__ . '/../models/ReporteModel.php';

/** Arma el reporte de eventos de somnolencia (lo usan el administrador y el conductor). */
class ReporteController
{
    // Severidad según la duración del cierre de ojos (segundos).
    public const LIMITE_MEDIO = 1.0;
    public const LIMITE_ALTO  = 3.0;

    public function __construct(private ReporteModel $model) {}

    public static function severidad(float $segundos): string
    {
        if ($segundos >= self::LIMITE_ALTO) return 'Alto';
        if ($segundos >= self::LIMITE_MEDIO) return 'Medio';
        return 'Bajo';
    }

    public static function tipo(float $segundos): string
    {
        return $segundos >= self::LIMITE_ALTO ? 'Microsueño' : 'Somnolencia';
    }

    public function opciones(): array
    {
        return [
            'conductores' => $this->model->conductores(),
            'vehiculos'   => $this->model->vehiculos(),
        ];
    }

    /**
     * @param array    $entrada        Filtros recibidos por GET.
     * @param int|null $soloConductor  Si viene, el reporte se limita a ese conductor.
     */
    public function generar(array $entrada, ?int $soloConductor = null): array
    {
        $filtros = [];
        $aplicados = [];

        $desde = $this->fechaValida($entrada['desde'] ?? '');
        $hasta = $this->fechaValida($entrada['hasta'] ?? '');
        if ($desde && $hasta && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        if ($desde || $hasta) {
            $filtros['desde'] = $desde;
            $filtros['hasta'] = $hasta;
            $aplicados['Rango de fechas'] = ($desde ? $this->fechaCorta($desde) : 'Inicio')
                . ' — ' . ($hasta ? $this->fechaCorta($hasta) : 'Hoy');
        }

        $idChofer = $soloConductor ?? (int)($entrada['chofer'] ?? 0);
        if ($idChofer > 0) {
            $nombre = $this->model->nombreUsuario($idChofer);
            if ($nombre !== null) {
                $filtros['id_usuario'] = $idChofer;
                if ($soloConductor === null) {
                    $aplicados['Chofer'] = $nombre;
                }
            }
        }

        $idVehiculo = (int)($entrada['vehiculo'] ?? 0);
        if ($idVehiculo > 0) {
            $placa = $this->model->placaVehiculo($idVehiculo);
            if ($placa !== null) {
                $filtros['id_vehiculo'] = $idVehiculo;
                $aplicados['Placa del vehículo'] = $placa;
            }
        }

        $eventos = [];
        $resumen = ['total' => 0, 'Alto' => 0, 'Medio' => 0, 'Bajo' => 0];
        foreach ($this->model->eventos($filtros) as $fila) {
            $segundos = (float)$fila['duracion_cierre_segundos'];
            $sev = self::severidad($segundos);
            $eventos[] = [
                'id'        => (int)$fila['id_alerta'],
                'fecha'     => date('d/m/Y H:i:s', strtotime($fila['fecha_hora'])),
                'chofer'    => $fila['chofer'],
                'placa'     => $fila['placa'] ?: 'Sin asignar',
                'tipo'      => self::tipo($segundos),
                'severidad' => $sev,
                'duracion'  => number_format($segundos, 2, '.', ''),
            ];
            $resumen['total']++;
            $resumen[$sev]++;
        }

        return [
            'eventos'   => $eventos,
            'aplicados' => $aplicados,
            'resumen'   => $resumen,
            'generado'  => date('d/m/Y H:i:s', strtotime($this->model->fechaHoraActual())),
        ];
    }

    private function fechaValida(string $valor): ?string
    {
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        return ($fecha && $fecha->format('Y-m-d') === $valor) ? $valor : null;
    }

    private function fechaCorta(string $fecha): string
    {
        return DateTime::createFromFormat('Y-m-d', $fecha)->format('d/m/Y');
    }
}
