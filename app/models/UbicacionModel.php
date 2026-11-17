<?php
/** Guarda y consulta las ubicaciones que envían los conductores. */
class UbicacionModel
{
    public function __construct(private PDO $db) {}

    public function guardar(int $idUsuario, float $lat, float $lng, ?float $precision): void
    {
        $st = $this->db->prepare(
            'INSERT INTO ubicaciones (id_usuario, latitud, longitud, precision_m) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$idUsuario, $lat, $lng, $precision]);

        // Limpieza ocasional: no guardamos recorridos de más de 7 días.
        if (random_int(1, 100) === 1) {
            $this->db->exec('DELETE FROM ubicaciones WHERE fecha_hora < NOW() - INTERVAL 7 DAY');
        }
    }

    /**
     * Última posición de cada conductor que haya enviado ubicación
     * dentro de la ventana de tiempo, más su recorrido reciente.
     */
    public function ultimasPorConductor(int $minutos = 360, int $puntosRuta = 30): array
    {
        $minutos = max(1, min($minutos, 10080));
        $puntosRuta = max(1, min($puntosRuta, 200));

        $sql = "SELECT u.id_usuario, u.nombre, l.latitud, l.longitud, l.precision_m, l.fecha_hora,
                       TIMESTAMPDIFF(SECOND, l.fecha_hora, NOW()) AS segundos
                FROM usuarios u
                INNER JOIN ubicaciones l ON l.id_ubicacion = (
                    SELECT MAX(l2.id_ubicacion) FROM ubicaciones l2 WHERE l2.id_usuario = u.id_usuario
                )
                WHERE u.rol = 'CONDUCTOR' AND l.fecha_hora >= NOW() - INTERVAL $minutos MINUTE
                ORDER BY u.nombre";
        $conductores = $this->db->query($sql)->fetchAll();

        $stRuta = $this->db->prepare(
            "SELECT latitud, longitud FROM ubicaciones
             WHERE id_usuario = ? AND fecha_hora >= NOW() - INTERVAL $minutos MINUTE
             ORDER BY id_ubicacion DESC LIMIT $puntosRuta"
        );

        foreach ($conductores as &$c) {
            $stRuta->execute([(int)$c['id_usuario']]);
            $puntos = array_reverse($stRuta->fetchAll());
            $c['ruta'] = array_map(
                fn($p) => [(float)$p['latitud'], (float)$p['longitud']],
                $puntos
            );
            $c['latitud'] = (float)$c['latitud'];
            $c['longitud'] = (float)$c['longitud'];
            $c['segundos'] = (int)$c['segundos'];
            $c['precision_m'] = $c['precision_m'] === null ? null : (float)$c['precision_m'];
        }
        unset($c);

        return $conductores;
    }
}