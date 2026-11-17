<?php
/** Acceso a las tablas sesiones y alertas. */
class HistorialModel
{
    public function __construct(private PDO $db) {}

    public function contarSesiones(int $idUsuario): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM sesiones WHERE id_usuario = ?');
        $st->execute([$idUsuario]);
        return (int)$st->fetchColumn();
    }

    public function contarAlertasDeUsuario(int $idUsuario): int
    {
        $sql = 'SELECT COUNT(*) FROM alertas a
                INNER JOIN sesiones s ON s.id_sesion = a.id_sesion
                WHERE s.id_usuario = ?';
        $st = $this->db->prepare($sql);
        $st->execute([$idUsuario]);
        return (int)$st->fetchColumn();
    }

    public function sesionesDeUsuario(int $idUsuario, int $limite = 100): array
    {
        $limite = max(1, min($limite, 500));
        // LIMIT no se enlaza de forma portable en todas las versiones de MariaDB;
        // el valor se limita y convierte a entero antes de incorporarlo.
        $sql = 'SELECT id_sesion, fecha_inicio, fecha_fin, duracion_segundos,
                       cantidad_alertas, estado
                FROM sesiones
                WHERE id_usuario = ?
                ORDER BY fecha_inicio DESC
                LIMIT ' . $limite;
        $st = $this->db->prepare($sql);
        $st->execute([$idUsuario]);
        return $st->fetchAll();
    }

    public function alertasDeSesion(int $idSesion, int $idUsuario): array
    {
        $sql = 'SELECT a.id_alerta, a.fecha_hora, a.ear,
                       a.duracion_cierre_segundos
                FROM alertas a
                INNER JOIN sesiones s ON s.id_sesion = a.id_sesion
                WHERE s.id_sesion = ? AND s.id_usuario = ?
                ORDER BY a.fecha_hora DESC';
        $st = $this->db->prepare($sql);
        $st->execute([$idSesion, $idUsuario]);
        return $st->fetchAll();
    }
}
