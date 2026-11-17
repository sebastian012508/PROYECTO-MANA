<?php
/** Consultas y escrituras del panel de administración. */
class AdminModel
{
    public function __construct(private PDO $db) {}

    public function resumen(): array
    {
        return [
            'conductores' => (int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'CONDUCTOR'")->fetchColumn(),
            'sesiones' => (int)$this->db->query('SELECT COUNT(*) FROM sesiones')->fetchColumn(),
            'alertas' => (int)$this->db->query('SELECT COUNT(*) FROM alertas')->fetchColumn(),
        ];
    }

    public function sesionesRecientes(int $limite = 20): array
    {
        $limite = max(1, min($limite, 100));
        $sql = 'SELECT s.id_sesion, u.nombre, s.fecha_inicio, s.fecha_fin,
                       s.duracion_segundos, s.cantidad_alertas, s.estado
                FROM sesiones s
                INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
                ORDER BY s.fecha_inicio DESC LIMIT ' . $limite;
        return $this->db->query($sql)->fetchAll();
    }

    public function conductores(): array
    {
        return $this->db->query(
            "SELECT id_usuario, nombre, usuario, correo, activo, fecha_creacion
             FROM usuarios WHERE rol = 'CONDUCTOR' ORDER BY nombre"
        )->fetchAll();
    }

    public function insertarConductor(string $nombre, string $usuario, ?string $correo, string $password): void
    {
        $st = $this->db->prepare(
            "INSERT INTO usuarios (nombre, usuario, correo, password_hash, rol)
             VALUES (?, ?, ?, ?, 'CONDUCTOR')"
        );
        $st->execute([$nombre, $usuario, $correo, password_hash($password, PASSWORD_DEFAULT)]);
    }
}
