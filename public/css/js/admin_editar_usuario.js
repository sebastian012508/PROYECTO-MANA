document.addEventListener('DOMContentLoaded', function () {
    // Función reutilizable para alternar visibilidad de contraseña (botón ojo)
    function configurarOjo(idInput, idBoton, idAbrir, idCerrar) {
        const input  = document.getElementById(idInput);
        const btn    = document.getElementById(idBoton);
        const abrir  = document.getElementById(idAbrir);
        const cerrar = document.getElementById(idCerrar);

        if (input && btn) {
            btn.addEventListener('click', function () {
                const esOculto = input.type === 'password';
                input.type = esOculto ? 'text' : 'password';
                if (abrir) abrir.classList.toggle('d-none', esOculto);
                if (cerrar) cerrar.classList.toggle('d-none', !esOculto);
            });
        }
    }

    // Configurar ojo en modal Editar y modal Eliminar
    configurarOjo('edit_password', 'btn-toggle-password-edit', 'icono-ojo-abrir-edit', 'icono-ojo-cerrar-edit');
    configurarOjo('delete_password_confirmacion', 'btn-toggle-password-delete', 'icono-ojo-abrir-delete', 'icono-ojo-cerrar-delete');

    // 1. Cargar datos en el modal de Edición
    const botonesEditar = document.querySelectorAll('.btn-editar-conductor');
    const campoId       = document.getElementById('edit_id_usuario');
    const campoNombre   = document.getElementById('edit_nombre');
    const campoUsuario  = document.getElementById('edit_usuario');
    const campoCorreo   = document.getElementById('edit_correo');
    const campoLicencia = document.getElementById('edit_tipo_licencia');
    const campoActivo   = document.getElementById('edit_activo');
    const inputPassEdit = document.getElementById('edit_password');
    const ojoAbrirEdit  = document.getElementById('icono-ojo-abrir-edit');
    const ojoCerrarEdit = document.getElementById('icono-ojo-cerrar-edit');

    botonesEditar.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (campoId) campoId.value = btn.getAttribute('data-id') || '';
            if (campoNombre) campoNombre.value = btn.getAttribute('data-nombre') || '';
            if (campoUsuario) campoUsuario.value = btn.getAttribute('data-usuario') || '';
            if (campoCorreo) campoCorreo.value = btn.getAttribute('data-correo') || '';
            if (campoLicencia) campoLicencia.value = btn.getAttribute('data-licencia') || '';
            if (campoActivo) campoActivo.value = btn.getAttribute('data-activo') || '1';

            if (inputPassEdit) {
                inputPassEdit.value = '';
                inputPassEdit.type = 'password';
            }
            if (ojoAbrirEdit) ojoAbrirEdit.classList.remove('d-none');
            if (ojoCerrarEdit) ojoCerrarEdit.classList.add('d-none');
        });
    });

    // 2. Cargar datos en el modal de Eliminación definitiva
    const botonesEliminar   = document.querySelectorAll('.btn-eliminar-conductor');
    const delCampoId        = document.getElementById('delete_id_usuario');
    const delSpanCuenta     = document.getElementById('delete_nombre_cuenta');
    const delSpanNombre     = document.getElementById('delete_nombre_completo');
    const delInputPass      = document.getElementById('delete_password_confirmacion');
    const ojoAbrirDelete    = document.getElementById('icono-ojo-abrir-delete');
    const ojoCerrarDelete   = document.getElementById('icono-ojo-cerrar-delete');

    botonesEliminar.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (delCampoId) delCampoId.value = btn.getAttribute('data-id') || '';
            if (delSpanCuenta) delSpanCuenta.textContent = btn.getAttribute('data-usuario') || '';
            if (delSpanNombre) delSpanNombre.textContent = btn.getAttribute('data-nombre') || '';

            if (delInputPass) {
                delInputPass.value = '';
                delInputPass.type = 'password';
            }
            if (ojoAbrirDelete) ojoAbrirDelete.classList.remove('d-none');
            if (ojoCerrarDelete) ojoCerrarDelete.classList.add('d-none');
        });
    });
});