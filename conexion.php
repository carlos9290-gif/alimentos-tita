<?php
/**
 * Conexión a MySQL.
 *
 * Las credenciales ya no viven aquí: se leen de config.php, que está
 * fuera del control de versiones (ver config.php.example).
 */

$rutaConfig = __DIR__ . '/config.php';

if (!file_exists($rutaConfig)) {
    die(
        '❌ Falta el archivo de configuración.<br>' .
        'Copia <code>config.php.example</code> como <code>config.php</code> ' .
        'y ajusta los datos de tu base de datos.'
    );
}

$cfg = require $rutaConfig;

$conn = @new mysqli(
    $cfg['host'] . ':' . $cfg['puerto'],
    $cfg['usuario'],
    $cfg['password'],
    $cfg['base']
);

if ($conn->connect_error) {
    // No se muestra el detalle del error: puede revelar usuario, host o
    // versión del motor. El detalle queda en el log del servidor.
    error_log('Error de conexión a MySQL: ' . $conn->connect_error);
    die('❌ No se pudo conectar con la base de datos. Revisa que MySQL esté encendido.');
}

$conn->set_charset($cfg['charset'] ?? 'utf8mb4');
