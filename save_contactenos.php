<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// El formulario de contacto.php envia el token.
csrf_validar();

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre_contacto = $_POST['nombre_contacto'];
    $apellido_contacto = $_POST['apellido_contacto'];
    $correo_contacto = $_POST['correo_contacto'];
    $comentario = $_POST['comentario'];

    $sql = "INSERT INTO contacto (nombre_contacto, apellido_contacto, correo_contacto, comentario) 
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre_contacto, $apellido_contacto, $correo_contacto, $comentario);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // 🔄 Redirige inmediatamente a la página principal con mensaje de éxito
    header("Location: index.php?mensaje=enviado");
    exit();
}
?>
