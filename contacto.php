<?php
require_once __DIR__ . '/seguridad.php';

// Si el usuario no está logueado, redirigir al inicio de sesión
if (!isset($_SESSION["cliente_id"])) {
    header("Location: inicio_sesion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nosotros</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php $pagina_activa = 'contacto'; include 'Navbar.php'; ?>

  <!-- Sección de contacto -->
  <div class="container contact bg-warning mt-5 p-4 rounded">
    <div class="row">
      <div class="col-md-3 text-center">
        <img src="https://image.ibb.co/kUASdV/contact-image.png" alt="Contacto" class="img-fluid mb-3">
        <h2>Contáctanos</h2>
        <h5>Nos gustaría escucharte</h5>
      </div>

      <div class="col-md-9">
        <form action="save_contactenos.php" method="POST" class="contact-form">
          <?= csrf_input() ?>
          <div class="mb-3">
            <label for="fname" class="form-label">Nombre:</label>
            <input type="text" class="form-control" id="fname" name="nombre_contacto" placeholder="Ingrese el primer nombre" required>
          </div>
          <div class="mb-3">
            <label for="lname" class="form-label">Apellido:</label>
            <input type="text" class="form-control" id="lname" name="apellido_contacto" placeholder="Ingrese el apellido" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Correo:</label>
            <input type="email" class="form-control" id="email" name="correo_contacto" placeholder="Ingrese el correo" required>
          </div>
          <div class="mb-3">
            <label for="comment" class="form-label">Comentario:</label>
            <textarea class="form-control" id="comment" name="comentario" rows="5" required></textarea>
          </div>
          <button type="submit" class="btn btn-light">Enviar</button>
        </form>
      </div>
    </div>
  </div>

  <?php include 'Footer.php'; ?>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="script.js"></script>


</body>
</html>