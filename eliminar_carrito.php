<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: inicio_sesion.php');
    exit();
}

// Antes bastaba con abrir eliminar_carrito.php?id=N desde cualquier sitio.
csrf_validar();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    header('Location: carrito.php');
    exit();
}

$id_cliente = (int) $_SESSION['cliente_id'];
$id_carrito = (int) $_POST['id'];

// El AND id_cliente impide borrar lineas del carrito de otra persona.
$eliminar = $conn->prepare("DELETE FROM carrito WHERE id_carrito = ? AND id_cliente = ?");
$eliminar->bind_param("ii", $id_carrito, $id_cliente);
$eliminar->execute();
$eliminar->close();

header('Location: carrito.php');
exit();
