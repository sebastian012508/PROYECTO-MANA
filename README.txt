VIGÍA - SISTEMA DE DETECCIÓN DE SOMNOLENCIA
Versión revisada y corregida

1. UBICACIÓN
Extrae la carpeta PROYECTO_MVC_CORREGIDO dentro de:
C:\xampp\htdocs\

Abre el sistema desde:
http://localhost/PROYECTO_MVC_CORREGIDO/

Puedes cambiar el nombre de la carpeta. Los enlaces internos PHP calculan la ruta base.

2. BASE DE DATOS
Inicia MySQL en XAMPP. Entra a phpMyAdmin e importa:
database/database.sql

El script crea y utiliza la base:
sistema_somnolencia

No cambies solo una conexión. Si usas otra base, cambia el mismo nombre en:
- config/db.php
- detector_api/config.py

Si ya tienes datos en otra base, no la elimines. Haz una copia/exportación antes de migrar.

3. CONFIGURACIÓN PHP
Abre config/db.php y revisa:
- DB_HOST
- DB_NAME (sistema_somnolencia)
- DB_USER
- DB_PASS

Asegúrate de que PHP tenga habilitadas las extensiones PDO MySQL y cURL.

4. CLAVE ENTRE PHP Y PYTHON
La clave API debe ser idéntica en:
- config/app.php -> API_SECRET
- detector_api/config.py -> API_SECRET

La clave incluida es solo un valor inicial para desarrollo local. Cámbiala por una cadena privada igual en ambos archivos.

5. CREAR EL PRIMER ADMINISTRADOR
Con Apache y MySQL encendidos, abre:
http://localhost/PROYECTO_MVC_CORREGIDO/views/auth/crear_admin.php
Crea el primer administrador. Después elimina o bloquea ese archivo para que no quede públicamente accesible.

6. PREPARAR PYTHON
Abre PowerShell en:
C:\xampp\htdocs\PROYECTO_MVC_CORREGIDO\detector_api

Ejecuta:
py -3.11 -m venv .venv
.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt

Si PowerShell bloquea la activación, ejecuta para esa terminal:
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.venv\Scripts\Activate.ps1

7. INICIAR LA API
Desde la carpeta detector_api y con el entorno virtual activado:
python app.py

Mantén esa terminal abierta. Flask escucha en 127.0.0.1:5001.

8. PROBAR EL FLUJO
- Inicia sesión como conductor desde la página principal.
- En el panel pulsa Iniciar monitoreo.
- La API abre la cámara y crea una fila en sesiones con el id_usuario del conductor.
- El video se visualiza en el panel.
- Al detectar un cierre ocular superior al umbral de tiempo, inserta una fila en alertas y aumenta sesiones.cantidad_alertas.
- Pulsa Detener monitoreo para cerrar fecha_fin, duracion_segundos y estado.
- Abre Ver mi historial. Selecciona Ver alertas para consultar los eventos de esa sesión.

NOTAS IMPORTANTES
- La cámara se abre en la computadora donde corre Python. Esta versión es local.
- No cierres la terminal de Flask durante un monitoreo.
- Usa el botón Detener antes de cerrar el navegador o apagar el equipo. Si se fuerza el cierre de Python o Windows, una sesión puede quedar EN_CURSO.
- El detector guarda un registro por cada episodio de ojos cerrados durante el tiempo mínimo. El pitido puede repetirse varias veces durante el mismo episodio; no crea una fila por cada pitido.
- No distribuyas la carpeta .venv. Se crea localmente con los comandos anteriores.
