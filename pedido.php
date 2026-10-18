<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// Solo clientes logueados
if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$id_cliente = $_SESSION['cliente_id'];

// Obtener productos del carrito
$sql = "SELECT c.id_carrito, p.nombre, p.precio, p.imagen_empanada, c.cantidad,
                p.stock, p.estado_producto
        FROM carrito c
        INNER JOIN producto p ON c.id_producto = p.id_producto
        WHERE c.id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

$total     = 0;
$items     = [];
$problemas = [];   // avisos de disponibilidad

while ($row = $resultado->fetch_assoc()) {
    $subtotal = $row['precio'] * $row['cantidad'];
    $row['subtotal'] = $subtotal;

    // Este total es solo para mostrar. El que se guarda lo recalcula
    // guardar_pedido.php: esta pagina ya no envia ningun importe.
    $total += $subtotal;

    $row['problema'] = '';
    if (strcasecmp($row['estado_producto'], 'activo') !== 0) {
        $row['problema'] = 'Ya no está disponible';
    } elseif ($row['cantidad'] > $row['stock']) {
        $row['problema'] = 'Solo quedan ' . (int) $row['stock'];
    }
    if ($row['problema'] !== '') {
        $problemas[] = $row['nombre'] . ': ' . $row['problema'];
    }

    $items[] = $row;
}

$error = $_SESSION['carrito_error'] ?? '';
unset($_SESSION['carrito_error']);

// Si no hay productos redirigir
if (count($items) == 0) {
    header("Location: carrito.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Carrito - Alimentos Tita</title>

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
    <h2 class="text-center fw-bold mb-4">🧾 Confirmación de Pedido</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if ($problemas): ?>
        <div class="alert alert-warning">
            <strong>No podemos completar el pedido todavía:</strong>
            <ul class="mb-0">
                <?php foreach ($problemas as $p): ?>
                    <li><?= esc($p) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="carrito.php" class="btn btn-sm btn-warning mt-2">Volver al carrito</a>
        </div>
    <?php endif; ?>

<form action="guardar_pedido.php" method="POST">
    <?= csrf_input() ?>

    <div class="card shadow p-4 mb-4">
        <h4 class="fw-bold mb-3">📍 Dirección de entrega</h4>
        <input type="text" name="direccion_envio" class="form-control" placeholder="Ej: Cra 45 # 80 - 22" required>
    </div>

    <div class="card shadow p-4">
        <h4 class="fw-bold mb-3">🛒 Resumen del pedido</h4>

        <table class="table table-bordered">
            <thead class="table-warning text-center">
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Disponibilidad</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="text-center">
                    <td><img src="<?= esc($item['imagen_empanada']) ?>" width="70" alt=""></td>
                    <td><?= esc($item['nombre']) ?></td>
                    <td>$<?php echo number_format($item['precio'], 0, ',', '.'); ?></td>
                    <td><?= esc($item['cantidad']) ?></td>
                    <td class="fw-bold">$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($item['problema'] === ''): ?>
                            <span class="badge bg-success">Disponible</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?= esc($item['problema']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3 class="text-end fw-bold mt-3">
            Total: <span class="text-success">$<?php echo number_format($total, 0, ',', '.'); ?></span>
        </h3>

        <?php // El total NO viaja desde el navegador: lo recalcula guardar_pedido.php. ?>

        <div class="text-end">
            <button type="submit" class="btn btn-success btn-lg mt-3"
                    <?= $problemas ? 'disabled' : '' ?>>
                Confirmar Pedido ✔
            </button>
        </div>
    </div>
</form>

</div>

<?php include 'Footer.php'; ?>

</body>
</html>
