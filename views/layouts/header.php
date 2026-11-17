<?php
require_once __DIR__ . '/../../config/app.php';
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../app/helpers/auth.php';
}
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark nav-vigia">
 <div class="container">
  <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= htmlspecialchars(APP_BASE_URL) ?>/index.html">
   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-car-front-fill" viewBox="0 0 16 16">
    <path d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679q.05.242.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.8.8 0 0 0 .381-.404l.792-1.848ZM3 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2m10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2M6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2zM2.906 5.189a.51.51 0 0 0 .497.731c.91-.073 3.35-.17 4.597-.17s3.688.097 4.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 11.691 3H4.309a.5.5 0 0 0-.447.276L2.906 5.19Z"/>
   </svg>
   <span>MANA</span>
  </a>
  <div class="ms-auto d-flex gap-2 align-items-center">
   <?php if (!empty($_SESSION['id_usuario'])): ?>
    <span class="text-white-50 small d-none d-md-inline"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></span>
    <a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars(APP_BASE_URL) ?>/views/auth/logout.php">Cerrar sesión</a>
   <?php else: ?>
    <a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars(APP_BASE_URL) ?>/views/auth/login.php">Ingresar</a>
   <?php endif; ?>
  </div>
 </div>
</nav>
<main class="container py-4">