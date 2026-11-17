<?php
require_once __DIR__ . '/../../app/helpers/auth.php';
require_role('ADMIN');

$pageTitle = 'Mapa de conductores';
require __DIR__ . '/../layouts/header.php';
?>
<!-- Leaflet (el header compartido no tiene espacio para CSS extra, por eso va aquí) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
 <div>
  <div class="text-uppercase small text-success fw-bold">Administración</div>
  <h1 class="fw-bold">Mapa de conductores</h1>
  <p class="muted mb-0">Ubicación compartida por los conductores. </p>
 </div>
 <a href="index.php" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="row g-3">
 <div class="col-lg-9">
  <div class="card panel-card p-2">
   <div id="mapa" style="height:70vh;border-radius:14px"></div>
  </div>
 </div>
 <div class="col-lg-3">
  <div class="card panel-card p-3">
   <h5 class="mb-1">Conductores</h5>
   <div id="actualizado" class="small muted mb-3">Cargando…</div>
   <div id="lista" class="d-grid gap-2"></div>
  </div>
 </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const URL_DATOS = 'ubicaciones.php';
    const REFRESCO_MS = 10000;
    const ACTIVO_SEG = 120; // más de 2 min sin enviar = "sin señal"

    // Centro inicial: La Paz, Bolivia (se reajusta cuando llegan conductores)
    const mapa = L.map('mapa').setView([-16.5, -68.15], 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; Colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);

    const marcadores = {};
    const rutas = {};
    let primeraCarga = true;

    function esc(t) {
        const d = document.createElement('div');
        d.textContent = (t === null || t === undefined) ? '' : t;
        return d.innerHTML;
    }
    function hace(seg) {
        seg = Number(seg) || 0;
        if (seg < 60) return 'hace ' + seg + ' s';
        if (seg < 3600) return 'hace ' + Math.floor(seg / 60) + ' min';
        return 'hace ' + Math.floor(seg / 3600) + ' h';
    }

    async function cargar() {
        const lista = document.getElementById('lista');
        const estado = document.getElementById('actualizado');
        try {
            const r = await fetch(URL_DATOS, {cache: 'no-store', credentials: 'same-origin'});
            const datos = await r.json();
            if (!datos.ok) throw new Error(datos.message || 'Error');

            const vistos = new Set();
            const puntosValidos = [];
            lista.innerHTML = '';

            (datos.conductores || []).forEach(c => {
                const id = String(c.id_usuario);
                const lat = Number(c.latitud);
                const lng = Number(c.longitud);
                if (!isFinite(lat) || !isFinite(lng)) return; // omite datos inválidos

                vistos.add(id);
                const segundos = Number(c.segundos) || 0;
                const activo = segundos <= ACTIVO_SEG;
                const color = activo ? '#16a38c' : '#8a94a6';
                const pos = [lat, lng];
                const detalle = esc(c.nombre) + '<br>Última señal: ' + hace(segundos);
                const ruta = (Array.isArray(c.ruta) && c.ruta.length) ? c.ruta : [pos];
                puntosValidos.push(pos);

                if (!marcadores[id]) {
                    marcadores[id] = L.circleMarker(pos, {radius: 11, weight: 3, color: '#fff', fillOpacity: 1})
                        .addTo(mapa)
                        .bindTooltip(esc(c.nombre), {permanent: true, direction: 'top', offset: [0, -10]})
                        .bindPopup(detalle);
                    rutas[id] = L.polyline([], {weight: 4, opacity: 0.7}).addTo(mapa);
                }
                marcadores[id].setLatLng(pos).setStyle({fillColor: color});
                marcadores[id].setPopupContent(detalle);
                rutas[id].setLatLngs(ruta).setStyle({color: color});

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'btn btn-light text-start border';
                item.innerHTML = '<span class="fw-bold">' + esc(c.nombre) + '</span><br>' +
                    '<span class="small" style="color:' + color + '">' + (activo ? '● En línea' : '● Sin señal') + '</span>' +
                    ' <span class="small muted">· ' + hace(segundos) + '</span>';
                item.addEventListener('click', () => {
                    mapa.flyTo(pos, 16);
                    marcadores[id].openPopup();
                });
                lista.appendChild(item);
            });

            // Quita del mapa a los que ya no aparecen en la ventana de tiempo
            Object.keys(marcadores).forEach(id => {
                if (!vistos.has(id)) {
                    mapa.removeLayer(marcadores[id]);
                    mapa.removeLayer(rutas[id]);
                    delete marcadores[id];
                    delete rutas[id];
                }
            });

            if (!puntosValidos.length) {
                lista.innerHTML = '<div class="muted small">Ningún conductor ha compartido su ubicación en las últimas 6 horas.</div>';
            } else if (primeraCarga) {
                mapa.fitBounds(puntosValidos, {padding: [60, 60], maxZoom: 16});
                primeraCarga = false;
            }
            estado.textContent = 'Actualizado ' + new Date().toLocaleTimeString();
        } catch (e) {
            estado.textContent = 'No se pudo actualizar: ' + e.message;
        } finally {
            setTimeout(cargar, REFRESCO_MS);
        }
    }

    cargar();
})();
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>