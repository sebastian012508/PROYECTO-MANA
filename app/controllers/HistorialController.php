<?php
require_once __DIR__ . '/../models/HistorialModel.php';

/** Controlador de las consultas del historial del conductor. */
class HistorialController
{
    public function __construct(private HistorialModel $model) {}

    public function resumen(int $idUsuario): array
    {
        return [
            'sesiones' => $this->model->contarSesiones($idUsuario),
            'alertas' => $this->model->contarAlertasDeUsuario($idUsuario),
        ];
    }

    public function listarSesiones(int $idUsuario): array
    {
        return $this->model->sesionesDeUsuario($idUsuario);
    }

    public function listarAlertas(int $idSesion, int $idUsuario): array
    {
        return $this->model->alertasDeSesion($idSesion, $idUsuario);
    }
}
