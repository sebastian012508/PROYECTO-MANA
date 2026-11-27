document.addEventListener('DOMContentLoaded', function () {
    const botonesVer   = document.querySelectorAll('.btn-ver-imagen-vehiculo');
    const imgElemento  = document.getElementById('modal_vehiculo_img');
    const sinImagenMsg = document.getElementById('modal_vehiculo_sin_img');
    const spanTitulo   = document.getElementById('modal_vehiculo_titulo');
    const spanDetalle  = document.getElementById('modal_vehiculo_detalle');

    botonesVer.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const urlImg   = btn.getAttribute('data-imagen') || '';
            const codigo   = btn.getAttribute('data-codigo') || '';
            const modelo   = btn.getAttribute('data-modelo') || '';
            const placa    = btn.getAttribute('data-placa') || '';
            const color    = btn.getAttribute('data-color') || 'No especificado';

            if (spanTitulo) {
                spanTitulo.textContent = codigo + ' — ' + modelo;
            }
            if (spanDetalle) {
                spanDetalle.textContent = 'Placa: ' + placa + ' | Color: ' + color;
            }

            if (urlImg !== '' && imgElemento) {
                imgElemento.src = urlImg;
                imgElemento.alt = 'Imagen de ' + modelo;
                imgElemento.classList.remove('d-none');
                if (sinImagenMsg) sinImagenMsg.classList.add('d-none');
            } else {
                if (imgElemento) {
                    imgElemento.src = '';
                    imgElemento.classList.add('d-none');
                }
                if (sinImagenMsg) sinImagenMsg.classList.remove('d-none');
            }
        });
    });
});