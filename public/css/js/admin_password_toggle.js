(function () {
    function iniciar() {
        const inputPassword = document.getElementById('password_conductor');
        const btnToggle     = document.getElementById('btn-toggle-password-admin');
        const ojoAbrir      = document.getElementById('icono-ojo-abrir-admin');
        const ojoCerrar     = document.getElementById('icono-ojo-cerrar-admin');

        if (!inputPassword || !btnToggle) return;

        btnToggle.addEventListener('click', function () {
            const esOculto = inputPassword.type === 'password';
            inputPassword.type = esOculto ? 'text' : 'password';
            if (ojoAbrir) ojoAbrir.classList.toggle('d-none', esOculto);
            if (ojoCerrar) ojoCerrar.classList.toggle('d-none', !esOculto);
            btnToggle.setAttribute('aria-pressed', esOculto ? 'true' : 'false');
            inputPassword.focus();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
