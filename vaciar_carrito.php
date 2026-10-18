<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: inicio_sesion.php');
    exit();
}

// Antes bastaba con abrir la URL para vaciarle el carrito a alguien.
csrf_validar();

$id_cliente = (int) $_SESSION['cliente_id'];

$stmt = $conn->prepare("DELETE FROM carrito WHERE id_cliente = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$stmt->close();

header('Location: carrito.php');
exit();
