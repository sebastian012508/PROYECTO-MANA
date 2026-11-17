<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
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
    'categoria'       => '',
    'tamano'          => '',
    'capacidad'       => '',
    'disponibilidad'  => 'Disponible',
];

// Mensaje de éxito tras la redirección
if (isset($_GET['registrado']) && $_GET['registrado'] === '1') {
    $exito = 'Vehículo registrado correctamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['codigo_vehiculo'] = trim($_POST['codigo_vehiculo'] ?? '');
    $formData['placa']           = strtoupper(trim($_POST['placa'] ?? ''));
    $formData['modelo']          = trim($_POST['modelo'] ?? '');
    $formData['categoria']       = trim($_POST['categoria'] ?? '');
    $formData['tamano']          = trim($_POST['tamano'] ?? '');
    $formData['capacidad']       = trim($_POST['capacidad'] ?? '');
    $formData['disponibilidad']  = trim($_POST['disponibilidad'] ?? 'Disponible');

    if (
        $formData['codigo_vehiculo'] === '' || $formData['placa'] === '' ||
        $formData['modelo'] === '' || $formData['capacidad'] === ''
    ) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (
        !in_array($formData['categoria'], $categoriasPermitidas, true) ||
        !in_array($formData['tamano'], $tamanosPermitidos, true) ||
        !in_array($formData['disponibilidad'], $disponibilidadesPermitidas, true)
    ) {
        $error = 'Uno de los valores seleccionados no es válido.';
    } else {
        try {
            $vehiculoModel->registrarVehiculo(
                $formData['codigo_vehiculo'],
                $formData['placa'],
                $formData['tamano'],
                $formData['modelo'],
                $formData['capacidad'],
                $formData['disponibilidad'],
                $formData['categoria']
            );
            // Redirección para evitar reenvío duplicado al recargar (F5)
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?registrado=1');
            exit;
        } catch (PDOException $e) {
            // Código 23000 indica violación de integridad (clave duplicada)
            if ($e->getCode() === '23000') {
                $error = 'Error al registrar: el código de vehículo o la placa ya existen en el sistema.';
            } else {
                error_log('Error al registrar vehículo: ' . $e->getMessage());
                $error = 'Ocurrió un error interno al guardar el vehículo. Intenta nuevamente.';
            }
        }
    }
}

$vehiculos = $vehiculoModel->listarTodos();
$reportes = $vehiculoModel->listarReportesRecientes(15);

$pageTitle = 'Gestión de Vehículos';
require __DIR__ . '/../layouts/header.php';
?>

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
    <form method="post" class="row g-3">
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
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select" required>
                <?php foreach ($categoriasPermitidas as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['categoria'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tamaño</label>
            <select name="tamano" class="form-select" required>
                <?php foreach ($tamanosPermitidos as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['tamano'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Capacidad</label>
            <input type="text" name="capacidad" class="form-control" placeholder="Ej. 20 Toneladas / 45 Pasajeros"
                   value="<?= htmlspecialchars($formData['capacidad']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Disponibilidad</label>
            <select name="disponibilidad" class="form-select" required>
                <?php foreach ($disponibilidadesPermitidas as $op): ?>
                    <option value="<?= htmlspecialchars($op) ?>" <?= $formData['disponibilidad'] === $op ? 'selected' : '' ?>>
                        <?= htmlspecialchars($op) ?>
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
                    <th>Código</th>
                    <th>Placa</th>
                    <th>Modelo</th>
                    <th>Categoría</th>
                    <th>Tamaño</th>
                    <th>Capacidad</th>
                    <th>Disponibilidad</th>
                    <th>Chofer Asignado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehiculos)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No hay vehículos registrados aún.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehiculos as $v): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($v['codigo_vehiculo']) ?></td>
                            <td><?= htmlspecialchars($v['placa']) ?></td>
                            <td><?= htmlspecialchars($v['modelo']) ?></td>
                            <td><?= htmlspecialchars($v['categoria']) ?></td>
                            <td><?= htmlspecialchars($v['tamano']) ?></td>
                            <td><?= htmlspecialchars($v['capacidad']) ?></td>
                            <td>
                                <span class="badge bg-<?= $v['disponibilidad'] === 'Disponible' ? 'success' : ($v['disponibilidad'] === 'En uso' ? 'warning text-dark' : 'danger') ?>">
                                    <?= htmlspecialchars($v['disponibilidad']) ?>
                                </span>
                            </td>
                            <td><?= $v['conductor_nombre'] ? htmlspecialchars($v['conductor_nombre']) : '<span class="text-muted">Sin asignar</span>' ?></td>
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

<?php require __DIR__ . '/../layouts/footer.php'; ?>