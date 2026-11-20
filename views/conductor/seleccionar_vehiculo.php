<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/models/VehiculoModel.php';

$vehiculoModel = new VehiculoModel($pdo);
$idConductor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;

if ($idConductor <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'seleccionar' && !empty($_POST['id_vehiculo'])) {
        // Verificar si el conductor ya tiene un vehículo asignado antes de permitir seleccionar otro
        $vehiculoPrevio = $vehiculoModel->vehiculoActualConductor($idConductor);
        if ($vehiculoPrevio) {
            $_SESSION['flash'] = 'Ya tienes un vehículo asignado. Debes liberarlo antes de seleccionar otro.';
        } else {
            $ok = $vehiculoModel->seleccionarVehiculo((int)$_POST['id_vehiculo'], $idConductor);
            $_SESSION['flash'] = $ok ? 'Vehículo asignado correctamente.' : 'El vehículo ya no está disponible.';
        }
    } elseif ($accion === 'liberar') {
        $vehiculoModel->liberarVehiculo($idConductor);
        $_SESSION['flash'] = 'Has liberado tu vehículo actual.';
    }

    header('Location: seleccionar_vehiculo.php');
    exit;
}

$vehiculoActual = $vehiculoModel->vehiculoActualConductor($idConductor);
$vehiculos = $vehiculoModel->listarTodos();

$pageTitle = 'Seleccionar Vehículo';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Estilos estáticos con los colores del landing page -->
<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/conductor_neon.css">

<div class="conductor-landing-static">
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <div class="text-uppercase small text-success fw-bold">Área del Conductor</div>
        <h1 class="fw-bold">Lista de Vehículos</h1>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if ($vehiculoActual): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>Vehículo actual asignado:</strong>
            <?= htmlspecialchars($vehiculoActual['codigo_vehiculo']) ?> — <?= htmlspecialchars($vehiculoActual['modelo']) ?> (Placa: <?= htmlspecialchars($vehiculoActual['placa']) ?>)
        </div>
        <form method="post" class="m-0">
            <input type="hidden" name="accion" value="liberar">
            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas dejar este vehículo?');">
                Dejar vehículo
            </button>
        </form>
    </div>
<?php endif; ?>

<div class="card panel-card p-4">
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
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehiculos)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No hay vehículos registrados por el administrador.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehiculos as $v): ?>
                        <?php
                        $idClienteSeleccion = (int)($v['id_cliente_seleccion'] ?? 0);
                        $badgeClass = match ($v['disponibilidad']) {
                            'Disponible' => 'bg-success',
                            'En uso'     => 'bg-warning text-dark',
                            default      => 'bg-danger',
                        };
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($v['codigo_vehiculo']) ?></td>
                            <td><?= htmlspecialchars($v['placa']) ?></td>
                            <td><?= htmlspecialchars($v['modelo']) ?></td>
                            <td><?= htmlspecialchars($v['categoria']) ?></td>
                            <td><?= htmlspecialchars($v['tamano']) ?></td>
                            <td><?= htmlspecialchars($v['capacidad']) ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($v['disponibilidad']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($idClienteSeleccion === $idConductor): ?>
                                    <span class="badge bg-primary">Tu vehículo actual</span>
                                <?php elseif ($v['disponibilidad'] === 'Disponible'): ?>
                                    <?php if ($vehiculoActual): ?>
                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Libera tu vehículo actual primero">
                                            Seleccionar
                                        </button>
                                    <?php else: ?>
                                        <form method="post" class="m-0">
                                            <input type="hidden" name="accion" value="seleccionar">
                                            <input type="hidden" name="id_vehiculo" value="<?= (int)$v['id_vehiculo'] ?>">
                                            <button type="submit" class="btn btn-sm btn-vigia">Seleccionar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">No disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>