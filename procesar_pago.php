<?php
/**
 * Recibe el formulario de pasarela.php, le pide una decisión al motor
 * del simulador (pagos.php) y la registra.
 *
 * Aquí está lo que diferencia un pago de un simple UPDATE de estado:
 *
 *  - La fila del pedido se bloquea con FOR UPDATE antes de mirar su
 *    estado. Dos clics seguidos en "Pagar" se serializan: el segundo
 *    encuentra el pedido ya en 'Pagado' y no cobra otra vez.
 *  - Cada respuesta del "banco" queda en la tabla `pago`, aprobada o
 *    rechazada. El estado del pedido solo se mueve si fue aprobada.
 *  - Un error de formato NO se registra como intento: la pasarela
 *    tampoco habría consultado al banco.
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/pagos.php';
include 'conexion.php';

csrf_validar();

if (!isset($_SESSION['cliente_id'])) {
    header('Location: inicio_sesion.php');
    exit();
}

$id_cliente = (int) $_SESSION['cliente_id'];
$id_pedido  = $_POST['id_pedido'] ?? '';
$metodo     = $_POST['metodo'] ?? '';

if (!ctype_digit((string) $id_pedido)) {
    header('Location: mis_pedidos.php');
    exit();
}

$id_pedido = (int) $id_pedido;

if (!in_array($metodo, PAGO_METODOS, true)) {
    $_SESSION['pago_error'] = 'Selecciona un método de pago válido.';
    header('Location: pasarela.php?id_pedido=' . $id_pedido);
    exit();
}

/** Deshace todo y devuelve a la pasarela con el motivo a la vista. */
function volver_a_pasarela(mysqli $conn, int $id_pedido, string $motivo, string $metodo): void
{
    $conn->rollback();
    $_SESSION['pago_error']  = $motivo;
    $_SESSION['pago_metodo'] = $metodo;
    header('Location: pasarela.php?id_pedido=' . $id_pedido);
    exit();
}

$conn->begin_transaction();

try {
    // -----------------------------------------------------------------
    // 1. Bloquear el pedido y comprobar que se puede cobrar.
    //
    //    El AND id_cliente es la misma defensa de detalle_pedido.php:
    //    impide pagar (o sondear) el pedido de otra persona.
    // -----------------------------------------------------------------
    $stmt = $conn->prepare(
        "SELECT id_pedido, total, estado
           FROM pedido
          WHERE id_pedido = ? AND id_cliente = ?
          FOR UPDATE"
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

    // Idempotencia: si ya está pagado, no se vuelve a cobrar. Esto es lo
    // que absorbe el doble clic y el F5 sobre el formulario enviado.
    if ($pedido['estado'] === 'Pagado') {
        $conn->rollback();
        $_SESSION['pago_ok'] = 'Este pedido ya estaba pagado. No se te cobró de nuevo.';
        header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
        exit();
    }

    if ($pedido['estado'] !== 'Pendiente') {
        $conn->rollback();
        $_SESSION['pago_error'] = 'Este pedido está en estado "' . $pedido['estado'] . '" y ya no admite pagos.';
        header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
        exit();
    }

    // -----------------------------------------------------------------
    // 2. Pedirle la decisión al simulador.
    //
    //    El monto sale del pedido, nunca del formulario: es el mismo
    //    principio por el que guardar_pedido.php recalcula el total.
    // -----------------------------------------------------------------
    $monto     = (float) $pedido['total'];
    $resultado = pago_cobrar($metodo, $_POST);

    if (!$resultado['ok']) {
        // Datos mal escritos: no hubo intento de cobro que registrar.
        volver_a_pasarela($conn, $id_pedido, $resultado['error'], $metodo);
    }

    // -----------------------------------------------------------------
    // 3. Registrar el intento, se haya aprobado o no.
    // -----------------------------------------------------------------
    $referencia = pago_referencia();

    $stmtPago = $conn->prepare(
        "INSERT INTO pago (id_pedido, referencia, metodo, estado, monto, marca, ultimos4, detalle, mensaje)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmtPago->bind_param(
        "isssdssss",
        $id_pedido,
        $referencia,
        $metodo,
        $resultado['estado'],
        $monto,
        $resultado['marca'],
        $resultado['ultimos4'],
        $resultado['detalle'],
        $resultado['mensaje']
    );
    $stmtPago->execute();
    $stmtPago->close();

    // -----------------------------------------------------------------
    // 4. Solo un intento aprobado mueve el pedido.
    // -----------------------------------------------------------------
    if ($resultado['estado'] === 'Aprobado') {
        $stmtEstado = $conn->prepare(
            "UPDATE pedido SET estado = 'Pagado' WHERE id_pedido = ? AND estado = 'Pendiente'"
        );
        $stmtEstado->bind_param("i", $id_pedido);
        $stmtEstado->execute();
        $cambiadas = $stmtEstado->affected_rows;
        $stmtEstado->close();

        if ($cambiadas !== 1) {
            // No debería ocurrir: tenemos la fila bloqueada desde el paso 1.
            // Si pasa, es preferible no cobrar que cobrar sin constancia.
            volver_a_pasarela($conn, $id_pedido, 'No se pudo confirmar el pago. Inténtalo de nuevo.', $metodo);
        }

        $conn->commit();

        $_SESSION['pago_ok'] = '✅ ' . $resultado['mensaje'] . ' Referencia: ' . $referencia;
        header('Location: detalle_pedido.php?id_pedido=' . $id_pedido);
        exit();
    }

    // Rechazado: el intento queda guardado y el pedido sigue Pendiente,
    // así que el cliente puede volver a intentarlo con otros datos.
    $conn->commit();

    $_SESSION['pago_error']  = '❌ ' . $resultado['mensaje'] . ' (referencia ' . $referencia . ')';
    $_SESSION['pago_metodo'] = $metodo;
    header('Location: pasarela.php?id_pedido=' . $id_pedido);
    exit();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log('Error al procesar el pago: ' . $e->getMessage());
    $_SESSION['pago_error'] = '❌ No se pudo procesar el pago. Inténtalo de nuevo.';
    header('Location: pasarela.php?id_pedido=' . $id_pedido);
    exit();
}
