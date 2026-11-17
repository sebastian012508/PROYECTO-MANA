<?php
require __DIR__ . '/../../config/db.php';
$count = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='ADMIN'")->fetchColumn();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $count === 0) {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($nombre !== '' && $usuario !== '' && strlen($password) >= 8) {
        $stmt = $pdo->prepare("INSERT INTO usuarios(nombre,usuario,password_hash,rol) VALUES(?,?,?,'ADMIN')");
        try {
            $stmt->execute([$nombre,$usuario,password_hash($password,PASSWORD_DEFAULT)]);
            $msg = 'Administrador creado. ELIMINA ahora el archivo crear_admin.php.';
            $count = 1;
        } catch (PDOException $e) { $msg = 'No se pudo crear. Verifica que el usuario no exista.'; }
    } else { $msg = 'Completa los campos y utiliza una contraseña de al menos 8 caracteres.'; }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear administrador</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container" style="max-width:520px;margin:5rem auto"><div class="card p-4 shadow-sm"><h2>Crear primer administrador</h2>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($count === 0): ?><form method="post"><label class="form-label">Nombre</label><input class="form-control mb-3" name="nombre" required><label class="form-label">Usuario</label><input class="form-control mb-3" name="usuario" required><label class="form-label">Contraseña (mínimo 8 caracteres)</label><input class="form-control mb-3" type="password" name="password" minlength="8" required><button class="btn btn-primary w-100">Crear administrador</button></form><?php else: ?><p>Ya existe un administrador. Por seguridad, elimina este archivo.</p><a href="login.php">Ir al login</a><?php endif; ?></div></div></body></html>
