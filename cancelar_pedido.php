<?php
/**
 * Cancelación de un pedido por parte del propio cliente.
 *
 * Solo se permite mientras el pedido siga 'Pendiente', es decir sin
 * pagar. Un pedido ya pagado lo cancela el administrador, porque
 * implica una devolución de dinero.
 *
 * Lo importante aquí es que el stock VUELVE al inventario: se había
 * descontado al crear el pedido, antes de cualquier pago.
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/pedidos.php';
include 'conexion.php';

csrf_validar();

if (!isset($_SESSION['cliente_id'])) {
    header('Location: inicio_sesion.php');
    exit();
}

$id_cliente = (int) $_SESSION['cliente_id'];
$id_pedido  = $_POST['id_pedido'] ?? '';

if (!ctype_digit((string) $id_pedido)) {
    header('Location: mis_pedidos.php');
    exit();
}

$id_pedido = (int) $id_pedido;

$conn->begin_transaction();

try {
    // Bloquear el pedido antes de decidir: dos clics seguidos en
    // "Cancelar" no pueden devolver el stock dos veces.
    $stmt = $conn->prepare(
        "SELECT estado FROM pedido WHERE id_pedido = ? AND id_cliente = ? FOR UPDATE"
    );
    $stmt->bind_param("ii", $id_pedido, $id_cliente);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        $conn->rollback();
        http_response_code(404);
        exit('❌ Pedido no encontrado.');
    }

    if ($pedido['estado'] !== 'Pendiente') {
        $conn->rollback();
        $_SESSION['pago_error'] = 'Este pedido está en estado "' . $pedido['estado'] .
                                  '" y ya no lo puedes cancelar tú. Escríbenos y lo revisamos.';
        header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
        exit();
    }

    pedido_devolver_stock($conn, $id_pedido);

    $stmtEstado = $conn->prepare(
        "UPDATE pedido SET estado = 'Cancelado' WHERE id_pedido = ? AND estado = 'Pendiente'"
    );
    $stmtEstado->bind_param("i", $id_pedido);
    $stmtEstado->execute();
    $stmtEstado->close();

    $conn->commit();

    $_SESSION['pago_ok'] = 'Pedido cancelado. Los productos volvieron al inventario.';
    header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
    exit();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Error al cancelar el pedido: ' . $e->getMessage());
    $_SESSION['pago_error'] = '❌ No se pudo cancelar el pedido. Inténtalo de nuevo.';
    header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
    exit();
}
