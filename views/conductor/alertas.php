<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/HistorialController.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$controller = new HistorialController(new HistorialModel($pdo));
$rows = $id > 0
    ? $controller->listarAlertas($id, (int)$_SESSION['id_usuario'])
    : [];
$pageTitle = 'Alertas de sesión';
require __DIR__ . '/../layouts/header.php';
?>
<h1 class="fw-bold">Alertas de la sesión #<?= (int)$id ?></h1>
<p class="muted">Eventos de somnolencia registrados durante esta sesión. Cada evento corresponde a un cierre prolongado de ojos; el pitido puede repetirse mientras continúe el cierre.</p>
<div class="card panel-card p-3">
 <div class="table-responsive">
  <table class="table">
   <thead><tr><th>ID alerta</th><th>Fecha y hora</th><th>Valor EAR</th><th>Tiempo de cierre</th></tr></thead>
   <tbody>
   <?php foreach ($rows as $a): ?>
    <tr><td>#<?= (int)$a['id_alerta'] ?></td><td><?= htmlspecialchars($a['fecha_hora']) ?></td><td><?= htmlspecialchars($a['ear']) ?></td><td><?= htmlspecialchars($a['duracion_cierre_segundos']) ?> s</td></tr>
   <?php endforeach; ?>
   <?php if (!$rows): ?><tr><td colspan="4" class="text-center muted">No hay alertas para esta sesión o no te pertenece.</td></tr><?php endif; ?>
   </tbody>
  </table>
 </div>
</div>
<a class="btn btn-outline-secondary mt-3" href="historial.php">Volver al historial</a>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
