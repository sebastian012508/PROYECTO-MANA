<?php
/** Consultas y escrituras para gestión de vehículos y reportes de fallas. */
class VehiculoModel
{
    public function __construct(private PDO $db) {}

    public function registrarVehiculo(
        string $codigo,
        string $placa,
        string $tamano,
        string $modelo,
        string $capacidad,
        string $disponibilidad,
        string $categoria
    ): void {
        $st = $this->db->prepare(
            "INSERT INTO vehiculos (codigo_vehiculo, placa, tamano, modelo, capacidad, disponibilidad, categoria)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$codigo, $placa, $tamano, $modelo, $capacidad, $disponibilidad, $categoria]);
    }

    public function listarTodos(): array
    {
        $sql = "SELECT v.*, u.nombre AS conductor_nombre
                FROM vehiculos v
                LEFT JOIN usuarios u ON u.id_usuario = v.id_cliente_seleccion
                ORDER BY v.fecha_registro DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function vehiculoActualConductor(int $idConductor): ?array
    {
        $st = $this->db->prepare("SELECT * FROM vehiculos WHERE id_cliente_seleccion = ? LIMIT 1");
        $st->execute([$idConductor]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function seleccionarVehiculo(int $idVehiculo, int $idConductor): bool
    {
        // Liberar vehículo anterior si el chofer ya tenía uno asignado
        $liberar = $this->db->prepare(
            "UPDATE vehiculos SET id_cliente_seleccion = NULL, disponibilidad = 'Disponible'
             WHERE id_cliente_seleccion = ?"
        );
        $liberar->execute([$idConductor]);

        // Asignar el nuevo vehículo seleccionado si está Disponible
        $asignar = $this->db->prepare(
            "UPDATE vehiculos SET id_cliente_seleccion = ?, disponibilidad = 'En uso'
             WHERE id_vehiculo = ? AND disponibilidad = 'Disponible'"
        );
        $asignar->execute([$idConductor, $idVehiculo]);
        return $asignar->rowCount() > 0;
    }

    public function liberarVehiculo(int $idConductor): void
    {
        $st = $this->db->prepare(
            "UPDATE vehiculos SET id_cliente_seleccion = NULL, disponibilidad = 'Disponible'
             WHERE id_cliente_seleccion = ?"
        );
        $st->execute([$idConductor]);
    }

    public function registrarReporte(int $idVehiculo, int $idConductor, string $tipoProblema, string $descripcion): void
    {
        $st = $this->db->prepare(
            "INSERT INTO reportes_vehiculo (id_vehiculo, id_conductor, tipo_problema, descripcion)
             VALUES (?, ?, ?, ?)"
        );
        $st->execute([$idVehiculo, $idConductor, $tipoProblema, $descripcion]);
    }

    public function listarReportesRecientes(int $limite = 20): array
    {
        $limite = max(1, min($limite, 100));
        $sql = "SELECT r.*, v.codigo_vehiculo, v.placa, v.modelo, u.nombre AS conductor_nombre
                FROM reportes_vehiculo r
                INNER JOIN vehiculos v ON v.id_vehiculo = r.id_vehiculo
                INNER JOIN usuarios u ON u.id_usuario = r.id_conductor
                ORDER BY r.fecha_reporte DESC LIMIT " . $limite;
        return $this->db->query($sql)->fetchAll();
    }
}