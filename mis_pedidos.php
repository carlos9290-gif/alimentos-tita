<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$id_cliente = $_SESSION['cliente_id'];

$sql = "SELECT * FROM pedido WHERE id_cliente = ? ORDER BY fecha_pedido DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - Alimentos Tita</title>

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
    <h2 class="text-center fw-bold mb-4">📦 Mis Pedidos</h2>

<?php if ($resultado->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-warning text-center">
                <tr>
                    <th>ID Pedido</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr class="text-center">
                    <td><?= esc($row['id_pedido']) ?></td>
                    <td><?= esc($row['fecha_pedido']) ?></td>
                    <td>$<?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    <td><?= esc($row['estado']) ?></td>
                    <td><a class="btn btn-warning btn-sm" href="detalle_pedido.php?id_pedido=<?= (int) $row['id_pedido'] ?>">Ver</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center">No tienes pedidos aún.</p>
<?php endif; ?>

</div>

<?php include 'Footer.php'; ?>

</body>
</html>
