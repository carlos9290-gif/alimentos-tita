<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login_admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administración</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    footer {
      background-color: #ffc107;
      color: #212529;
      padding: 15px 0;
      margin-top: 50px;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-warning shadow">
    <div class="container-fluid">

      <!-- LOGO -->
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../imagenes/logo.jpg" alt="Logo" width="55" height="55" class="rounded-circle me-2">
        <span class="fw-bold text-dark">Panel de Administración</span>
      </a>

      <div class="d-flex">
        <a href="logout_admin.php" class="btn btn-outline-dark">Cerrar Sesión</a>
      </div>
    </div>
  </nav>


  <!-- CONTENIDO -->
  <div class="container mt-5">
    <h2>Bienvenido, <?php echo $_SESSION['usuario']; ?> 👋</h2>
    <p>Aquí podrás administrar productos, clientes y pedidos.</p>

    <div class="row mt-4">

      <div class="col-md-4">
        <div class="card shadow text-center">
          <div class="card-body">
            <h5 class="card-title">Gestión de Productos</h5>
            <a href="gestion_producto.php" class="btn btn-warning">Ver Productos</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow text-center">
          <div class="card-body">
            <h5 class="card-title">Gestión de Clientes</h5>
            <a href="gestion_cliente.php" class="btn btn-warning">Ver Clientes</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow text-center">
          <div class="card-body">
            <h5 class="card-title">Pedidos</h5>
            <a href="pedido_admin.php" class="btn btn-warning">Ver Pedidos</a>
          </div>
        </div>
      </div>

    </div>
  </div>


  <!-- FOOTER -->
  <footer class="text-center shadow">
    <p class="mb-0">© <?php echo date("Y"); ?> Alimentos Tita. Todos los derechos reservados.</p>
    <small>Panel de Administración | Desarrollado con ❤️</small>
  </footer>


</body>
</html>
