<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../app/controllers/ReporteController.php';

$opciones = (new ReporteController(new ReporteModel($pdo)))->opciones();
$pageTitle = 'Reportes';
require __DIR__ . '/../layouts/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_URL) ?>/public/css/admin_neon.css">

<div class="admin-neon-scope">
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="text-uppercase small text-success fw-bold">Administración</div>
        <h1 class="fw-bold">Reporte de eventos de somnolencia</h1>
        <p class="muted mb-0">Elige los filtros o deja todo vacío para incluir todo el historial. El reporte incluye todos los tipos de alerta y todas las severidades.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="card panel-card p-4">
    <form method="get" action="../reportes/eventos.php" class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="desde">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="chofer">Chofer</label>
            <select id="chofer" name="chofer" class="form-select">
                <option value="">Todos los choferes</option>
                <?php foreach ($opciones['conductores'] as $c): ?>
                    <option value="<?= (int)$c['id_usuario'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="vehiculo">Placa del vehículo</label>
            <select id="vehiculo" name="vehiculo" class="form-select">
                <option value="">Todas las placas</option>
                <?php foreach ($opciones['vehiculos'] as $v): ?>
                    <option value="<?= (int)$v['id_vehiculo'] ?>"><?= htmlspecialchars($v['placa'] . ' (' . $v['codigo_vehiculo'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-vigia">Descargar PDF</button>
        </div>
    </form>
</div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
