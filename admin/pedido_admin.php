<?php
require_once __DIR__ . '/../seguridad.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

include "../conexion.php";

// FILTRO POR ESTADO (solo valores de la lista blanca)
$ESTADOS = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];
$filtro  = $_GET['estado'] ?? '';
if (!in_array($filtro, $ESTADOS, true)) {
    $filtro = '';
}

$sql = "SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, p.direccion_envio,
               c.nombre, c.apellido, c.correo
        FROM pedido p
        INNER JOIN cliente c ON p.id_cliente = c.id_cliente";

if ($filtro !== '') {
    $sql .= " WHERE p.estado = ?";
}

$sql .= " ORDER BY p.id_pedido DESC";

$stmt = $conn->prepare($sql);
if ($filtro !== '') {
    $stmt->bind_param("s", $filtro);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Pedidos - Alimentos Tita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        footer {
            background-color: #ffc107;
            text-align: center;
            padding: 15px 0;
            margin-top: 40px;
        }
    </style>

</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-warning shadow">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index_admin.php">
        <img src="../imagenes/logo.jpg" alt="Logo" width="55" height="55" class="rounded-circle me-2">
            <span class="fw-bold text-dark">Gestión de Pedidos</span>
        </a>

        <div>
            <a href="logout_admin.php" class="btn btn-outline-dark">Cerrar Sesión</a>
        </div>
    </div>
</nav>


<div class="container mt-4">

    <h2 class="mb-3 text-center">📦 Gestión de Pedidos</h2>

    <a href="index_admin.php" class="btn btn-warning mb-3">⬅ Volver al Panel</a>


    <!-- FILTROS -->
    <form method="GET" class="mb-3 d-flex gap-2">
        <select name="estado" class="form-select" style="max-width: 280px;">
            <option value="">-- Todos los estados --</option>
            <option value="Pendiente"  <?= $filtro=='Pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="Pagado"     <?= $filtro=='Pagado' ? 'selected' : '' ?>>Pagado</option>
            <option value="Enviado"    <?= $filtro=='Enviado' ? 'selected' : '' ?>>Enviado</option>
            <option value="Entregado"  <?= $filtro=='Entregado' ? 'selected' : '' ?>>Entregado</option>
            <option value="Cancelado"  <?= $filtro=='Cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>

        <button class="btn btn-warning">Filtrar</button>
    </form>


    <!-- TABLA DE PEDIDOS -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
            <thead class="table-dark text-center">
                <tr>
                    <th># Pedido</th>
                    <th>Cliente</th>
                    <th>Correo</th>
                    <th>Dirección</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr class="text-center">

                    <td><?= htmlspecialchars($row['id_pedido']) ?></td>

                    <td><?= htmlspecialchars($row['nombre'] . " " . $row['apellido']) ?></td>

                    <td><?= htmlspecialchars($row['correo']) ?></td>

                    <td><?= htmlspecialchars($row['direccion_envio']) ?></td>

                    <td><?= htmlspecialchars($row['fecha_pedido']) ?></td>

                    <td><strong>$<?= number_format($row['total'], 0, ',', '.') ?></strong></td>

                    <!-- CAMBIO DE ESTADO -->
                    <td>
                        <form action="save_pedido_admin.php" method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id_pedido" value="<?= (int) $row['id_pedido'] ?>">

                            <select name="estado" class="form-select" onchange="this.form.submit()">
                                <option value="Pendiente"  <?= $row['estado']=='Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="Pagado"     <?= $row['estado']=='Pagado' ? 'selected' : '' ?>>Pagado</option>
                                <option value="Enviado"    <?= $row['estado']=='Enviado' ? 'selected' : '' ?>>Enviado</option>
                                <option value="Entregado"  <?= $row['estado']=='Entregado' ? 'selected' : '' ?>>Entregado</option>
                                <option value="Cancelado"  <?= $row['estado']=='Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </form>
                    </td>

                    <td>
                        <a href="detalle_pedido_admin.php?id_pedido=<?= (int) $row['id_pedido'] ?>" 
                           class="btn btn-primary btn-sm">
                           Ver
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>

        </table>
    </div>

</div>

<footer>
    © <?= date("Y") ?> Alimentos Tita — Panel de Administración
</footer>

</body>
</html>
