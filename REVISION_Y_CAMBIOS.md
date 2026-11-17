# Revisión técnica del proyecto Vigía

## Hallazgos en el ZIP original

1. **Base de datos inconsistente.** `database/database.sql` crea `sistema_somnolencia`, pero `config/db.php`, `config/conexion.php` y `detector_api/config.py` apuntaban a `somnolencia_db`. PHP y Python podían conectarse a una base distinta de la que se importó.
2. **Dos sistemas de autenticación mezclados.** `views/login.php` era la versión antigua y consultaba `usuarios.password`; el esquema actual usa `usuarios.password_hash`. Además tomaba `rol` desde el formulario, en lugar de leerlo desde MariaDB.
3. **Clave API antigua incorrecta.** `views/control_detector.php` enviaba `clave_secreta_vigia_2026`, mientras Flask esperaba el valor de `detector_api/config.py`. Flask rechazaba esa petición con HTTP 401. Así el video podía abrirse sin asociar un `user_id` válido ni crear una sesión.
4. **Inicio incompleto en la versión MVC.** El endpoint `/start` solo asignaba el usuario. La cámara se arrancaba únicamente cuando un navegador solicitaba `/video_feed`. El panel nuevo no contenía una imagen que solicitara ese endpoint, por lo que el hilo de cámara y el registro no comenzaban.
5. **Historial duplicado.** `views/mi_historial.php` era la implementación antigua y `views/conductor/historial.php` la nueva. El primer archivo hacía detección dinámica de columnas y, si no encontraba `id_usuario`, podía consultar todas las sesiones; no debía utilizarse como historial del conductor.
6. **Rutas internas rotas.** La cabecera enlazaba a `/PROYECTO/views/index.php`, archivo inexistente, y otros enlaces dependían de un nombre fijo de carpeta. `index.html` enviaba al login antiguo.
7. **Archivos obsoletos o duplicados.** Había dos copias idénticas de `database.sql`, tres estilos usados por las pantallas antiguas y un controlador de correo incompleto que requería `vendor/autoload.php` y un `config.php` no presentes.
8. **Python duplicado.** `detector.py` contenía otra implementación de sesión/cámara, pero Flask utilizaba `camara.py`. La implementación antigua no era el proceso lanzado por Flask; se conservó únicamente el cálculo EAR y los índices que `camara.py` necesita.
9. **Imports de MediaPipe frágiles.** El código importaba directamente `mediapipe.python.solutions`; ahora utiliza la interfaz `mp.solutions`, que ya fue comprobada en el entorno de trabajo previo.
10. **Documentación contradictoria.** El README indicaba que Flask lanzaba `detector.py` como proceso separado, lo cual no coincidía con `app.py` ni `camara.py`.

## Cambios incluidos en esta versión

- Se unificó el nombre de la base de datos en PHP y Python: `sistema_somnolencia`.
- Se eliminó la autenticación antigua y el panel antiguo; el login valida `password_hash` y el rol proviene de la fila del usuario en la base.
- Se centralizó el inicio de sesión PHP y se reforzaron los parámetros de la cookie de sesión.
- Se añadieron modelos y controladores para autenticación, administración, API del detector e historial, separando las consultas y operaciones de las vistas.
- Se ajustaron las rutas del sitio para no depender de que la carpeta se llame `PROYECTO`.
- `/start` ahora inicia el hilo de cámara directamente. Primero abre la cámara, verifica MariaDB y crea el registro de sesión con el `id_usuario` recibido.
- `/video_feed` únicamente transmite imágenes; abrir o cerrar la imagen ya no inicia ni detiene una sesión.
- `/stop` señala al hilo para terminar y este actualiza `fecha_fin`, `duracion_segundos`, `cantidad_alertas` y `estado`.
- El guardado de alertas hace `commit` y solo después aumenta el contador en memoria. Ante un error realiza `rollback` y publica el mensaje en `/estado`.
- El historial filtra por el usuario autenticado. El detalle de alertas comprueba tanto el `id_sesion` como el `id_usuario`.
- Se retiraron entornos virtuales, cachés, SQL duplicado, pantallas antiguas y archivos no utilizados.

## Estructura recomendada

```text
PROYECTO_MVC_CORREGIDO/
├── app/
│   ├── controllers/
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── DetectorController.php
│   │   └── HistorialController.php
│   ├── helpers/
│   │   └── auth.php
│   └── models/
│       ├── HistorialModel.php
│       └── UsuarioModel.php
├── config/
│   ├── app.php
│   └── db.php
├── database/
│   └── database.sql
├── detector_api/
│   ├── app.py
│   ├── camara.py
│   ├── config.py
│   ├── detector.py
│   └── requirements.txt
├── public/css/
│   ├── diseño.css
│   └── style.css
├── views/
│   ├── admin/
│   ├── auth/
│   ├── conductor/
│   └── layouts/
├── index.html
└── README.txt
```

## Qué se considera una alerta

El detector registra **un evento por episodio de cierre prolongado**. La alarma sonora puede emitir varios pitidos mientras los ojos siguen cerrados; esos pitidos repetidos no se insertan como alertas distintas. Para contar cada pitido se necesitaría una tabla/evento diferente, pero normalmente es mejor contar el episodio de somnolencia.

## Comprobaciones realizadas aquí

- Se revisó la estructura y el contenido de los archivos de PHP, Python, SQL, HTML y CSS incluidos en el ZIP.
- Todos los archivos PHP pasaron `php -l` en el entorno de análisis original.
- El código Python original pasó la compilación sintáctica (`compileall`).
- La copia corregida se vuelve a validar antes de entregarse.

## Lo que no se puede confirmar sin tu XAMPP

No se puede probar desde este entorno la conexión real a tu MariaDB, la cámara física ni el flujo de video en el navegador de tu computadora. Debes realizar la prueba local descrita en README.txt y revisar la terminal de Flask para los mensajes `[BD OK]` o `[DETECTOR ERROR]`.
