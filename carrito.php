<?php
require_once __DIR__ . '/seguridad.php';
include 'conexion.php';

// Solo clientes logueados
if (!isset($_SESSION['cliente_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$id_cliente = $_SESSION['cliente_id'];

$sql = "SELECT c.id_carrito, p.nombre, p.precio, c.cantidad, p.imagen_empanada,
                p.stock, p.estado_producto
        FROM carrito c
        INNER JOIN producto p ON c.id_producto = p.id_producto
        WHERE c.id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

$total = 0;

$ok    = $_SESSION['carrito_ok']    ?? '';
$error = $_SESSION['carrito_error'] ?? '';
unset($_SESSION['carrito_ok'], $_SESSION['carrito_error']);

// Se acumulan aqui para poder bloquear el boton de finalizar compra.
$hay_problemas = false;
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

<!-- 🔸 CONTENIDO -->
<div class="container py-5">
    <h2 class="text-center mb-4 fw-bold">🛒 Tu Carrito de Compras</h2>

    <?php if ($ok !== ''): ?>
        <div class="alert alert-success"><?= esc($ok) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <?php if ($resultado->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-warning text-center">
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Disponibilidad</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = $resultado->fetch_assoc()):
                $subtotal = $row['precio'] * $row['cantidad'];
                $total += $subtotal;

                $problema = '';
                if (strcasecmp($row['estado_producto'], 'activo') !== 0) {
                    $problema = 'Ya no está disponible';
                } elseif ($row['cantidad'] > $row['stock']) {
                    $problema = 'Solo quedan ' . (int) $row['stock'];
                }
                if ($problema !== '') {
                    $hay_problemas = true;
                }
            ?>
                <tr class="text-center align-middle">
                    <td><img src="<?= esc($row['imagen_empanada']) ?>" width="70" class="shadow-sm" alt=""></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td>$<?php echo number_format($row['precio'], 0, ',', '.'); ?></td>
                    <td><?= (int) $row['cantidad'] ?></td>
                    <td class="fw-bold">$<?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($problema === ''): ?>
                            <span class="badge bg-success">Disponible</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?= esc($problema) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="eliminar_carrito.php">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int) $row['id_carrito'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h3 class="text-end me-4 fw-bold">
        Total: <span class="text-success">$<?php echo number_format($total, 0, ',', '.'); ?></span>
    </h3>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <form method="POST" action="vaciar_carrito.php" class="d-inline"
              onsubmit="return confirm('¿Vaciar todo el carrito?');">
            <?= csrf_input() ?>
            <button type="submit" class="btn btn-outline-danger">Vaciar carrito</button>
        </form>
        <?php if ($hay_problemas): ?>
            <span class="btn btn-success disabled" aria-disabled="true"
                  title="Ajusta los productos marcados en rojo">Finalizar compra</span>
        <?php else: ?>
            <a href="pedido.php" class="btn btn-success">Finalizar compra</a>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2 mt-3">
        <h3 class= "text-black text-cente">Seguir Comprando</h3>
        <a href="casa.php" class="btn btn-warning text-white fw-bold">🥟 Comprar para la Casa</a>
        <a href="negocio.php" class="btn btn-warning text-white fw-bold">🏪 Comprar para mi Negocio</a>
        <a href="evento.php" class="btn btn-warning text-white fw-bold">🎉 Comprar para Eventos</a>
    </div>


    <?php else: ?>

    <div class="alert alert-info text-center fs-5 shadow-sm">
        Tu carrito está vacío 🛍️
    </div>

    <div class="text-center">
        <a href="productos.php" class="btn btn-primary">Opciones de Productos</a>
    </div>

    <?php endif; ?>
</div>


<?php include 'Footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
