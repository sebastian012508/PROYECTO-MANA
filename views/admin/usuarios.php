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
    if ($accion === 'editar') {
        $msg = $controller->editarConductor($_POST);
    } elseif ($accion === 'eliminar') {
        $msg = $controller->eliminarConductor($_POST);
    } elseif ($accion === 'cambiar_estado') {
        $msg = $controller->actualizarEstadoConductor(
            (int)($_POST['id_usuario'] ?? 0),
            (int)($_POST['nuevo_estado'] ?? 1)
        );
    } else {
        $msg = $controller->crearConductor($_POST);
    }
}
$users = $controller->conductores();
$licencias = AdminController::LICENCIAS_PERMITIDAS;
$pageTitle = 'Conductores';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos Neón estáticos (solo colores, sin animaciones) -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/admin_neon.css">

<div class="admin-neon-static">
<h1 class="fw-bold">Conductores</h1><p class="muted">Crea y administra las cuentas de los conductores registrados.</p>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="row g-4">
 <div class="col-lg-4"><div class="card panel-card p-4"><h5>Registrar conductor</h5><form method="post">
  <input type="hidden" name="accion" value="crear">
  <label class="form-label">Nombre completo</label><input class="form-control mb-3" name="nombre" required>
  <label class="form-label">Usuario</label><input class="form-control mb-3" name="usuario" required>
  <label class="form-label">Correo (obligatorio)</label><input class="form-control mb-3" type="email" name="correo" required>
  <label class="form-label">Tipo de licencia</label>
  <select class="form-select mb-3" name="tipo_licencia" required>
    <option value="">Selecciona categoría...</option>
    <?php foreach ($licencias as $lic): ?>
      <option value="<?= htmlspecialchars($lic) ?>"><?= htmlspecialchars($lic) ?></option>
    <?php endforeach; ?>
  </select>
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
  <button class="btn btn-vigia w-100" type="submit">Crear cuenta</button>
 </form></div></div>

 <div class="col-lg-8"><div class="card panel-card p-4"><h5>Usuarios registrados</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Licencia</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
 <?php foreach ($users as $u): ?>
  <tr>
   <td><?= htmlspecialchars($u['nombre']) ?></td>
   <td><?= htmlspecialchars($u['usuario']) ?></td>
   <td><?= htmlspecialchars($u['correo'] ?? '—') ?></td>
   <td><?= htmlspecialchars($u['tipo_licencia'] ?? 'Sin registrar') ?></td>
   <td><?= $u['activo'] ? 'Activo' : '<span class="text-danger fw-bold">Bloqueado / Inactivo</span>' ?></td>
   <td>
    <div class="d-flex flex-wrap gap-1">
      <button type="button"
              class="btn btn-sm btn-outline-info btn-editar-conductor"
              data-bs-toggle="modal"
              data-bs-target="#modalEditarConductor"
              data-id="<?= (int)$u['id_usuario'] ?>"
              data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
              data-usuario="<?= htmlspecialchars($u['usuario']) ?>"
              data-correo="<?= htmlspecialchars($u['correo'] ?? '') ?>"
              data-licencia="<?= htmlspecialchars($u['tipo_licencia'] ?? '') ?>"
              data-activo="<?= (int)$u['activo'] ?>">
        Editar
      </button>

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

      <button type="button"
              class="btn btn-sm btn-outline-danger btn-eliminar-conductor"
              data-bs-toggle="modal"
              data-bs-target="#modalEliminarConductor"
              data-id="<?= (int)$u['id_usuario'] ?>"
              data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
              data-usuario="<?= htmlspecialchars($u['usuario']) ?>">
        Eliminar
      </button>
    </div>
   </td>
  </tr>
 <?php endforeach; ?>
 <?php if (!$users): ?><tr><td colspan="6" class="text-center muted">No hay conductores registrados.</td></tr><?php endif; ?>
 </tbody></table></div></div></div>
</div>

<!-- MODAL PARA EDITAR CONDUCTOR -->
<div class="modal fade" id="modalEditarConductor" tabindex="-1" aria-labelledby="modalEditarConductorLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content panel-card border border-info">
      <form method="post">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_usuario" id="edit_id_usuario">

        <div class="modal-header border-secondary">
          <h5 class="modal-title fw-bold" id="modalEditarConductorLabel">Editar datos del conductor</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="edit_nombre">Nombre completo</label>
            <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edit_usuario">Usuario</label>
            <input type="text" id="edit_usuario" name="usuario" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edit_correo">Correo electrónico</label>
            <input type="email" id="edit_correo" name="correo" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edit_tipo_licencia">Tipo de licencia</label>
            <select id="edit_tipo_licencia" name="tipo_licencia" class="form-select">
              <option value="">Sin especificar</option>
              <?php foreach ($licencias as $lic): ?>
                <option value="<?= htmlspecialchars($lic) ?>"><?= htmlspecialchars($lic) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edit_activo">Estado de la cuenta</label>
            <select id="edit_activo" name="activo" class="form-select">
              <option value="1">Activo (Puede iniciar sesión)</option>
              <option value="0">Inactivo / Bloqueado</option>
            </select>
          </div>

          <div class="mb-1">
            <label class="form-label" for="edit_password">Nueva contraseña (opcional)</label>
            <div class="input-group">
              <input type="password"
                     id="edit_password"
                     name="password"
                     class="form-control"
                     minlength="8"
                     pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                     title="Déjalo vacío para mantener la contraseña actual, o ingresa una de mínimo 8 caracteres con mayúscula, minúscula, número y signo.">
              <button type="button"
                      id="btn-toggle-password-edit"
                      class="btn btn-outline-secondary"
                      aria-label="Mostrar u ocultar contraseña"
                      title="Mostrar / ocultar contraseña">
                <svg id="icono-ojo-abrir-edit" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                </svg>
                <svg id="icono-ojo-cerrar-edit" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="d-none">
                  <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
                  <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
                  <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
                </svg>
              </button>
            </div>
            <div class="small muted mt-1">Déjalo en blanco si no deseas cambiar la contraseña actual del conductor.</div>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-vigia">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL PARA ELIMINAR CONDUCTOR DEFINITIVAMENTE -->
<div class="modal fade" id="modalEliminarConductor" tabindex="-1" aria-labelledby="modalEliminarConductorLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content panel-card border border-danger">
      <form method="post">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id_usuario" id="delete_id_usuario">

        <div class="modal-header border-secondary">
          <h5 class="modal-title fw-bold text-danger" id="modalEliminarConductorLabel">Eliminar cuenta permanentemente</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="alert alert-danger mb-3">
            ¿Estás seguro de eliminar por completo la cuenta <strong>"<span id="delete_nombre_cuenta"></span>"</strong> (<span id="delete_nombre_completo"></span>)?
            <div class="small mt-1">Esta acción eliminará el usuario y todo su historial de la base de datos y <strong>no se puede deshacer</strong>.</div>
          </div>

          <div class="mb-2">
            <label class="form-label" for="delete_password_confirmacion">
              Ingresa la contraseña de esta cuenta para confirmar la eliminación:
            </label>
            <div class="input-group">
              <input type="password"
                     id="delete_password_confirmacion"
                     name="password_confirmacion"
                     class="form-control"
                     placeholder="Contraseña de la cuenta a eliminar"
                     required
                     autocomplete="off">
              <button type="button"
                      id="btn-toggle-password-delete"
                      class="btn btn-outline-secondary"
                      aria-label="Mostrar u ocultar contraseña"
                      title="Mostrar / ocultar contraseña">
                <svg id="icono-ojo-abrir-delete" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                </svg>
                <svg id="icono-ojo-cerrar-delete" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="d-none">
                  <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
                  <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
                  <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Eliminar definitivamente</button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>

<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/admin_password_toggle.js"></script>
<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/admin_editar_usuario.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>