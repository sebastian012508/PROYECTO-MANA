"""Configuración de MariaDB y del detector local."""
DB_HOST = "127.0.0.1"
DB_PORT = 3306
DB_NAME = "sistema_somnolencia"  # Debe coincidir con database/database.sql y config/db.php
DB_USER = "root"
DB_PASSWORD = ""

# Debe coincidir exactamente con API_SECRET en config/app.php.
API_SECRET = "CAMBIA_ESTA_CLAVE_LARGA_EN_PHP_Y_PYTHON_2026"

CAMERA_INDEX = 0
EAR_THRESHOLD = 0.20
DROWSY_SECONDS = 2.0
MAR_THRESHOLD = 0.65
YAWN_SECONDS = 1.5
ALARM_FREQUENCY = 3500
ALARM_DURATION_MS = 215
