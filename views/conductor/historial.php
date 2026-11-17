<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/HistorialController.php';

$controller = new HistorialController(new HistorialModel($pdo));
$rows = $controller->listarSesiones((int)$_SESSION['id_usuario']);

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
<a class="btn btn-outline-secondary mt-3" href="index.php">Volver al panel</a>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
