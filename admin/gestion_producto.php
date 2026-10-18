<?php
require_once __DIR__ . '/../seguridad.php';
include '../conexion.php';

$prod_ok    = $_SESSION['producto_ok']    ?? '';
$prod_error = $_SESSION['producto_error'] ?? '';
unset($_SESSION['producto_ok'], $_SESSION['producto_error']);

// Solo el admin autenticado puede entrar
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}

// Filtros
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado   = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// SQL dinámico
$sql = "SELECT id_producto, nombre, descripcion, precio, stock, estado_producto, imagen_empanada 
        FROM producto";

$conds = [];
$params = [];
$types  = '';

if ($busqueda !== '') {
    $conds[] = "nombre LIKE ?";
    $params[] = "%{$busqueda}%";
    $types   .= 's';
}

if ($estado !== '' && in_array($estado, ['Activo','Inactivo'], true)) {
    $conds[] = "estado_producto = ?";
    $params[] = $estado;
    $types   .= 's';
}

if ($conds) {
    $sql .= " WHERE " . implode(" AND ", $conds);
}

$sql .= " ORDER BY id_producto DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos - Alimentos Tita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

<!-- NAVBAR CON LOGO -->
<nav class="navbar navbar-expand-lg navbar-dark bg-warning shadow">
    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center" href="index_admin.php">
            <img src="../imagenes/logo.jpg" alt="Logo" width="55" height="55" class="rounded-circle me-2"
                 alt="Logo Alimentos Tita" 
                 style="height:45px;" 
                 class="me-2">
            <span class="fw-bold text-dark">Gestión de Productos</span>
        </a>

        <div>
            <a href="logout_admin.php" class="btn btn-outline-dark">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <h2 class="mb-4 text-center">🛒 Lista de Productos</h2>

    <?php if ($prod_ok !== ''): ?>
        <div class="alert alert-success"><?= esc($prod_ok) ?></div>
    <?php endif; ?>

    <?php if ($prod_error !== ''): ?>
        <div class="alert alert-danger"><?= esc($prod_error) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="index_admin.php" class="btn btn-warning">⬅ Volver al Panel</a>
        <a href="crear_producto.php" class="btn btn-primary">➕ Nuevo Producto</a>

        <form class="ms-auto d-flex gap-2" method="get">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nombre"
                   value="<?= htmlspecialchars($busqueda) ?>">

            <select name="estado" class="form-select" style="max-width:160px;">
                <option value="">Todos</option>
                <option value="Activo"   <?= $estado==='Activo'?'selected':'' ?>>Activo</option>
                <option value="Inactivo" <?= $estado==='Inactivo'?'selected':'' ?>>Inactivo</option>
            </select>

            <button class="btn btn-outline-secondary">Filtrar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Stock</th>
                    <th>Estado</th>
                    <th style="width:150px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($p = $resultado->fetch_assoc()) { ?>

                <?php
                // Imagen desde BD
                $imgBD = trim($p['imagen_empanada']);

                // Detectar casos según TU BD
                if ($imgBD == "" || $imgBD == "imagenes/" || is_null($imgBD)) {
                    $rutaImg = "../imagenes/no-image.png";

                } elseif (strpos($imgBD, "imagenes/") === 0) {
                    // Agregar ../ porque este archivo está dentro de /admin/
                    $rutaImg = "../" . $imgBD;

                } else {
                    $rutaImg = $imgBD;
                }
                ?>

                <tr>
                    <td><?= (int) $p['id_producto'] ?></td>

                    <!-- Imagen -->
                    <td style="width:80px;">
                        <img src="<?= htmlspecialchars($rutaImg, ENT_QUOTES, 'UTF-8') ?>"
                             alt="Imagen producto"
                             style="width:64px;height:64px;object-fit:cover;border-radius:6px;">
                    </td>

                    <td><?= htmlspecialchars($p['nombre']) ?></td>

                    <td style="max-width:300px;">
                        <div class="text-truncate" title="<?= htmlspecialchars($p['descripcion']) ?>">
                            <?= htmlspecialchars($p['descripcion']) ?>
                        </div>
                    </td>

                    <td class="text-end">$<?= number_format($p['precio'], 0, ',', '.') ?></td>

                    <td class="text-end"><?= (int) $p['stock'] ?></td>

                    <td>
                        <?= strcasecmp($p['estado_producto'], 'activo') === 0
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>' ?>
                    </td>

                    <td>
                        <a href="editar_producto.php?id=<?= (int) $p['id_producto'] ?>" class="btn btn-warning btn-sm">Editar</a>

                        <form method="POST" action="eliminar_producto.php" class="d-inline"
                              onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>

            <?php } ?>
            </tbody>

        </table>
    </div>
</div>

<footer>
    © <?= date('Y') ?> Alimentos Tita — Panel de Administración
</footer>

</body>
</html>
