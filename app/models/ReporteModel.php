<?php
/** Consultas para el reporte de eventos de somnolencia. */
class ReporteModel
{
    public function __construct(private PDO $db) {}

    public function eventos(array $filtros): array
    {
        $sql = 'SELECT a.id_alerta, a.fecha_hora, a.duracion_cierre_segundos,
                       u.nombre AS chofer, v.placa
                FROM alertas a
                INNER JOIN sesiones s ON s.id_sesion = a.id_sesion
                INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
                LEFT JOIN vehiculos v ON v.id_vehiculo = s.id_vehiculo
                WHERE 1 = 1';
        $parametros = [];

        if (!empty($filtros['desde'])) {
            $sql .= ' AND a.fecha_hora >= ?';
            $parametros[] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $sql .= ' AND a.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)';
            $parametros[] = $filtros['hasta'];
        }
        if (!empty($filtros['id_usuario'])) {
            $sql .= ' AND s.id_usuario = ?';
            $parametros[] = (int)$filtros['id_usuario'];
        }
        if (!empty($filtros['id_vehiculo'])) {
            $sql .= ' AND s.id_vehiculo = ?';
            $parametros[] = (int)$filtros['id_vehiculo'];
        }
        $sql .= ' ORDER BY a.fecha_hora DESC, a.id_alerta DESC';
        $st = $this->db->prepare($sql);
        $st->execute($parametros);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function conductores(): array
    {
        return $this->db->query(
            "SELECT id_usuario, nombre FROM usuarios WHERE rol = 'CONDUCTOR' ORDER BY nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function vehiculos(): array
    {
        return $this->db->query(
            'SELECT id_vehiculo, placa, codigo_vehiculo FROM vehiculos ORDER BY placa'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function nombreUsuario(int $idUsuario): ?string
    {
        $st = $this->db->prepare('SELECT nombre FROM usuarios WHERE id_usuario = ?');
        $st->execute([$idUsuario]);
        $nombre = $st->fetchColumn();
        return $nombre === false ? null : (string)$nombre;
    }

    public function placaVehiculo(int $idVehiculo): ?string
    {
        $st = $this->db->prepare('SELECT placa FROM vehiculos WHERE id_vehiculo = ?');
        $st->execute([$idVehiculo]);
        $placa = $st->fetchColumn();
        return $placa === false ? null : (string)$placa;
    }

    public function fechaHoraActual(): string
    {
        return (string)$this->db->query('SELECT NOW()')->fetchColumn();
    }
}
