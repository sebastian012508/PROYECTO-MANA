<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/models/VehiculoModel.php';

$vehiculoModel = new VehiculoModel($pdo);
$idConductor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

if ($idConductor <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

$vehiculoActual = $vehiculoModel->vehiculoActualConductor($idConductor);
$todosVehiculos = $vehiculoModel->listarTodos();

// Lista blanca de tipos de problema permitidos
$tiposProblemaPermitidos = [
    'Falla de llantas'    => 'Falla de llantas (pinchazo, desgaste, presión)',
    'Falta de gasolina'   => 'Falta de gasolina / combustible bajo',
    'Falla mecánica'      => 'Falla mecánica (motor, frenos, transmisión)',
    'Sistema eléctrico'   => 'Sistema eléctrico (luces, batería)',
    'Otro'                => 'Otro problema / característica',
];

$error = '';
$exito = '';

if (isset($_GET['enviado']) && $_GET['enviado'] === '1') {
    $exito = 'Reporte de falla o novedad enviado correctamente al administrador.';
}

// Valores por defecto para conservar lo escrito si ocurre un error
$formData = [
    'id_vehiculo'   => $vehiculoActual ? (int)$vehiculoActual['id_vehiculo'] : 0,
    'tipo_problema' => 'Falla de llantas',
    'descripcion'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['id_vehiculo']   = (int)($_POST['id_vehiculo'] ?? 0);
    $formData['tipo_problema'] = trim($_POST['tipo_problema'] ?? '');
    $formData['descripcion']   = trim($_POST['descripcion'] ?? '');

    // Verificar que el ID de vehículo recibido exista realmente en la flota
    $idsVehiculosValidos = array_map(static fn($v) => (int)$v['id_vehiculo'], $todosVehiculos);

    if ($formData['id_vehiculo'] <= 0 || !in_array($formData['id_vehiculo'], $idsVehiculosValidos, true)) {
        $error = 'Debes seleccionar un vehículo válido de la lista.';
    } elseif (!array_key_exists($formData['tipo_problema'], $tiposProblemaPermitidos)) {
        $error = 'El tipo de problema seleccionado no es válido.';
    } elseif ($formData['descripcion'] === '') {
        $error = 'Debes escribir una descripción detallada del problema o novedad.';
    } else {
        try {
            $vehiculoModel->registrarReporte(
                $formData['id_vehiculo'],
                $idConductor,
                $formData['tipo_problema'],
                $formData['descripcion']
            );
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?enviado=1');
            exit;
        } catch (PDOException $e) {
            error_log('Error al registrar reporte de vehículo: ' . $e->getMessage());
            $error = 'Ocurrió un error al guardar el reporte. Intenta nuevamente.';
        }
    }
}

$pageTitle = 'Reportar Novedades del Vehículo';
require __DIR__ . '/../layouts/header.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <div class="text-uppercase small text-success fw-bold">Área del Conductor</div>
        <h1 class="fw-bold">Reportar Fallas o Características del Vehículo</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($exito): ?>
    <div class="alert alert-success"><?= htmlspecialchars($exito) ?></div>
<?php endif; ?>

<div class="card panel-card p-4">
    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Vehículo</label>
            <select name="id_vehiculo" class="form-select" required>
                <?php if ($vehiculoActual): ?>
                    <option value="<?= (int)$vehiculoActual['id_vehiculo'] ?>" <?= $formData['id_vehiculo'] === (int)$vehiculoActual['id_vehiculo'] ? 'selected' : '' ?>>
                        [Asignado] <?= htmlspecialchars($vehiculoActual['codigo_vehiculo']) ?> - <?= htmlspecialchars($vehiculoActual['placa']) ?> (<?= htmlspecialchars($vehiculoActual['modelo']) ?>)
                    </option>
                <?php else: ?>
                    <option value="">-- Selecciona un vehículo --</option>
                <?php endif; ?>

                <?php foreach ($todosVehiculos as $v): ?>
                    <?php $idV = (int)$v['id_vehiculo']; ?>
                    <?php if (!$vehiculoActual || $idV !== (int)$vehiculoActual['id_vehiculo']): ?>
                        <option value="<?= $idV ?>" <?= $formData['id_vehiculo'] === $idV ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['codigo_vehiculo']) ?> - <?= htmlspecialchars($v['placa']) ?> (<?= htmlspecialchars($v['modelo']) ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Tipo de Problema / Novedad</label>
            <select name="tipo_problema" class="form-select" required>
                <?php foreach ($tiposProblemaPermitidos as $valor => $etiqueta): ?>
                    <option value="<?= htmlspecialchars($valor) ?>" <?= $formData['tipo_problema'] === $valor ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etiqueta) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Detalle del problema o característica observada</label>
            <textarea name="descripcion" class="form-control" rows="4"
                      placeholder="Describe qué ocurre con la movilidad (ej. Llanta trasera derecha baja, nivel de gasolina en reserva, ruido en frenos...)"
                      required><?= htmlspecialchars($formData['descripcion']) ?></textarea>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-vigia">Enviar Reporte</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>