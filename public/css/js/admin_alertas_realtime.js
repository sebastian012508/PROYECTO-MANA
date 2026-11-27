document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('contenedor-notificaciones-realtime');
    if (!contenedor) return;

    const endpointUrl = contenedor.getAttribute('data-endpoint') || 'api_alertas_realtime.php';
    const contadorCardAlertas = document.getElementById('kpi-total-alertas');
    const btnAudio = document.getElementById('btn-estado-audio-alertas');

    let ultimoIdConocido = -1;
    let audioHabilitado = true;
    let audioCtx = null;

    // Desbloquear AudioContext en la primera interacción del usuario con la página
    function desbloquearAudio() {
        if (!audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioCtx = new AudioContextClass();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(function () {});
        }
    }

    document.addEventListener('click', desbloquearAudio, { once: true });
    document.addEventListener('keydown', desbloquearAudio, { once: true });

    if (btnAudio) {
        btnAudio.addEventListener('click', function () {
            audioHabilitado = !audioHabilitado;
            desbloquearAudio();
            btnAudio.textContent = audioHabilitado ? 'Sonido de alertas: Activado' : 'Sonido de alertas: Silenciado';
            btnAudio.classList.toggle('btn-outline-info', audioHabilitado);
            btnAudio.classList.toggle('btn-outline-secondary', !audioHabilitado);
        });
    }

    // Emitir tono de alerta + voz "Una nueva alerta se ha activado"
    function reproducirAvisoSonoro() {
        if (!audioHabilitado) return;

        try {
            desbloquearAudio();
            if (audioCtx) {
                const ahora = audioCtx.currentTime;
                // Doble pitido de atención
                [0, 0.22].forEach(function (offset) {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ahora + offset);
                    gain.gain.setValueAtTime(0.18, ahora + offset);
                    gain.gain.exponentialRampToValueAtTime(0.001, ahora + offset + 0.18);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(ahora + offset);
                    osc.stop(ahora + offset + 0.18);
                });
            }
        } catch (e) {
            // Ignorar si el navegador aún no permite AudioContext
        }

        // Síntesis de voz en español
        if ('speechSynthesis' in window) {
            try {
                window.speechSynthesis.cancel();
                const mensajeVoz = new SpeechSynthesisUtterance('Una nueva alerta se ha activado');
                mensajeVoz.lang = 'es-ES';
                mensajeVoz.rate = 1.02;
                mensajeVoz.pitch = 1.0;
                window.speechSynthesis.speak(mensajeVoz);
            } catch (e) {
                // Ignorar si speechSynthesis no está disponible
            }
        }
    }

    // Crear tarjeta de notificación en el panel derecho
    function mostrarNotificacionDerecha(alerta) {
        const tarjeta = document.createElement('div');
        tarjeta.className = 'alert alert-danger shadow-lg border border-danger mb-2 p-3';
        tarjeta.style.backgroundColor = '#140c18';
        tarjeta.style.color = '#ffffff';
        tarjeta.style.borderLeft = '5px solid #ff2a6d';

        const cabecera = document.createElement('div');
        cabecera.className = 'd-flex justify-content-between align-items-center mb-1';

        const titulo = document.createElement('strong');
        titulo.className = 'text-danger';
        titulo.textContent = 'Nueva alerta activada (#' + alerta.id_alerta + ')';

        const btnCerrar = document.createElement('button');
        btnCerrar.type = 'button';
        btnCerrar.className = 'btn-close btn-close-white btn-sm';
        btnCerrar.setAttribute('aria-label', 'Cerrar');
        btnCerrar.addEventListener('click', function () {
            tarjeta.remove();
        });

        cabecera.appendChild(titulo);
        cabecera.appendChild(btnCerrar);

        const cuerpo = document.createElement('div');
        cuerpo.className = 'small';
        cuerpo.innerHTML =
            '<div><strong>Conductor:</strong> ' + escaparHtml(alerta.nombre_conductor) + '</div>' +
            '<div><strong>Vehículo:</strong> ' + escaparHtml(alerta.placa_vehiculo) + '</div>' +
            '<div><strong>Nivel:</strong> ' + escaparHtml(alerta.nivel_alerta) + '</div>' +
            '<div class="text-muted mt-1">Hora: ' + escaparHtml(alerta.hora_formateada) + '</div>';

        tarjeta.appendChild(cabecera);
        tarjeta.appendChild(cuerpo);
        contenedor.prepend(tarjeta);

        // Limitar a máximo 5 notificaciones visibles simultáneamente
        while (contenedor.children.length > 5) {
            contenedor.lastElementChild.remove();
        }
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = String(texto ?? '');
        return div.innerHTML;
    }

    async function verificarNuevasAlertas() {
        try {
            const sep = endpointUrl.includes('?') ? '&' : '?';
            const resp = await fetch(endpointUrl + sep + 'ultimo_id=' + encodeURIComponent(ultimoIdConocido), {
                headers: { 'Accept': 'application/json' }
            });
            if (!resp.ok) return;

            const data = await resp.json();
            if (!data || !data.ok) return;

            if (contadorCardAlertas && typeof data.total_alertas === 'number') {
                contadorCardAlertas.textContent = String(data.total_alertas);
            }

            // Primera sincronización: solo fijar el ID base sin disparar alertas pasadas
            if (ultimoIdConocido < 0) {
                ultimoIdConocido = Number(data.ultimo_id) || 0;
                return;
            }

            if (Array.isArray(data.nuevas) && data.nuevas.length > 0) {
                ultimoIdConocido = Number(data.ultimo_id) || ultimoIdConocido;
                data.nuevas.forEach(function (alerta) {
                    mostrarNotificacionDerecha(alerta);
                });
                reproducirAvisoSonoro();
            }
        } catch (err) {
            // Reintentar en el siguiente ciclo silenciosamente
        }
    }

    // Iniciar monitoreo en tiempo real cada 1,5 segundos
    verificarNuevasAlertas();
    setInterval(verificarNuevasAlertas, 1500);
});