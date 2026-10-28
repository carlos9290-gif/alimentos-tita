<?php
/**
 * Operaciones sobre pedidos que necesitan más de una página.
 *
 * Existe para no duplicar la devolución de stock entre la cancelación
 * del cliente (cancelar_pedido.php) y la del administrador
 * (admin/save_pedido_admin.php).
 */

/**
 * Devuelve al inventario todo lo que ocupaba un pedido.
 *
 * POR QUÉ HACE FALTA: el stock se descuenta cuando se CREA el pedido
 * (guardar_pedido.php), no cuando se paga. Un pedido 'Pendiente' ya
 * tiene producto reservado, así que cancelarlo sin devolverlo deja
 * inventario bloqueado para siempre.
 *
 * Quien llama DEBE:
 *   1. estar dentro de una transacción,
 *   2. haber bloqueado la fila del pedido con FOR UPDATE, y
 *   3. haber comprobado que el pedido todavía no estaba 'Cancelado'.
 *
 * Sin el punto 3 una cancelación repetida sumaría stock inexistente.
 */
function pedido_devolver_stock(mysqli $conn, int $id_pedido): void
{
    // Una sola sentencia: el motor recorre el detalle y suma. Así no
    // hay ventana entre leer las líneas y devolver las cantidades.
    $stmt = $conn->prepare(
        "UPDATE producto p
           INNER JOIN detalle_pedido d ON d.id_producto = p.id_producto
              SET p.stock = p.stock + d.cantidad
            WHERE d.id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $stmt->close();
}

/** Estados en los que el pedido tiene producto reservado del inventario. */
function pedido_reserva_stock(string $estado): bool
{
    return $estado !== 'Cancelado';
}
