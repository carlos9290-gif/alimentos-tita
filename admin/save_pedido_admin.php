<?php
require_once __DIR__ . '/../seguridad.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

csrf_validar();

include __DIR__ . '/../conexion.php';

// Estados permitidos: deben coincidir con el ENUM de la tabla pedido.
$ESTADOS = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];

$id_pedido = $_POST['id_pedido'] ?? '';
$estado    = $_POST['estado']    ?? '';

if (!ctype_digit((string) $id_pedido) || !in_array($estado, $ESTADOS, true)) {
    header('Location: pedido_admin.php');
    exit();
}

$id_pedido = (int) $id_pedido;

$stmt = $conn->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");
$stmt->bind_param("si", $estado, $id_pedido);
$stmt->execute();
$stmt->close();

header('Location: pedido_admin.php');
exit();
