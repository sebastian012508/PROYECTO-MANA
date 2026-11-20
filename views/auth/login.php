<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

if (!empty($_SESSION['id_usuario'])) {
    $destino = ($_SESSION['rol'] ?? '') === 'ADMIN'
        ? '/views/admin/index.php'
        : '/views/conductor/index.php';
    header('Location: ' . APP_BASE_URL . $destino);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $controller = new AuthController(new UsuarioModel($pdo));
    $u = $controller->autenticar($usuario, $password);

    if ($u) {
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = (int)$u['id_usuario'];
        $_SESSION['nombre'] = $u['nombre'];
        $_SESSION['rol'] = $u['rol']; // El rol proviene de MariaDB, nunca del formulario.
        $destino = $u['rol'] === 'ADMIN'
            ? '/views/admin/index.php'
            : '/views/conductor/index.php';
        header('Location: ' . APP_BASE_URL . $destino);
        exit;
    }
    $error = 'Usuario o contraseña incorrectos, o cuenta desactivada.';
}

$pageTitle = 'Iniciar sesión';
$botonAdmin = true; // En el login el boton de arriba lleva a solicitar_admin.php
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

  <!-- LADO DERECHO: TU TARJETA DE INICIO DE SESIÓN -->
  <div class="split-right">
    <div class="card panel-card p-4 shadow-sm">
      <h2 class="fw-bold mb-1">Iniciar sesión</h2>
      <p class="muted">Accede con tu cuenta de MANA.</p>
      
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="post" action="">
        <label class="form-label" for="usuario">Usuario</label>
        <input id="usuario" class="form-control mb-3" name="usuario" required autocomplete="username">
        
        <label class="form-label" for="password">Contraseña</label>
        <input id="password" class="form-control mb-4" type="password" name="password" required autocomplete="current-password">
        
        <button class="btn btn-vigia w-100 btn-lg" type="submit">Ingresar</button>
      </form>
      
      <a class="d-block text-center mt-3 small" href="<?= htmlspecialchars(APP_BASE_URL) ?>/index.html">Volver al inicio</a>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>