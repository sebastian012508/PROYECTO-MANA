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
  <a class="navbar-brand fw-bold" href="<?= htmlspecialchars(APP_BASE_URL) ?>/index.html">VIGÍA</a>
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
