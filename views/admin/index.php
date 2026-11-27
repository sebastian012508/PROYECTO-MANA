<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/AdminController.php';

$controller = new AdminController(new AdminModel($pdo));
$data = $controller->panel();
$resumen = $data['resumen'];
$sesiones = $data['sesiones'];
$pageTitle = 'Panel administrador';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos Neón exclusivos para el panel administrador -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/admin_neon.css">

<!-- Contenedor flotante derecho para notificaciones de somnolencia en tiempo real -->
<div id="contenedor-notificaciones-realtime"
     data-endpoint="api_alertas_realtime.php"
     style="position: fixed; right: 20px; top: 82px; width: 350px; max-width: calc(100vw - 32px); z-index: 1080;">
</div>

<div class="admin-neon-scope">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
 <div>
  <div class="text-uppercase small text-success fw-bold">Administración</div>
  <h1 class="fw-bold">Panel general</h1>
  <p class="muted mb-0">Resumen del sistema de monitoreo.</p>
 </div>
 <div class="d-flex flex-wrap gap-2 align-items-center">
  <button type="button" id="btn-estado-audio-alertas" class="btn btn-outline-info">Sonido de alertas: Activado</button>
  <a class="btn btn-vigia" href="usuarios.php">Gestionar conductores</a>
  <a class="btn btn-vigia" href="vehiculos.php">Gestionar Vehículos</a>
  <a class="btn btn-vigia" href="mapa.php">Mapa de conductores</a>
  <a class="btn btn-vigia" href="reportes.php">Reportes</a>
 </div>
</div>

<div class="row g-3 mb-4">
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Conductores</div><div class="display-5 fw-bold"><?= $resumen['conductores'] ?></div></div></div>
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Sesiones</div><div class="display-5 fw-bold"><?= $resumen['sesiones'] ?></div></div></div>
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Alertas</div><div id="kpi-total-alertas" class="display-5 fw-bold"><?= $resumen['alertas'] ?></div></div></div>
</div>

<div class="card panel-card p-3">
 <h5 class="mb-3">Sesiones recientes</h5>
 <div class="table-responsive">
  <table class="table align-middle">
   <thead>
    <tr>
     <th>ID</th>
     <th>Conductor</th>
     <th>Inicio</th>
     <th>Duración (s)</th>
     <th>Alertas</th>
     <th>Estado</th>
    </tr>
   </thead>
   <tbody>
    <?php foreach ($sesiones as $s): ?>
     <tr>
      <td>#<?= (int)$s['id_sesion'] ?></td>
      <td><?= htmlspecialchars($s['nombre']) ?></td>
      <td><?= htmlspecialchars($s['fecha_inicio']) ?></td>
      <td><?= $s['duracion_segundos'] === null ? '—' : (int)$s['duracion_segundos'] ?></td>
      <td><?= (int)$s['cantidad_alertas'] ?></td>
      <td><span class="pill"><?= htmlspecialchars($s['estado']) ?></span></td>
     </tr>
    <?php endforeach; ?>
    <?php if (!$sesiones): ?>
     <tr><td colspan="6" class="text-center muted">Todavía no hay sesiones registradas.</td></tr>
    <?php endif; ?>
   </tbody>
  </table>
 </div>
</div>
</div>

<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/admin_alertas_realtime.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>