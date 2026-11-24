<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/AdminController.php';

$controller = new AdminController(new AdminModel($pdo));
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';
    if ($accion === 'cambiar_estado') {
        $msg = $controller->actualizarEstadoConductor(
            (int)($_POST['id_usuario'] ?? 0),
            (int)($_POST['nuevo_estado'] ?? 1)
        );
    } else {
        $msg = $controller->crearConductor($_POST);
    }
}
$users = $controller->conductores();
$pageTitle = 'Conductores';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos Neón estáticos (solo colores, sin animaciones) -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/admin_neon.css">

<div class="admin-neon-static">
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
 <div><h1 class="fw-bold">Conductores</h1><p class="muted mb-0">Crea cuentas para que cada conductor pueda iniciar sesión.</p></div>
 <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="row g-4">
 <div class="col-lg-5"><div class="card panel-card p-4"><h5>Registrar conductor</h5><form method="post">
  <input type="hidden" name="accion" value="crear">
  <label class="form-label">Nombre completo</label><input class="form-control mb-3" name="nombre" required>
  <label class="form-label">Usuario</label><input class="form-control mb-3" name="usuario" required>
  <label class="form-label">Correo (obligatorio)</label><input class="form-control mb-3" type="email" name="correo" required>
  <label class="form-label" for="password_conductor">Contraseña segura</label>
  <div class="input-group mb-1">
    <input id="password_conductor"
           class="form-control"
           type="password"
           name="password"
           minlength="8"
           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
           title="Mínimo 8 caracteres, una mayúscula, una minúscula, un número y un signo."
           required>
    <button type="button"
            id="btn-toggle-password-admin"
            class="btn btn-outline-secondary"
            aria-label="Mostrar u ocultar contraseña"
            title="Mostrar / ocultar contraseña">
      <svg id="icono-ojo-abrir-admin" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
      </svg>
      <svg id="icono-ojo-cerrar-admin" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="d-none">
        <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
        <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
        <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
      </svg>
    </button>
  </div>
  <div class="small muted mb-3">Mínimo 8 caracteres: incluye mayúscula, minúscula, número y signo (ej. <code>Mana2026*</code>).</div>
  <button class="btn btn-vigia" type="submit">Crear cuenta</button>
 </form></div></div>
 <div class="col-lg-7"><div class="card panel-card p-4"><h5>Usuarios registrados</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
 <?php foreach ($users as $u): ?>
  <tr>
   <td><?= htmlspecialchars($u['nombre']) ?></td>
   <td><?= htmlspecialchars($u['usuario']) ?></td>
   <td><?= htmlspecialchars($u['correo'] ?? '—') ?></td>
   <td><?= $u['activo'] ? 'Activo' : '<span class="text-danger fw-bold">Bloqueado / Inactivo</span>' ?></td>
   <td>
    <form method="post" class="m-0">
     <input type="hidden" name="accion" value="cambiar_estado">
     <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
     <?php if ($u['activo']): ?>
      <input type="hidden" name="nuevo_estado" value="0">
      <button type="submit" class="btn btn-sm btn-outline-secondary">Desactivar</button>
     <?php else: ?>
      <input type="hidden" name="nuevo_estado" value="1">
      <button type="submit" class="btn btn-sm btn-vigia">Habilitar</button>
     <?php endif; ?>
    </form>
   </td>
  </tr>
 <?php endforeach; ?>
 <?php if (!$users): ?><tr><td colspan="5" class="text-center muted">No hay conductores registrados.</td></tr><?php endif; ?>
 </tbody></table></div></div></div>
</div>
</div>

<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/admin_password_toggle.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>