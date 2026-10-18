<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// El formulario de pedido.php envia el token.
csrf_validar();

if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$id_cliente      = (int) $_SESSION['cliente_id'];
$direccion_envio = trim($_POST['direccion_envio'] ?? '');

/** Cancela el pedido y devuelve al carrito con el motivo. */
function cancelar(mysqli $conn, string $motivo): void
{
    $conn->rollback();
    $_SESSION['carrito_error'] = $motivo;
    header("Location: carrito.php");
    exit();
}

if ($direccion_envio === '') {
    $_SESSION['carrito_error'] = '❌ Debes indicar una dirección de entrega.';
    header("Location: pedido.php");
    exit();
}

/*
 * NOTA: el total YA NO se lee de $_POST.
 *
 * Antes llegaba en un <input type="hidden"> y se guardaba tal cual, así
 * que bastaba con editar el HTML para pagar $1 un pedido de $500.000.
 * Ahora se recalcula aquí a partir de los precios de la tabla producto,
 * que es la única fuente fiable.
 */

$conn->begin_transaction();

try {
    // -----------------------------------------------------------------
    // 1. Leer el carrito bloqueando las filas implicadas.
    //    FOR UPDATE evita que dos pedidos simultáneos se lleven el mismo
    //    stock: el segundo espera a que el primero termine.
    // -----------------------------------------------------------------
    $sqlCarrito = "SELECT c.id_producto,
                          c.cantidad,
                          p.nombre,
                          p.precio,
                          p.stock,
                          p.estado_producto
                     FROM carrito c
                     INNER JOIN producto p ON c.id_producto = p.id_producto
                    WHERE c.id_cliente = ?
                    FOR UPDATE";
    $stmtCar = $conn->prepare($sqlCarrito);
    $stmtCar->bind_param("i", $id_cliente);
    $stmtCar->execute();
    $items = $stmtCar->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCar->close();

    if (!$items) {
        cancelar($conn, '❌ Tu carrito está vacío.');
    }

    // -----------------------------------------------------------------
    // 2. Validar disponibilidad y calcular el total en el servidor.
    // -----------------------------------------------------------------
    $total = 0;

    foreach ($items as $item) {
        if (strcasecmp($item['estado_producto'], 'activo') !== 0) {
            cancelar($conn, "❌ \"{$item['nombre']}\" ya no está disponible. Quítalo del carrito para continuar.");
        }

        if ($item['cantidad'] > $item['stock']) {
            cancelar(
                $conn,
                "❌ No hay stock suficiente de \"{$item['nombre']}\": " .
                "pediste {$item['cantidad']} y quedan {$item['stock']}."
            );
        }

        $total += $item['precio'] * $item['cantidad'];
    }

    // -----------------------------------------------------------------
    // 3. Cabecera del pedido, con el total ya calculado.
    // -----------------------------------------------------------------
    $fecha = date("Y-m-d H:i:s");

    $stmt = $conn->prepare(
        "INSERT INTO pedido (id_cliente, direccion_envio, total, estado, fecha_pedido)
         VALUES (?, ?, ?, 'Pendiente', ?)"
    );
    $stmt->bind_param("isds", $id_cliente, $direccion_envio, $total, $fecha);
    $stmt->execute();
    $id_pedido = $conn->insert_id;
    $stmt->close();

    // -----------------------------------------------------------------
    // 4. Líneas del pedido y descuento de stock.
    //
    //    Se guarda precio_unitario además del subtotal: si mañana cambia
    //    el precio del producto, el histórico del pedido no se altera.
    // -----------------------------------------------------------------
    $stmtDet = $conn->prepare(
        "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );

    // El AND stock >= ? es la última defensa: si otra transacción se
    // adelantó, no actualiza ninguna fila y el pedido se cancela entero.
    $stmtStock = $conn->prepare(
        "UPDATE producto SET stock = stock - ? WHERE id_producto = ? AND stock >= ?"
    );

    foreach ($items as $item) {
        $id_producto = (int) $item['id_producto'];
        $cantidad    = (int) $item['cantidad'];
        $precio      = (float) $item['precio'];
        $subtotal    = $precio * $cantidad;

        $stmtDet->bind_param("iiidd", $id_pedido, $id_producto, $cantidad, $precio, $subtotal);
        $stmtDet->execute();

        $stmtStock->bind_param("iii", $cantidad, $id_producto, $cantidad);
        $stmtStock->execute();

        if ($stmtStock->affected_rows !== 1) {
            cancelar($conn, "❌ Se agotó \"{$item['nombre']}\" mientras confirmabas el pedido. Inténtalo de nuevo.");
        }
    }

    $stmtDet->close();
    $stmtStock->close();

    // -----------------------------------------------------------------
    // 5. Vaciar el carrito y confirmar.
    // -----------------------------------------------------------------
    $del = $conn->prepare("DELETE FROM carrito WHERE id_cliente = ?");
    $del->bind_param("i", $id_cliente);
    $del->execute();
    $del->close();

    $conn->commit();

    // El pedido salio: no dejar colgado ningun aviso del carrito.
    unset($_SESSION["carrito_ok"], $_SESSION["carrito_error"]);

    header("Location: detalle_pedido.php?id_pedido=" . $id_pedido);
    exit();

} catch (mysqli_sql_exception $e) {
    // Desde PHP 8.1 mysqli lanza excepciones: cualquier fallo de las
    // consultas de arriba cae aquí y deshace el pedido completo.
    $conn->rollback();
    error_log('Error al guardar el pedido: ' . $e->getMessage());
    $_SESSION['carrito_error'] = '❌ No se pudo registrar el pedido. Inténtalo de nuevo.';
    header("Location: carrito.php");
    exit();
}
