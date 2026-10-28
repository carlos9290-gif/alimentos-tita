<?php
/**
 * Cambia el estado de un pedido desde el panel.
 *
 * Ya no es un UPDATE suelto: cancelar un pedido DEVUELVE el stock.
 *
 * El stock se descuenta cuando se crea el pedido (guardar_pedido.php),
 * no cuando se paga, así que un pedido en cualquier estado distinto de
 * 'Cancelado' tiene producto reservado. Antes, poner 'Cancelado' aquí
 * dejaba ese inventario bloqueado para siempre.
 */

require_once __DIR__ . '/../seguridad.php';
require_once __DIR__ . '/../pedidos.php';

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

$conn->begin_transaction();

try {
    // Bloquear antes de decidir: dos administradores cancelando a la vez
    // no pueden devolver el stock dos veces.
    $stmt = $conn->prepare("SELECT estado FROM pedido WHERE id_pedido = ? FOR UPDATE");
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pedido) {
        $conn->rollback();
        $_SESSION['admin_error'] = 'El pedido #' . $id_pedido . ' no existe.';
        header('Location: pedido_admin.php');
        exit();
    }

    $actual = $pedido['estado'];

    if ($actual === $estado) {
        $conn->rollback();
        header('Location: pedido_admin.php');
        exit();
    }

    /*
     * 'Cancelado' es terminal.
     *
     * Al cancelar ya se devolvió el stock. Revivir el pedido obligaría a
     * descontarlo otra vez, y puede que entre tanto se haya vendido:
     * quedaría stock negativo o un pedido que no se puede surtir. Si el
     * cliente vuelve, se crea un pedido nuevo.
     */
    if ($actual === 'Cancelado') {
        $conn->rollback();
        $_SESSION['admin_error'] = 'El pedido #' . $id_pedido .
            ' está cancelado y su stock ya se devolvió al inventario. ' .
            'No se puede reactivar: crea un pedido nuevo.';
        header('Location: pedido_admin.php');
        exit();
    }

    if ($estado === 'Cancelado') {
        pedido_devolver_stock($conn, $id_pedido);
    }

    $stmtEstado = $conn->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");
    $stmtEstado->bind_param("si", $estado, $id_pedido);
    $stmtEstado->execute();
    $stmtEstado->close();

    $conn->commit();

    $_SESSION['admin_ok'] = $estado === 'Cancelado'
        ? 'Pedido #' . $id_pedido . ' cancelado. Los productos volvieron al inventario.'
        : 'Pedido #' . $id_pedido . ' actualizado a "' . $estado . '".';

    header('Location: pedido_admin.php');
    exit();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Error al cambiar el estado del pedido: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'No se pudo cambiar el estado del pedido. Inténtalo de nuevo.';
    header('Location: pedido_admin.php');
    exit();
}
