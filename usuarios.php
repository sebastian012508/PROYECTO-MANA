<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/AdminController.php';

$controller = new AdminController(new AdminModel($pdo));
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = $controller->crearConductor($_POST);
}
$users = $controller->conductores();
$pageTitle = 'Conductores';
require __DIR__ . '/../layouts/header.php';
?>
<h1 class="fw-bold">Conductores</h1><p class="muted">Crea cuentas para que cada conductor pueda iniciar sesión.</p>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="row g-4">
 <div class="col-lg-5"><div class="card panel-card p-4"><h5>Registrar conductor</h5><form method="post">
  <label class="form-label">Nombre completo</label><input class="form-control mb-3" name="nombre" required>
  <label class="form-label">Usuario</label><input class="form-control mb-3" name="usuario" required>
  <label class="form-label">Correo (obligatorio)</label><input class="form-control mb-3" type="email" name="correo" required>
  <label class="form-label">Contraseña (mínimo 8 caracteres)</label><input class="form-control mb-3" type="password" name="password" minlength="8" required>
  <button class="btn btn-vigia" type="submit">Crear cuenta</button>
 </form></div></div>
 <div class="col-lg-7"><div class="card panel-card p-4"><h5>Usuarios registrados</h5><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Estado</th></tr></thead><tbody>
 <?php foreach ($users as $u): ?><tr><td><?= htmlspecialchars($u['nombre']) ?></td><td><?= htmlspecialchars($u['usuario']) ?></td><td><?= htmlspecialchars($u['correo'] ?? '—') ?></td><td><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></td></tr><?php endforeach; ?>
 <?php if (!$users): ?><tr><td colspan="4" class="text-center muted">No hay conductores registrados.</td></tr><?php endif; ?>
 </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
