<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_login();
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/ReporteController.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['ADMIN', 'CONDUCTOR'], true)) {
    http_response_code(403);
    exit('No tienes permisos para acceder a esta sección.');
}

$model = new ReporteModel($pdo);
$controller = new ReporteController($model);
$idUsuario = (int)$_SESSION['id_usuario'];
$reporte = $controller->generar($_GET, $rol === 'CONDUCTOR' ? $idUsuario : null);

$nombreUsuario = $model->nombreUsuario($idUsuario) ?? ($_SESSION['nombre'] ?? '');
$etiquetaUsuario = $rol === 'ADMIN' ? 'Administrador' : 'Conductor';

ob_start();
require __DIR__ . '/plantilla_eventos.php';
$html = ob_get_clean();

$opciones = new Options();
$opciones->set('defaultFont', 'DejaVu Sans');
$opciones->set('isRemoteEnabled', false);
$opciones->setChroot(__DIR__);

$dompdf = new Dompdf($opciones);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nombreArchivo = 'eventos_somnolencia_' . str_replace(['/', ':', ' '], ['-', '-', '_'], $reporte['generado']) . '.pdf';
$dompdf->stream($nombreArchivo, ['Attachment' => true]);
exit;
