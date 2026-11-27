<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/models/VehiculoModel.php';

$vehiculoModel = new VehiculoModel($pdo);
$error = '';
$exito = '';

// Valores permitidos para los campos de selección
$categoriasPermitidas = [
    'Carga Pesada',
    'Transporte Interdepartamental',
    'Minibús / Van',
    'Vehículo Liviano',
];
$tamanosPermitidos = [
    'Pequeño',
    'Mediano',
    'Grande',
    'Extra Grande / Tráiler',
];
$disponibilidadesPermitidas = [
    'Disponible',
    'En uso',
    'Mantenimiento',
];

// Valores por defecto del formulario
$formData = [
    'codigo_vehiculo' => '',
    'placa'           => '',
    'modelo'          => '',
    'color'           => '',
    'categoria'       => 'Carga Pesada',
    'tamano'          => 'Pequeño',
    'capacidad'       => '',
    'disponibilidad'  => 'Disponible',
    'id_conductor'    => '',
];

// Mensajes de éxito tras la redirección
if (isset($_GET['registrado']) && $_GET['registrado'] === '1') {
    $exito = 'Vehículo registrado correctamente.';
} elseif (isset($_GET['asignado']) && $_GET['asignado'] === '1') {
    $exito = 'Asignación de conductor actualizada correctamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'registrar';

    if ($accion === 'asignar_conductor') {
        $idVehiculo  = (int)($_POST['id_vehiculo'] ?? 0);
        $idConductor = (int)($_POST['id_conductor'] ?? 0);
        if ($idVehiculo > 0) {
            try {
                $vehiculoModel->asignarConductorDirecto($idVehiculo, $idConductor > 0 ? $idConductor : null);
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?asignado=1');
                exit;
            } catch (Throwable $e) {
                error_log('Error al asignar conductor: ' . $e->getMessage());
                $error = 'No se pudo actualizar la asignación del conductor.';
            }
        }
    } else {
        $formData['codigo_vehiculo'] = trim($_POST['codigo_vehiculo'] ?? '');
        $formData['placa']           = strtoupper(trim($_POST['placa'] ?? ''));
        $formData['modelo']          = trim($_POST['modelo'] ?? '');
        $formData['color']           = trim($_POST['color'] ?? '');
        $formData['categoria']       = trim($_POST['categoria'] ?? '');
        $formData['tamano']          = trim($_POST['tamano'] ?? '');
        $formData['capacidad']       = trim($_POST['capacidad'] ?? '');
        $formData['disponibilidad']  = trim($_POST['disponibilidad'] ?? 'Disponible');
        $formData['id_conductor']    = trim($_POST['id_conductor'] ?? '');

        $rutaImagenRelativa = null;

        if (
            $formData['codigo_vehiculo'] === '' || $formData['placa'] === '' ||
            $formData['modelo'] === '' || $formData['color'] === '' ||
            $formData['capacidad'] === ''
        ) {
            $error = 'Todos los campos obligatorios (incluyendo color) deben completarse.';
        } elseif (
            !in_array($formData['categoria'], $categoriasPermitidas, true) ||
            !in_array($formData['tamano'], $tamanosPermitidos, true) ||
            !in_array($formData['disponibilidad'], $disponibilidadesPermitidas, true)
        ) {
            $error = 'Uno de los valores seleccionados no es válido.';
        }

        // Procesar subida opcional de imagen del vehículo
        if ($error === '' && isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            $archivo = $_FILES['imagen'];
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $error = 'Error al subir la imagen del vehículo.';
            } elseif ($archivo['size'] > 3 * 1024 * 1024) {
                $error = 'La imagen supera el tamaño máximo permitido de 3 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($archivo['tmp_name']);
                $extPermitidas = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($extPermitidas[$mime])) {
                    $error = 'Formato de imagen no válido. Solo se permiten JPG, PNG o WEBP.';
                } else {
                    $dirDestino = __DIR__ . '/../../public/img/vehiculos';
                    if (!is_dir($dirDestino)) {
                        mkdir($dirDestino, 0755, true);
                    }
                    $nombreArchivo = 'veh_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extPermitidas[$mime];
                    if (move_uploaded_file($archivo['tmp_name'], $dirDestino . '/' . $nombreArchivo)) {
                        $rutaImagenRelativa = 'public/img/vehiculos/' . $nombreArchivo;
                    } else {
                        $error = 'No se pudo guardar la imagen en el servidor.';
                    }
                }
            }
        }

        if ($error === '') {
            try {
                $idConductorAsignado = $formData['id_conductor'] !== '' ? (int)$formData['id_conductor'] : null;

                $vehiculoModel->registrarVehiculo(
                    $formData['codigo_vehiculo'],
                    $formData['placa'],
                    $formData['tamano'],
                    $formData['modelo'],
                    $formData['capacidad'],
                    $formData['disponibilidad'],
                    $formData['categoria'],
                    $formData['color'],
                    $rutaImagenRelativa,
                    $idConductorAsignado
                );
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?registrado=1');
                exit;
            } catch (PDOException $e) {
                if ($rutaImagenRelativa && file_exists(__DIR__ . '/../../' . $rutaImagenRelativa)) {
                    @unlink(__DIR__ . '/../../' . $rutaImagenRelativa);
                }
                if ($e->getCode() === '23000') {
                    $error = 'Error al registrar: el código de vehículo o la placa ya existen en el sistema.';
                } else {
                    error_log('Error al registrar vehículo: ' . $e->getMessage());
                    $error = 'Ocurrió un error interno al guardar el vehículo. Intenta nuevamente.';
                }
            }
        }
    }
}

$vehiculos   = $vehiculoModel->listarTodos();
$conductores = $vehiculoModel->listarConductoresActivos();
$reportes    = $vehiculoModel->listarReportesRecientes(15);

$pageTitle = 'Gestión de Vehículos';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos Neón estáticos con predominio rosa neón -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/admin_neon.css">

<div class="admin-neon-pink">
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <div class="text-uppercase small text-primary fw-bold">Administración de Flota</div>
        <h1 class="fw-bold">Registro y Control de Vehículos</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($exito): ?>
    <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
<?php endif; ?>

<div class="card panel-card p-4 mb-4">
    <h4 class="mb-3">Registrar Nuevo Vehículo</h4>
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="accion" value="registrar">

        <div class="col-md-3">
            <label class="form-label">Código Vehículo</label>
            <input type="text" name="codigo_vehiculo" class="form-control" placeholder="Ej. VEH-001"
                   value="<?= htmlspecialchars($formData['codigo_vehiculo']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Placa</label>
            <input type="text" name="placa" class="form-control" placeholder="Ej. 4582-XTR"
                   value="<?= htmlspecialchars($formData['placa']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-control" placeholder="Ej. Volvo FH16 2022"
                   value="<?= htmlspecialchars($formData['modelo']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Color</label>
            <input type="text" name="color" class="form-control" placeholder="Ej. Blanco / Rojo / Gris"
                   value="<?= htmlspecialchars($formData['color']) ?>" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select" required>
                <?php foreach ($categoriasPermitidas as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['categoria'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tamaño</label>
            <select name="tamano" class="form-select" required>
                <?php foreach ($tamanosPermitidos as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['tamano'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Capacidad</label>
            <input type="text" name="capacidad" class="form-control" placeholder="Ej. 20 Toneladas / 45 Pasajeros"
                   value="<?= htmlspecialchars($formData['capacidad']) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Disponibilidad</label>
            <select name="disponibilidad" class="form-select" required>
                <?php foreach ($disponibilidadesPermitidas as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['disponibilidad'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Imagen del Auto (JPG, PNG o WEBP - Máx. 3 MB)</label>
            <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="col-md-6">
            <label class="form-label">Designar directamente a un Conductor (opcional)</label>
            <select name="id_conductor" class="form-select">
                <option value="">-- Dejar sin asignar (Disponible) --</option>
                <?php foreach ($conductores as $c): ?>
                    <option value="<?= (int)$c['id_usuario'] ?>" <?= (string)$formData['id_conductor'] === (string)$c['id_usuario'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?> (@<?= htmlspecialchars($c['usuario']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-vigia">Guardar Vehículo</button>
        </div>
    </form>
</div>

<div class="card panel-card p-4 mb-4">
    <h4 class="mb-3">Vehículos Registrados</h4>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Código</th>
                    <th>Placa</th>
                    <th>Modelo</th>
                    <th>Color</th>
                    <th>Categoría</th>
                    <th>Tamaño</th>
                    <th>Capacidad</th>
                    <th>Disponibilidad</th>
                    <th>Chofer Asignado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehiculos)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No hay vehículos registrados aún.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehiculos as $v): ?>
                        <tr>
                            <td>
                                <?php if (!empty($v['imagen'])): ?>
                                    <img src="<?= htmlspecialchars(APP_BASE_URL . '/' . $v['imagen']) ?>"
                                         alt="<?= htmlspecialchars($v['modelo']) ?>"
                                         style="width: 68px; height: 46px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2);">
                                <?php else: ?>
                                    <span class="text-muted small">Sin foto</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($v['codigo_vehiculo']) ?></td>
                            <td><?= htmlspecialchars($v['placa']) ?></td>
                            <td><?= htmlspecialchars($v['modelo']) ?></td>
                            <td><?= htmlspecialchars($v['color'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($v['categoria']) ?></td>
                            <td><?= htmlspecialchars($v['tamano']) ?></td>
                            <td><?= htmlspecialchars($v['capacidad']) ?></td>
                            <td>
                                <span class="badge bg-<?= $v['disponibilidad'] === 'Disponible' ? 'success' : ($v['disponibilidad'] === 'En uso' ? 'warning text-dark' : 'danger') ?>">
                                    <?= htmlspecialchars($v['disponibilidad']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="d-flex gap-1 align-items-center m-0">
                                    <input type="hidden" name="accion" value="asignar_conductor">
                                    <input type="hidden" name="id_vehiculo" value="<?= (int)$v['id_vehiculo'] ?>">
                                    <select name="id_conductor" class="form-select form-select-sm" style="min-width: 165px;">
                                        <option value="0">-- Sin asignar --</option>
                                        <?php foreach ($conductores as $c): ?>
                                            <option value="<?= (int)$c['id_usuario'] ?>" <?= ((int)($v['id_cliente_seleccion'] ?? 0) === (int)$c['id_usuario']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-info">Asignar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card panel-card p-4">
    <h4 class="mb-3">Últimos Reportes de Fallas de Choferes</h4>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Vehículo</th>
                    <th>Chofer</th>
                    <th>Tipo de Problema</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportes)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay reportes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($reportes as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['fecha_reporte']) ?></td>
                            <td><strong><?= htmlspecialchars($r['codigo_vehiculo']) ?></strong> (<?= htmlspecialchars($r['placa']) ?>)</td>
                            <td><?= htmlspecialchars($r['conductor_nombre']) ?></td>
                            <td><span class="badge bg-danger"><?= htmlspecialchars($r['tipo_problema']) ?></span></td>
                            <td><?= htmlspecialchars($r['descripcion']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>