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
        string $categoria,
        ?string $color = null,
        ?string $imagen = null,
        ?int $idConductor = null
    ): void {
        $this->db->beginTransaction();
        try {
            if ($idConductor !== null && $idConductor > 0) {
                // Liberar vehículo anterior si ese conductor ya tenía uno asignado
                $liberar = $this->db->prepare(
                    "UPDATE vehiculos SET id_cliente_seleccion = NULL, disponibilidad = 'Disponible'
                     WHERE id_cliente_seleccion = ?"
                );
                $liberar->execute([$idConductor]);

                // Si se asigna directamente a un conductor, su estado pasa a 'En uso'
                if ($disponibilidad === 'Disponible') {
                    $disponibilidad = 'En uso';
                }
            } else {
                $idConductor = null;
            }

            $st = $this->db->prepare(
                "INSERT INTO vehiculos
                 (codigo_vehiculo, placa, tamano, modelo, color, imagen, capacidad, disponibilidad, categoria, id_cliente_seleccion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $st->execute([
                $codigo,
                $placa,
                $tamano,
                $modelo,
                $color !== '' ? $color : null,
                $imagen,
                $capacidad,
                $disponibilidad,
                $categoria,
                $idConductor,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function listarTodos(): array
    {
        $sql = "SELECT v.*, u.nombre AS conductor_nombre
                FROM vehiculos v
                LEFT JOIN usuarios u ON u.id_usuario = v.id_cliente_seleccion
                ORDER BY v.fecha_registro DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function listarConductoresActivos(): array
    {
        $sql = "SELECT id_usuario, nombre, usuario
                FROM usuarios
                WHERE rol = 'CONDUCTOR' AND activo = 1
                ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function asignarConductorDirecto(int $idVehiculo, ?int $idConductor): void
    {
        $this->db->beginTransaction();
        try {
            if ($idConductor !== null && $idConductor > 0) {
                // Liberar vehículo anterior del conductor
                $liberarPrevio = $this->db->prepare(
                    "UPDATE vehiculos SET id_cliente_seleccion = NULL, disponibilidad = 'Disponible'
                     WHERE id_cliente_seleccion = ? AND id_vehiculo <> ?"
                );
                $liberarPrevio->execute([$idConductor, $idVehiculo]);

                // Asignar este vehículo al conductor
                $asignar = $this->db->prepare(
                    "UPDATE vehiculos SET id_cliente_seleccion = ?, disponibilidad = 'En uso'
                     WHERE id_vehiculo = ?"
                );
                $asignar->execute([$idConductor, $idVehiculo]);
            } else {
                // Dejar vehículo sin conductor asignado
                $desasignar = $this->db->prepare(
                    "UPDATE vehiculos SET id_cliente_seleccion = NULL,
                            disponibilidad = CASE WHEN disponibilidad = 'En uso' THEN 'Disponible' ELSE disponibilidad END
                     WHERE id_vehiculo = ?"
                );
                $desasignar->execute([$idVehiculo]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
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
        $liberar = $this->db->prepare(
            "UPDATE vehiculos SET id_cliente_seleccion = NULL, disponibilidad = 'Disponible'
             WHERE id_cliente_seleccion = ?"
        );
        $liberar->execute([$idConductor]);

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