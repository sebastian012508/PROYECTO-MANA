<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../app/controllers/DetectorController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$accion = $_POST['accion'] ?? '';
$resultado = (new DetectorController())->ejecutar($accion, (int)$_SESSION['id_usuario']);
$_SESSION['flash'] = $resultado['message'];
header('Location: index.php');
exit;
