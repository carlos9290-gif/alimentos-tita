<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

// Solo el admin autenticado puede entrar
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php');
    exit();
}

$ok    = $_SESSION['cliente_ok']    ?? '';
$error = $_SESSION['cliente_error'] ?? '';
unset($_SESSION['cliente_ok'], $_SESSION['cliente_error']);

// La columna contrasena NO se consulta: guarda un hash y no debe salir
// nunca a pantalla. Para cambiarla se usa editar_cliente.php.
$resultado = $conn->query(
    "SELECT id_cliente, nombre, apellido, correo, direccion, telefono, fecha_registro
       FROM cliente
      ORDER BY id_cliente DESC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes - Alimentos Tita</title>

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
            <img src="../imagenes/logo.jpg" alt="Logo Alimentos Tita" width="55" height="55"
                 class="rounded-circle me-2">
            <span class="fw-bold text-dark">Gestión de Clientes</span>
        </a>

        <div>
            <a href="logout_admin.php" class="btn btn-outline-dark">Cerrar Sesión</a>
        </div>

    </div>
</nav>

<div class="container mt-4">

    <h2 class="mb-4 text-center">👥 Lista de Clientes</h2>

    <?php if ($ok !== ''): ?>
        <div class="alert alert-success"><?= esc($ok) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>

    <a href="index_admin.php" class="btn btn-warning mb-3">⬅ Volver al Panel</a>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Registro</th>
                    <th style="width:170px;">Acciones</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($cliente = $resultado->fetch_assoc()) { ?>
                <tr>
                    <td><?= esc($cliente['id_cliente']) ?></td>
                    <td><?= esc($cliente['nombre']) ?></td>
                    <td><?= esc($cliente['apellido']) ?></td>
                    <td><?= esc($cliente['correo']) ?></td>
                    <td><?= esc($cliente['direccion']) ?></td>
                    <td><?= esc($cliente['telefono']) ?></td>
                    <td><?= esc($cliente['fecha_registro']) ?></td>

                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar_cliente.php?id=<?= (int) $cliente['id_cliente'] ?>"
                               class="btn btn-warning btn-sm">Editar</a>

                            <!-- Borrado por POST + token CSRF -->
                            <form method="POST" action="eliminar_cliente.php" class="d-inline"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= (int) $cliente['id_cliente'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </div>
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
