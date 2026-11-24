<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/HistorialController.php';

require_once __DIR__ . '/../../app/controllers/ReporteController.php';

$controller = new HistorialController(new HistorialModel($pdo));
$rows = $controller->listarSesiones((int)$_SESSION['id_usuario']);
$vehiculosReporte = (new ReporteController(new ReporteModel($pdo)))->opciones()['vehiculos'];

function formatoDuracion(?int $segundos): string
{
    if ($segundos === null) return '—';
    $segundos = max(0, $segundos);
    $horas = intdiv($segundos, 3600);
    $minutos = intdiv($segundos % 3600, 60);
    $resto = $segundos % 60;
    return sprintf('%02d:%02d:%02d', $horas, $minutos, $resto);
}

$pageTitle = 'Mi historial';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos estáticos con los colores del landing page -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/conductor_neon.css">

<div class="conductor-landing-static">
<h1 class="fw-bold">Mi historial</h1>
<p class="muted">Sesiones de monitoreo y alertas registradas en la base de datos para tu cuenta.</p>
<div class="card panel-card p-3">
 <div class="table-responsive">
  <table class="table align-middle">
   <thead><tr><th>Sesión</th><th>Inicio</th><th>Fin</th><th>Duración</th><th>Alertas</th><th>Estado</th><th>Detalle</th></tr></thead>
   <tbody>
   <?php foreach ($rows as $s): ?>
    <tr>
     <td>#<?= (int)$s['id_sesion'] ?></td>
     <td><?= htmlspecialchars($s['fecha_inicio']) ?></td>
     <td><?= htmlspecialchars($s['fecha_fin'] ?? 'En curso') ?></td>
     <td><?= htmlspecialchars(formatoDuracion($s['duracion_segundos'] === null ? null : (int)$s['duracion_segundos'])) ?></td>
     <td><?= (int)$s['cantidad_alertas'] ?></td>
     <td><span class="pill"><?= htmlspecialchars($s['estado']) ?></span></td>
     <td><a href="alertas.php?id=<?= (int)$s['id_sesion'] ?>">Ver alertas</a></td>
    </tr>
   <?php endforeach; ?>
   <?php if (!$rows): ?><tr><td colspan="7" class="text-center muted py-4">Todavía no tienes sesiones registradas.</td></tr><?php endif; ?>
   </tbody>
  </table>
 </div>
</div>
<div class="card panel-card p-4 mt-4">
 <h4 class="mb-1">Descargar mis eventos</h4>
 <p class="muted">Descarga en PDF el listado de tus eventos de somnolencia, con todos los tipos de alerta y severidades. Deja los filtros vacíos para incluir todo tu historial.</p>
 <form method="get" action="../reportes/eventos.php" class="row g-3">
  <div class="col-md-3">
   <label class="form-label" for="desde">Desde</label>
   <input type="date" id="desde" name="desde" class="form-control">
  </div>
  <div class="col-md-3">
   <label class="form-label" for="hasta">Hasta</label>
   <input type="date" id="hasta" name="hasta" class="form-control">
  </div>
  <div class="col-md-6">
   <label class="form-label" for="vehiculo">Placa</label>
   <select id="vehiculo" name="vehiculo" class="form-select">
    <option value="">Todas</option>
    <?php foreach ($vehiculosReporte as $v): ?>
     <option value="<?= (int)$v['id_vehiculo'] ?>"><?= htmlspecialchars($v['placa']) ?></option>
    <?php endforeach; ?>
   </select>
  </div>
  <div class="col-12">
   <button type="submit" class="btn btn-vigia">Descargar PDF</button>
  </div>
 </form>
</div>
<a class="btn btn-outline-secondary mt-3" href="index.php">Volver al panel</a>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>