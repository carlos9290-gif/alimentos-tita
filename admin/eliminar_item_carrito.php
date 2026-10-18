<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

// Borrar es destructivo: exige POST con token valido.
csrf_validar();

if (!isset($_POST['id']) || !ctype_digit((string) $_POST['id'])) {
    header('Location: index_admin.php');
    exit();
}

$id_carrito = (int) $_POST['id'];

$eliminar = $conn->prepare("DELETE FROM carrito WHERE id_carrito = ?");
$eliminar->bind_param("i", $id_carrito);
$eliminar->execute();
$eliminar->close();

// NOTA: antes redirigía a ver_carritos.php / ver_carrito.php, que no
// existen en el proyecto. Tampoco hay ninguna página del panel que enlace
// aquí todavía: este endpoint queda listo pero sin usar.
header('Location: index_admin.php');
exit();
