<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// ===============================
// 1. VALIDAR SESIÓN
// ===============================
if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$id_cliente = (int) $_SESSION['cliente_id'];

/** Vuelve al carrito con un aviso. */
function volver_carrito(string $clave, string $texto): void
{
    $_SESSION[$clave] = $texto;
    header("Location: carrito.php");
    exit();
}

// ===============================
// 2. VALIDAR PARÁMETROS
// ===============================
if (!isset($_GET['id_producto']) || !ctype_digit((string) $_GET['id_producto'])) {
    volver_carrito('carrito_error', '❌ No se recibió el producto.');
}

$id_producto = (int) $_GET['id_producto'];
$cantidad    = isset($_GET['cantidad']) ? (int) $_GET['cantidad'] : 1;

if ($cantidad < 1) {
    $cantidad = 1;
}

// ===============================
// 3. EL PRODUCTO DEBE EXISTIR Y ESTAR ACTIVO
// ===============================
$checkProd = $conn->prepare(
    "SELECT nombre, stock, estado_producto FROM producto WHERE id_producto = ?"
);
$checkProd->bind_param("i", $id_producto);
$checkProd->execute();
$producto = $checkProd->get_result()->fetch_assoc();
$checkProd->close();

if (!$producto) {
    volver_carrito('carrito_error', '❌ El producto no existe.');
}

if (strcasecmp($producto['estado_producto'], 'activo') !== 0) {
    volver_carrito('carrito_error', "❌ \"{$producto['nombre']}\" no está disponible en este momento.");
}

$stock = (int) $producto['stock'];

if ($stock <= 0) {
    volver_carrito('carrito_error', "❌ \"{$producto['nombre']}\" está agotado.");
}

// ===============================
// 4. SUMAR A LO QUE YA HAYA EN EL CARRITO
// ===============================
$consulta = $conn->prepare("SELECT cantidad FROM carrito WHERE id_cliente = ? AND id_producto = ?");
$consulta->bind_param("ii", $id_cliente, $id_producto);
$consulta->execute();
$fila = $consulta->get_result()->fetch_assoc();
$consulta->close();

$ya_en_carrito = $fila ? (int) $fila['cantidad'] : 0;
$deseada       = $ya_en_carrito + $cantidad;

// No se deja meter al carrito más de lo que hay en bodega: así el
// cliente se entera aquí y no al final, al confirmar el pedido.
$recortado = false;
if ($deseada > $stock) {
    $deseada   = $stock;
    $recortado = true;
}

if ($deseada === $ya_en_carrito) {
    volver_carrito(
        'carrito_error',
        "❌ Ya tienes el máximo disponible de \"{$producto['nombre']}\" en el carrito ($stock)."
    );
}

if ($fila) {
    $actualizar = $conn->prepare(
        "UPDATE carrito SET cantidad = ? WHERE id_cliente = ? AND id_producto = ?"
    );
    $actualizar->bind_param("iii", $deseada, $id_cliente, $id_producto);
    $actualizar->execute();
    $actualizar->close();
} else {
    $insertar = $conn->prepare(
        "INSERT INTO carrito (id_cliente, id_producto, cantidad) VALUES (?, ?, ?)"
    );
    $insertar->bind_param("iii", $id_cliente, $id_producto, $deseada);
    $insertar->execute();
    $insertar->close();
}

// ===============================
// 5. REDIRECCIÓN FINAL
// ===============================
if ($recortado) {
    volver_carrito(
        'carrito_error',
        "⚠️ Solo quedan $stock unidades de \"{$producto['nombre']}\": se ajustó la cantidad."
    );
}

volver_carrito('carrito_ok', "✅ \"{$producto['nombre']}\" agregado al carrito.");
