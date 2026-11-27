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
    <?php
    $urlImgActual = !empty($vehiculoActual['imagen'])
        ? APP_BASE_URL . '/' . ltrim((string)$vehiculoActual['imagen'], '/')
        : '';
    ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>Vehículo actual asignado:</strong>
            <?= htmlspecialchars($vehiculoActual['codigo_vehiculo']) ?> — <?= htmlspecialchars($vehiculoActual['modelo']) ?>
            (Placa: <?= htmlspecialchars($vehiculoActual['placa']) ?><?= !empty($vehiculoActual['color']) ? ' | Color: ' . htmlspecialchars($vehiculoActual['color']) : '' ?>)
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button"
                    class="btn btn-sm btn-outline-info btn-ver-imagen-vehiculo"
                    data-bs-toggle="modal"
                    data-bs-target="#modalImagenVehiculo"
                    data-imagen="<?= htmlspecialchars($urlImgActual) ?>"
                    data-codigo="<?= htmlspecialchars($vehiculoActual['codigo_vehiculo']) ?>"
                    data-modelo="<?= htmlspecialchars($vehiculoActual['modelo']) ?>"
                    data-placa="<?= htmlspecialchars($vehiculoActual['placa']) ?>"
                    data-color="<?= htmlspecialchars($vehiculoActual['color'] ?? 'No especificado') ?>">
                Ver imagen del vehículo
            </button>
            <form method="post" class="m-0">
                <input type="hidden" name="accion" value="liberar">
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas dejar este vehículo?');">
                    Dejar vehículo
                </button>
            </form>
        </div>
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
                    <th>Color</th>
                    <th>Categoría</th>
                    <th>Tamaño</th>
                    <th>Capacidad</th>
                    <th>Estado</th>
                    <th>Imagen</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehiculos)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No hay vehículos registrados por el administrador.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehiculos as $v): ?>
                        <?php
                        $idClienteSeleccion = (int)($v['id_cliente_seleccion'] ?? 0);
                        $badgeClass = match ($v['disponibilidad']) {
                            'Disponible' => 'bg-success',
                            'En uso'     => 'bg-warning text-dark',
                            default      => 'bg-danger',
                        };
                        $urlImgFila = !empty($v['imagen'])
                            ? APP_BASE_URL . '/' . ltrim((string)$v['imagen'], '/')
                            : '';
                        ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($v['codigo_vehiculo']) ?></td>
                            <td><?= htmlspecialchars($v['placa']) ?></td>
                            <td><?= htmlspecialchars($v['modelo']) ?></td>
                            <td><?= htmlspecialchars($v['color'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($v['categoria']) ?></td>
                            <td><?= htmlspecialchars($v['tamano']) ?></td>
                            <td><?= htmlspecialchars($v['capacidad']) ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($v['disponibilidad']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-info btn-ver-imagen-vehiculo"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalImagenVehiculo"
                                        data-imagen="<?= htmlspecialchars($urlImgFila) ?>"
                                        data-codigo="<?= htmlspecialchars($v['codigo_vehiculo']) ?>"
                                        data-modelo="<?= htmlspecialchars($v['modelo']) ?>"
                                        data-placa="<?= htmlspecialchars($v['placa']) ?>"
                                        data-color="<?= htmlspecialchars($v['color'] ?? 'No especificado') ?>">
                                    Ver imagen del vehículo
                                </button>
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

<!-- MODAL DE SOLO LECTURA PARA OBSERVAR LA IMAGEN DEL VEHÍCULO -->
<div class="modal fade" id="modalImagenVehiculo" tabindex="-1" aria-labelledby="modalImagenVehiculoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content panel-card border border-info" style="background-color: #0b1926; color: #ffffff;">
      <div class="modal-header border-secondary">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modal_vehiculo_titulo">Vista del vehículo</h5>
          <small class="text-info" id="modal_vehiculo_detalle"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center p-4">
        <img id="modal_vehiculo_img"
             src=""
             alt="Imagen del vehículo"
             class="img-fluid rounded shadow d-none"
             style="max-height: 460px; object-fit: contain; border: 1px solid rgba(255,255,255,0.2);">
        <div id="modal_vehiculo_sin_img" class="py-5 text-muted d-none">
          El administrador aún no ha subido una fotografía para este vehículo.
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

</div>

<script src="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/js/conductor_ver_vehiculo.js"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>