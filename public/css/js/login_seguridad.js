document.addEventListener('DOMContentLoaded', function () {
    // 1. Botón de ojo para mostrar / ocultar contraseña
    const inputPassword = document.getElementById('password');
    const btnToggle     = document.getElementById('btn-toggle-password');
    const ojoAbrir      = document.getElementById('icono-ojo-abrir');
    const ojoCerrar     = document.getElementById('icono-ojo-cerrar');

    if (inputPassword && btnToggle) {
        btnToggle.addEventListener('click', function () {
            const esOculto = inputPassword.type === 'password';
            inputPassword.type = esOculto ? 'text' : 'password';
            if (ojoAbrir) ojoAbrir.classList.toggle('d-none', esOculto);
            if (ojoCerrar) ojoCerrar.classList.toggle('d-none', !esOculto);
        });
    }

    // 2. Contador regresivo en tiempo real para bloqueo temporal
    const spanContador = document.getElementById('contador-bloqueo');
    if (!spanContador) return;

    let segundos = parseInt(spanContador.getAttribute('data-segundos'), 10);
    if (isNaN(segundos) || segundos <= 0) return;

    const alerta       = document.getElementById('alerta-login');
    const inputUsuario = document.getElementById('usuario');
    const btnIngresar  = document.getElementById('btn-ingresar');

    function formatearTiempo(s) {
        if (s < 60) return s + ' segundo(s)';
        const min = Math.floor(s / 60);
        const seg = s % 60;
        return min + ' min ' + (seg < 10 ? '0' : '') + seg + ' s';
    }

    // Mostrar formato inicial inmediatamente
    spanContador.textContent = formatearTiempo(segundos);

    // Calcular hora exacta de fin para que no se desfase si cambian de pestaña
    const finTimestamp = Date.now() + (segundos * 1000);

    const intervalo = setInterval(function () {
        const restantes = Math.ceil((finTimestamp - Date.now()) / 1000);

        if (restantes > 0) {
            spanContador.textContent = formatearTiempo(restantes);
        } else {
            clearInterval(intervalo);
            if (inputUsuario) inputUsuario.disabled = false;
            if (inputPassword) inputPassword.disabled = false;
            if (btnToggle) btnToggle.disabled = false;
            if (btnIngresar) {
                btnIngresar.disabled = false;
                btnIngresar.textContent = 'Ingresar';
            }
            if (alerta) {
                alerta.className = 'alert alert-info';
                alerta.textContent = 'El tiempo de espera terminó. Ya puedes volver a intentar iniciar sesión.';
            }
            if (inputPassword) inputPassword.focus();
        }
    }, 250);
});