<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

if (!isset($_GET['id_pedido']) || !ctype_digit((string) $_GET['id_pedido'])) {
    header("Location: mis_pedidos.php");
    exit();
}

$id_pedido  = (int) $_GET['id_pedido'];
$id_cliente = (int) $_SESSION['cliente_id'];

// El AND id_cliente es lo que impide leer el pedido de otra persona
// cambiando el id_pedido en la URL.
$sqlPedido = "SELECT * FROM pedido WHERE id_pedido = ? AND id_cliente = ?";
$stmt = $conn->prepare($sqlPedido);
$stmt->bind_param("ii", $id_pedido, $id_cliente);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

// No existe, o no le pertenece: se responde igual en ambos casos.
if (!$pedido) {
    http_response_code(404);
    exit("❌ Pedido no encontrado.");
}

// Detalles
$sqlDet = "SELECT d.*, p.nombre, p.imagen_empanada, p.precio
           FROM detalle_pedido d
           INNER JOIN producto p ON d.id_producto = p.id_producto
           WHERE d.id_pedido = ?";
$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("i", $id_pedido);
$stmtDet->execute();
$detalles = $stmtDet->get_result();

// Intentos de pago, el más reciente primero. Un pedido pagado tiene al
// menos uno aprobado; los rechazados también quedan a la vista porque
// explican por qué un pedido tardó en pagarse.
$stmtPagos = $conn->prepare(
    "SELECT referencia, metodo, estado, detalle, mensaje, fecha_pago
       FROM pago
      WHERE id_pedido = ?
      ORDER BY id_pago DESC"
);
$stmtPagos->bind_param("i", $id_pedido);
$stmtPagos->execute();
$pagos = $stmtPagos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPagos->close();

$pago_ok    = $_SESSION['pago_ok']    ?? '';
$pago_error = $_SESSION['pago_error'] ?? '';
unset($_SESSION['pago_ok'], $_SESSION['pago_error']);

$colores_estado = [
    'Pendiente' => 'warning text-dark',
    'Pagado'    => 'success',
    'Enviado'   => 'info text-dark',
    'Entregado' => 'primary',
    'Cancelado' => 'secondary',
];
$color_estado = $colores_estado[$pedido['estado']] ?? 'secondary';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Pedido - Alimentos Tita</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tus estilos -->
    <link rel="stylesheet" href="style.css">

    <style>
        /* --- Estilos de mejora --- */

        body {
            background-color: #fafafa;
        }

        .navbar-brand img {
            border-radius: 50%;
        }

        .table img {
            border-radius: 10px;
        }

        .btn {
            border-radius: 10px;
        }

        .footer {
            background: #343a40;
            color: white;
            padding: 20px 0;
            margin-top: 50px;
        }
        .footer a {
            color: #ffc107;
            text-decoration: none;
        }
        .footer a:hover {
            color: #ffffff;
        }
    </style>
</head>

<body>

<?php include 'Navbar.php'; ?>

<div class="container py-5">

<h2 class="fw-bold mb-4">📄 Detalle del Pedido #<?= esc($id_pedido) ?></h2>

<?php if ($pago_ok !== ''): ?>
    <div class="alert alert-success"><?= esc($pago_ok) ?></div>
<?php endif; ?>

<?php if ($pago_error !== ''): ?>
    <div class="alert alert-danger"><?= esc($pago_error) ?></div>
<?php endif; ?>

<p><strong>Fecha:</strong> <?= esc($pedido['fecha_pedido']) ?></p>
<p>
    <strong>Estado:</strong>
    <span class="badge bg-<?= $color_estado ?>"><?= esc($pedido['estado']) ?></span>
</p>
<p><strong>Dirección:</strong> <?= esc($pedido['direccion_envio']) ?></p>

<?php if ($pedido['estado'] === 'Pendiente'): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Este pedido todavía no está pagado. Los productos están reservados para ti.</span>
        <a href="pasarela.php?id_pedido=<?= (int) $id_pedido ?>" class="btn btn-success">Pagar ahora</a>
    </div>
<?php endif; ?>

<?php if ($pagos): ?>
    <div class="card mt-3 mb-4">
        <div class="card-header fw-semibold">Pagos</div>
        <ul class="list-group list-group-flush">
            <?php foreach ($pagos as $p): ?>
                <li class="list-group-item">
                    <span class="badge bg-<?= $p['estado'] === 'Aprobado' ? 'success' : 'danger' ?>">
                        <?= esc($p['estado']) ?>
                    </span>
                    <strong class="ms-1"><?= esc($p['metodo']) ?></strong>
                    <?php if ($p['detalle']): ?>
                        <span class="text-muted">— <?= esc($p['detalle']) ?></span>
                    <?php endif; ?>
                    <div class="small text-muted"><?= esc($p['mensaje']) ?></div>
                    <div class="small text-muted">
                        Referencia <?= esc($p['referencia']) ?> &middot; <?= esc($p['fecha_pago']) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<table class="table table-bordered mt-4">
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
    <tr class="text-center align-middle">
        <td><img src="<?= esc($row['imagen_empanada']) ?>" width="70" alt=""></td>
        <td><?= esc($row['nombre']) ?></td>
        <td><?= esc($row['cantidad']) ?></td>
        <?php
                    $pu = (float) ($row['precio_unitario'] ?? 0);
                    if ($pu <= 0 && (int) $row['cantidad'] > 0) {
                        $pu = $row['subtotal'] / $row['cantidad'];   // pedido anterior a la Fase 3
                    }
                ?>
        <td>$<?php echo number_format($pu, 0, ',', '.'); ?></td>
        <td>$<?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h3 class="text-end fw-bold mt-3">
    Total: $<?php echo number_format($pedido['total'], 0, ',', '.'); ?>
</h3>

<div class="mt-4">
    <a href="mis_pedidos.php" class="btn btn-secondary">Volver</a>
</div>

</div>

<?php include 'Footer.php'; ?>

</body>
</html>
