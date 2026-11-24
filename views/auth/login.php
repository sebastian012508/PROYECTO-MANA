<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';
require_once __DIR__ . '/../../app/helpers/seguridad_login.php';

if (!empty($_SESSION['id_usuario'])) {
    $destino = ($_SESSION['rol'] ?? '') === 'ADMIN'
        ? '/views/admin/index.php'
        : '/views/conductor/index.php';
    header('Location: ' . APP_BASE_URL . $destino);
    exit;
}

$error = '';
$usuarioIngresado = trim($_POST['usuario'] ?? ($_SESSION['ultimo_usuario_login'] ?? ''));
$estadoBloqueo = verificarEstadoBloqueo($pdo, $usuarioIngresado);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $_SESSION['ultimo_usuario_login'] = $usuario;
    $usuarioIngresado = $usuario;

    // 1. Comprobar si ya está bloqueado antes de validar contraseña
    $estadoBloqueo = verificarEstadoBloqueo($pdo, $usuario);

    if ($estadoBloqueo['bloqueado']) {
        $error = $estadoBloqueo['mensaje'];
    } else {
        $controller = new AuthController(new UsuarioModel($pdo));
        $u = $controller->autenticar($usuario, $password);

        if ($u) {
            limpiarIntentosLogin($usuario);
            unset($_SESSION['ultimo_usuario_login']);

            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int)$u['id_usuario'];
            $_SESSION['nombre'] = $u['nombre'];
            $_SESSION['rol'] = $u['rol'];
            $destino = $u['rol'] === 'ADMIN'
                ? '/views/admin/index.php'
                : '/views/conductor/index.php';
            header('Location: ' . APP_BASE_URL . $destino);
            exit;
        }

        // Registrar fallo y aplicar escala: 30s -> 15min -> bloqueo total
        $estadoBloqueo = registrarIntentoFallido($pdo, $usuario);
        $error = $estadoBloqueo['mensaje'];
    }
} elseif ($estadoBloqueo['bloqueado']) {
    $error = $estadoBloqueo['mensaje'];
}

$bloquearInputs = $estadoBloqueo['bloqueado'] && !$estadoBloqueo['permanente'] && $estadoBloqueo['segundos'] > 0;
$segundosRestantes = (int)$estadoBloqueo['segundos'];

$pageTitle = 'Iniciar sesión';
$botonAdmin = true;
require __DIR__ . '/../layouts/header.php';
?>

<!-- CSS exclusivo del Split Screen -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/auth_split.css">

<div class="split-container">
  
  <!-- LADO IZQUIERDO: IMAGEN Y MENSAJE -->
  <div class="split-left" style="background-image: url('<?= htmlspecialchars(APP_BASE_URL) ?>/public/img/autos_carretera.webp');">
    <div class="split-left-content">
      <span class="split-badge">Monitoreo Inteligente en Ruta</span>
      <h2>Prevención activa contra la <span>fatiga al volante</span></h2>
      <p>Accede al panel de control para supervisar alertas en tiempo real, trazabilidad GPS y seguridad de conductores.</p>
    </div>
  </div>

  <!-- LADO DERECHO: TARJETA DE INICIO DE SESIÓN -->
  <div class="split-right">
    <div class="card panel-card p-4 shadow-sm">
      <h2 class="fw-bold mb-1">Iniciar sesión</h2>
      <p class="muted">Accede con tu cuenta de MANA.</p>
      
      <?php if ($error): ?>
        <div class="alert alert-danger" id="alerta-login">
          <?= htmlspecialchars($error) ?>
          <?php if ($bloquearInputs): ?>
            <div class="fw-bold mt-1">
              Tiempo restante: <span id="contador-bloqueo" data-segundos="<?= $segundosRestantes ?>"><?= $segundosRestantes ?> s</span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
      <form method="post" action="" id="form-login">
        <label class="form-label" for="usuario">Usuario</label>
        <input id="usuario"
               class="form-control mb-3"
               name="usuario"
               value="<?= htmlspecialchars($usuarioIngresado) ?>"
               <?= $bloquearInputs ? 'disabled' : '' ?>
               required
               autocomplete="username">
        
        <label class="form-label" for="password">Contraseña</label>
        <div class="input-group mb-4">
          <input id="password"
                 class="form-control"
                 type="password"
                 name="password"
                 <?= $bloquearInputs ? 'disabled' : '' ?>
                 required
                 autocomplete="current-password">
          <button type="button"
                  id="btn-toggle-password"
                  class="btn btn-outline-secondary"
                  aria-label="Mostrar u ocultar contraseña"
                  title="Mostrar / ocultar contraseña"
                  <?= $bloquearInputs ? 'disabled' : '' ?>>
            <svg id="icono-ojo-abrir" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
            </svg>
            <svg id="icono-ojo-cerrar" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="d-none">
              <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/>
              <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/>
              <path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>
            </svg>
          </button>
        </div>
        
        <button id="btn-ingresar"
                class="btn btn-vigia w-100 btn-lg"
                type="submit"
                <?= $bloquearInputs ? 'disabled' : '' ?>>
          <?= $bloquearInputs ? 'Formulario bloqueado...' : 'Ingresar' ?>
        </button>
      </form>
      
      <a class="d-block text-center mt-3 small" href="<?= htmlspecialchars(APP_BASE_URL) ?>/index.html">Volver al inicio</a>
    </div>
  </div>

</div>

<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/login_seguridad.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>