<?php
require_once __DIR__ . '/../seguridad.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

include "../conexion.php";

if (!isset($_GET['id_pedido']) || !ctype_digit((string) $_GET['id_pedido'])) {
    header("Location: pedido_admin.php");
    exit();
}
$id_pedido = (int) $_GET['id_pedido'];

// DATOS DEL PEDIDO
$sqlPedido = "SELECT p.*, c.nombre, c.apellido, c.correo 
              FROM pedido p
              INNER JOIN cliente c ON p.id_cliente = c.id_cliente
              WHERE id_pedido = ?";
$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

// DETALLE DEL PEDIDO
$sqlDet = "SELECT d.*, p.nombre, p.imagen_empanada 
           FROM detalle_pedido d
           INNER JOIN producto p ON d.id_producto = p.id_producto
           WHERE d.id_pedido = ?";
$stmt2 = $conn->prepare($sqlDet);
$stmt2->bind_param("i", $id_pedido);
$stmt2->execute();
$detalles = $stmt2->get_result();

// INTENTOS DE PAGO
// Una fila por intento: los rechazos explican por que un pedido sigue
// pendiente, y son lo primero que se mira ante un reclamo.
$sqlPagos = "SELECT referencia, metodo, estado, detalle, mensaje, fecha_pago
             FROM pago
             WHERE id_pedido = ?
             ORDER BY id_pago DESC";
$stmt3 = $conn->prepare($sqlPagos);
$stmt3->bind_param("i", $id_pedido);
$stmt3->execute();
$pagos = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt3->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">

    <h2>📄 Detalle Pedido #<?= esc($id_pedido) ?></h2>
    <hr>

    <h5>Cliente</h5>
    <p><?= esc($pedido['nombre'] . " " . $pedido['apellido']) ?> (<?= esc($pedido['correo']) ?>)</p>

    <h5>Información del pedido</h5>
    <p><strong>Fecha:</strong> <?= esc($pedido['fecha_pedido']) ?></p>
    <p><strong>Dirección:</strong> <?= esc($pedido['direccion_envio']) ?></p>
    <p><strong>Estado:</strong> <?= esc($pedido['estado']) ?></p>

    <table class="table table-bordered mt-3">
        <thead class="table-warning text-center">
            <tr>
                <th>Imagen</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $detalles->fetch_assoc()): ?>
            <tr class="text-center">
                <td><img src="../<?= esc($row['imagen_empanada']) ?>" width="70" alt=""></td>
                <td><?= esc($row['nombre']) ?></td>
                <td><?= esc($row['cantidad']) ?></td>
                <?php
                    $pu = (float) ($row['precio_unitario'] ?? 0);
                    if ($pu <= 0 && (int) $row['cantidad'] > 0) {
                        $pu = $row['subtotal'] / $row['cantidad'];   // pedido anterior a la Fase 3
                    }
                ?>
                <td>$<?= number_format($pu, 0, ',', '.') ?></td>
                <td><strong>$<?= number_format($row['subtotal'], 0, ',', '.') ?></strong></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h3 class="text-end">Total: $<?= number_format($pedido['total'], 0, ',', '.') ?></h3>

    <h5 class="mt-4">Intentos de pago</h5>
    <?php if (!$pagos): ?>
        <p class="text-muted">Este pedido no registra ningún intento de pago.</p>
    <?php else: ?>
        <table class="table table-sm table-bordered bg-white">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Resultado</th>
                    <th>Referencia</th>
                    <th>Respuesta</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= esc($p['fecha_pago']) ?></td>
                    <td>
                        <?= esc($p['metodo']) ?>
                        <?php if ($p['detalle']): ?>
                            <div class="small text-muted"><?= esc($p['detalle']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $p['estado'] === 'Aprobado' ? 'success' : 'danger' ?>">
                            <?= esc($p['estado']) ?>
                        </span>
                    </td>
                    <td class="small"><?= esc($p['referencia']) ?></td>
                    <td class="small"><?= esc($p['mensaje']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="pedido_admin.php" class="btn btn-secondary mt-3">Volver</a>

</div>

</body>
</html>
