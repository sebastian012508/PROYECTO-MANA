<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('CONDUCTOR');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/controllers/HistorialController.php';
require_once __DIR__ . '/../../app/controllers/DetectorController.php';

$historial = new HistorialController(new HistorialModel($pdo));
$resumen = $historial->resumen((int)$_SESSION['id_usuario']);
$nSesiones = $resumen['sesiones'];
$nAlertas = $resumen['alertas'];

$apiStatus = (new DetectorController())->estado();

$pageTitle = 'Panel conductor';
require __DIR__ . '/../layouts/header.php';
?>
<?php if (!empty($_SESSION['flash'])): ?>
 <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash']) ?></div>
 <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<div class="mb-4">
 <div class="text-uppercase small text-success fw-bold">Área del conductor</div>
 <h1 class="fw-bold">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
 <p class="muted">Desde aquí puedes iniciar o detener el monitoreo de somnolencia.</p>
</div>
<div class="row g-3 mb-4">
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Mis sesiones</div><div class="display-5 fw-bold"><?= $nSesiones ?></div></div></div>
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Mis alertas</div><div class="display-5 fw-bold"><?= $nAlertas ?></div></div></div>
 <div class="col-md-4"><div class="card stat-card p-3"><div class="muted">Servicio Python</div><div class="fs-5 fw-bold mt-2"><?= htmlspecialchars($apiStatus) ?></div></div></div>
</div>
<div class="card panel-card p-4 mb-4">
 <h4>Monitoreo de somnolencia</h4>
 <p class="muted">Pulsa Iniciar para abrir la cámara y registrar una sesión vinculada a tu usuario. La imagen aparecerá en esta página.</p>
 <div class="d-flex flex-wrap gap-2 mb-3">
  <form method="post" action="control.php"><input type="hidden" name="accion" value="start"><button class="btn btn-vigia btn-lg" type="submit">▶ Iniciar monitoreo</button></form>
  <form method="post" action="control.php"><input type="hidden" name="accion" value="stop"><button class="btn btn-outline-danger btn-lg" type="submit">■ Detener monitoreo</button></form>
  <a class="btn btn-outline-secondary btn-lg" href="historial.php">Ver mi historial</a>
  <a class="btn btn-outline-primary btn-lg" href="seleccionar_vehiculo.php"> Seleccionar Vehículo</a>
  <a class="btn btn-outline-warning btn-lg" href="reportar_vehiculo.php"> Reportar Falla Vehículo</a>
 </div>
 <div class="row g-3 align-items-start">
  <div class="col-lg-8">
   <div class="bg-dark rounded overflow-hidden text-center">
    <div id="camara-mensaje" class="text-white p-5">Pulsa Iniciar monitoreo para activar la cámara.</div>
    <img id="camara-video" alt="Cámara de detección en tiempo real" style="display:none;width:100%;max-height:480px;object-fit:contain;min-height:260px">
   </div>
  </div>
  <div class="col-lg-4">
   <div class="card border-0 bg-light p-3 h-100">
    <div class="small text-uppercase text-secondary fw-bold">Estado del detector</div>
    <h4 id="estado-texto" class="text-secondary mt-2">Esperando inicio</h4>
    <p id="estado-detalle" class="mb-0">EAR: —</p>
    <p id="estado-aviso" class="small text-danger mt-2 mb-0"></p>
   </div>
  </div>
 </div>
</div>
<div class="card panel-card p-4 mb-4">
 <h4>Compartir mi ubicación</h4>
 <p class="muted">Tu administrador no puede ver la cámara, pero sí tu posición en el mapa. Mientras esté activado, tu ubicación se envía cada 15 segundos. Mantén esta página abierta.</p>
 <div class="d-flex flex-wrap gap-2 align-items-center">
  <button id="btn-ubicacion" class="btn btn-vigia btn-lg" type="button"> Compartir mi ubicación</button>
  <span id="ubicacion-estado" class="small muted">Ubicación apagada.</span>
 </div>
</div>
<div class="alert alert-warning mb-0"><strong>Seguridad:</strong> este detector es un prototipo de asistencia. No sustituye el descanso, la supervisión ni las medidas de seguridad vial.</div>

<script>
(function () {
    const api = <?= json_encode(PYTHON_API_URL) ?>;
    const texto = document.getElementById('estado-texto');
    const detalle = document.getElementById('estado-detalle');
    const aviso = document.getElementById('estado-aviso');
    const video = document.getElementById('camara-video');

    const mensajeCamara = document.getElementById('camara-mensaje');
    let reintentoProgramado = false;
    function conectarVideo() {
        if (!video.getAttribute('src')) {
            video.src = api + '/video_feed?t=' + Date.now();
        }
        video.style.display = 'block';
        mensajeCamara.style.display = 'none';
    }
    function desconectarVideo() {
        video.removeAttribute('src');
        video.style.display = 'none';
        mensajeCamara.style.display = 'block';
    }
    video.addEventListener('error', () => {
        aviso.textContent = 'No se recibió video. Se intentará reconectar mientras el detector esté activo.';
        if (!reintentoProgramado) {
            reintentoProgramado = true;
            setTimeout(() => {
                reintentoProgramado = false;
                if (video.style.display !== 'none') { video.src = api + '/video_feed?t=' + Date.now(); }
            }, 3000);
        }
    });

    async function actualizarEstado() {
        try {
            const respuesta = await fetch(api + '/estado', {cache: 'no-store'});
            if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
            const e = await respuesta.json();
            aviso.textContent = e.aviso || e.error || '';
            if (e.error) {
                desconectarVideo();
                texto.textContent = 'ERROR';
                texto.className = 'text-danger mt-2';
                detalle.textContent = e.error;
            } else if (!e.activa) {
                desconectarVideo();
                texto.textContent = e.aviso && e.aviso.indexOf('Iniciando') >= 0 ? 'INICIANDO' : 'DETENIDO';
                texto.className = 'text-secondary mt-2';
                detalle.textContent = e.aviso || 'Inicia el monitoreo para comenzar una sesión.';
            } else if (!e.rostro) {
                conectarVideo();
                texto.textContent = 'SIN ROSTRO';
                texto.className = 'text-warning mt-2';
                detalle.textContent = 'Colócate frente a la cámara.';
            } else if (e.alerta) {
                conectarVideo();
                texto.textContent = 'SOMNOLENCIA DETECTADA';
                texto.className = 'text-danger mt-2';
                detalle.textContent = 'EAR: ' + Number(e.ear).toFixed(3) + ' · cierre: ' + e.segundos_cerrados + ' s';
            } else {
                conectarVideo();
                texto.textContent = 'DESPIERTO';
                texto.className = 'text-success mt-2';
                detalle.textContent = 'EAR: ' + Number(e.ear).toFixed(3);
            }
        } catch (error) {
            desconectarVideo();
            texto.textContent = 'SIN CONEXIÓN';
            texto.className = 'text-secondary mt-2';
            detalle.textContent = 'La API Python no responde.';
        }
    }
    actualizarEstado();
    setInterval(actualizarEstado, 700);
})();
</script>
<script>
// Compartir ubicación con el administrador (se envía cada INTERVALO_MS).
(function () {
    const INTERVALO_MS = 15000;
    const btn = document.getElementById('btn-ubicacion');
    const estado = document.getElementById('ubicacion-estado');
    let watchId = null, timer = null, ultima = null;

    function enviar() {
        if (!ultima) return;
        fetch('ubicacion.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(ultima)
        })
        .then(r => r.json())
        .then(d => {
            estado.textContent = d.ok
                ? 'Ubicación enviada a las ' + new Date().toLocaleTimeString()
                : (d.message || 'No se pudo enviar.');
        })
        .catch(() => { estado.textContent = 'Sin conexión con el servidor. Reintentando…'; });
    }

    function iniciar() {
        if (!navigator.geolocation) {
            estado.textContent = 'Este navegador no soporta ubicación.';
            return;
        }
        estado.textContent = 'Obteniendo ubicación…';
        watchId = navigator.geolocation.watchPosition(
            pos => {
                ultima = {lat: pos.coords.latitude, lng: pos.coords.longitude, precision: pos.coords.accuracy};
                if (!timer) {
                    enviar();
                    timer = setInterval(enviar, INTERVALO_MS);
                }
            },
            err => {
                detener(false);
                estado.textContent = err.code === 1
                    ? 'Permiso denegado. Activa la ubicación para este sitio en el navegador.'
                    : 'No se pudo obtener la ubicación (' + err.message + ').';
            },
            {enableHighAccuracy: true, maximumAge: 5000, timeout: 20000}
        );
        btn.textContent = '■ Dejar de compartir';
        btn.className = 'btn btn-outline-danger btn-lg';
        try { localStorage.setItem('vigia_compartir', '1'); } catch (e) {}
    }

    function detener(mensaje) {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        if (timer) clearInterval(timer);
        watchId = null; timer = null; ultima = null;
        btn.textContent = ' Compartir mi ubicación';
        btn.className = 'btn btn-vigia btn-lg';
        if (mensaje !== false) estado.textContent = 'Ubicación apagada.';
        try { localStorage.removeItem('vigia_compartir'); } catch (e) {}
    }

    btn.addEventListener('click', () => { watchId === null ? iniciar() : detener(); });

    // Si lo tenía activado antes de recargar la página, se reanuda solo.
    try { if (localStorage.getItem('vigia_compartir') === '1') iniciar(); } catch (e) {}
})();
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
