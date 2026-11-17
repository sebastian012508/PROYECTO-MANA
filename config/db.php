<?php
// La base definida en database/database.sql se llama sistema_somnolencia.
$DB_HOST = '127.0.0.1';
$DB_NAME = 'sistema_somnolencia';
$DB_USER = 'root';
$DB_PASS = ''; // Cambia si tu usuario MariaDB tiene contraseña.

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[VIGIA DB] ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con MariaDB. Verifica que MySQL esté iniciado y que config/db.php use la base correcta.');
}
