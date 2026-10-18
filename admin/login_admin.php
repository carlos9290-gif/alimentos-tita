<?php
require_once __DIR__ . '/../seguridad.php';
include __DIR__ . '/../conexion.php';

$error = $_SESSION['admin_login_error'] ?? '';
unset($_SESSION['admin_login_error']);

// El enlace para crear administrador solo tiene sentido si aun no existe
// ninguno; despues, crear_admin.php exige sesion de administrador.
$sin_admins = ((int) $conn->query("SELECT COUNT(*) AS n FROM admin")->fetch_assoc()['n']) === 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5 col-md-4">
    <div class="card shadow">
      <div class="card-body">
        <h3 class="text-center mb-4">Panel de Administración</h3>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="auth_admin.php">
          <?= csrf_input() ?>
          <div class="mb-3">
            <label>Usuario</label>
            <input type="text" name="usuario" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" name="contrasena" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning w-100">Iniciar Sesión</button>
        </form>

        <?php if ($sin_admins): ?>
          <div class="text-center mt-3">
            <a href="crear_admin.php">Crear el primer administrador</a>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</body>
</html>
