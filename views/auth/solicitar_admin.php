<?php
require_once __DIR__ . '/../../config/app.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if ($nombre === '' || $correo === '') {
        $error = 'Completa tu nombre y tu correo.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no es valido.';
    } else {
        // Correo del grupo MANA donde llegan las solicitudes
        $para = 'proyectogrupomana@gmail.com';
        $asunto = 'Nueva solicitud para ser conductor';

        $texto = "Una persona solicito ser conductor en MANA.\n\n";
        $texto .= "Nombre: " . $nombre . "\n";
        $texto .= "Correo: " . $correo . "\n";

        $cabeceras = "From: proyectogrupomana@gmail.com\r\n";
        $cabeceras .= "Reply-To: " . $correo . "\r\n";
        $cabeceras .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($para, $asunto, $texto, $cabeceras)) {
            $mensaje = 'Tu solicitud fue enviada. Te responderemos a tu correo.';
        } else {
            $error = 'No se pudo enviar la solicitud. Intentalo mas tarde.';
        }
    }
}

$pageTitle = 'Solicitar ser conductor';
require __DIR__ . '/../layouts/header.php';
?>

<!-- CSS exclusivo del Split Screen -->
<link rel="stylesheet" href="../../public/css/auth_split.css">

<div class="split-container auth-login">


  <!-- LADO IZQUIERDO: IMAGEN Y MENSAJE -->
<div class="split-left" style="background-image: url('../../public/img/solicitar_conductor.jpg');">
    <div class="split-left-top">
      <span class="split-marca">MANA</span>
      <a class="split-volver" href="login.php">Volver <span aria-hidden="true">→</span></a>
    </div>
    <div class="split-left-content">
      <span class="split-badge">Conducción Segura MANA</span>
      <h2>Únete a la red de <span>conductores protegidos</span></h2>
      <p>Envía tus datos para habilitar tu acceso al sistema de detección temprana de fatiga y microsueños en ruta.</p>
    </div>
  </div>

  <!-- LADO DERECHO: TU TARJETA DE FORMULARIO ORIGINAL -->
  <div class="split-right">
    <div class="card panel-card p-4 shadow-sm">
      <h2 class="fw-bold mb-1">Solicitar ser conductor</h2>
      <p class="muted">Escribe tus datos y te contactaremos.</p>
      
      <?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      
      <form method="post" action="">
        <label class="form-label" for="nombre">Nombre</label>
        <input id="nombre" class="form-control mb-3" name="nombre" required>
        
        <label class="form-label" for="correo">Correo</label>
        <input id="correo" class="form-control mb-4" type="email" name="correo" required>
        
        <button class="btn btn-vigia w-100 btn-lg" type="submit">Enviar solicitud</button>
      </form>
      
      <a class="d-block text-center mt-3 small solo-movil" href="login.php">Volver</a>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>