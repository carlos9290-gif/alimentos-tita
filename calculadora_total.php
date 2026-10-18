<?php
include 'conexion.php';

// Obtener id_pedido desde GET
$id_pedido = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;

if ($id_pedido <= 0) {
    header('Content-Type: application/json');
    echo json_encode(array());
    $conn->close();
    exit();
}

// Prepared statement para obtener subtotales de un pedido específico
$sql = "SELECT d.subtotal, p.nombre
        FROM detalle_pedido d
        INNER JOIN producto p ON d.id_producto = p.id_producto
        WHERE d.id_pedido = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(array());
    $conn->close();
    exit();
}

$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

$productos = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = array(
            "nombre" => $row["nombre"],
            "subtotal" => $row["subtotal"]
        );
    }
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($productos);

$conn->close();
?>