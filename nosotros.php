<?php
session_start();

// Verifica si el cliente está logueado
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
  <title>Nosotros | Alimentos Tita</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<?php $pagina_activa = 'nosotros'; include 'Navbar.php'; ?>

  <!-- PORTADA -->
  <section class="position-relative">
    <img src="imagenes/nosotros.png" alt="Equipo de trabajo en fábrica" class="img-fluid w-100" 
         style="height: 90vh; object-fit: cover; filter: brightness(65%);">
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white">
      <h1 class="fw-bold display-5">Nuestra Historia</h1>
      <p class="lead">Tradición, sabor y compromiso desde 2020</p>
    </div>
  </section>

  <!--  HISTORIA -->
  <section class="container my-5">
    <div class="row align-items-center">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <h2 class="fw-bold text-warning mb-4 text-center text-lg-start">¿Quiénes Somos?</h2>
        <p class="text-muted fs-5">
          Desde el año <strong>2020</strong>, <strong>Alimentos Tita</strong> tiene el propósito de llevar a cada hogar 
          el sabor auténtico y tradicional de las empanadas, ofreciendo una opción práctica, deliciosa y de excelente calidad.
        </p>
        <p class="text-muted fs-5">
          Nos especializamos en la <strong>fabricación y venta de empanadas congeladas</strong> en la ciudad de Bogotá,
          garantizando frescura, sabor y una experiencia inigualable en cada bocado.
        </p>
      </div>
      <div class="col-lg-6 text-center">
        <img src="imagenes/fabrica.png" class="img-fluid rounded shadow-sm" alt="Fábrica de empanadas">
      </div>
    </div>
  </section>

  <!-- 🔸 MISIÓN Y VISIÓN -->
  <section class="py-5 bg-light">
    <div class="container">
      <div class="row text-center g-4">
        <div class="col-md-6">
          <div class="card border-0 shadow h-100">
            <img src="imagenes/mision.png" class="card-img-top rounded-top" alt="Misión Alimentos Tita" style="height: 300px; object-fit: cover;">
            <div class="card-body">
              <h3 class="card-title text-warning fw-bold">Misión</h3>
              <p class="card-text text-muted">
                Ofrecer a nuestros clientes empanadas congeladas de la más alta calidad, elaboradas con ingredientes frescos
                y procesos responsables, brindando una experiencia práctica y deliciosa que conserve la esencia
                de la comida tradicional colombiana.
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-0 shadow h-100">
            <img src="imagenes/vision.png" class="card-img-top rounded-top" alt="Visión Alimentos Tita" style="height: 300px; object-fit: cover;">
            <div class="card-body">
              <h3 class="card-title text-warning fw-bold">Visión</h3>
              <p class="card-text text-muted">
                Ser reconocidos en el 2030 como la empresa líder en la producción de empanadas congeladas en Bogotá
                y expandirnos a nivel nacional, destacándonos por nuestra innovación, calidad y compromiso
                con la satisfacción de nuestros clientes.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 🔸 VALORES -->
  <section class="py-5">
    <div class="container text-center">
      <h2 class="fw-bold text-warning mb-4">Nuestros Valores</h2>
      <div class="row g-4">
        <?php
          $valores = [
            ["Calidad", "Garantizamos productos frescos y cuidadosamente elaborados."],
            ["Tradición", "Rescatamos y mantenemos el sabor auténtico de nuestras raíces."],
            ["Innovación", "Buscamos nuevas formas de mejorar nuestros productos y procesos."],
            ["Compromiso", "Trabajamos con responsabilidad hacia nuestros clientes y colaboradores."],
            ["Confianza", "Construimos relaciones basadas en la transparencia y el respeto."]
          ];
          foreach ($valores as $v) {
            echo '
            <div class="col-6 col-md-4 col-lg-2 mx-auto">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <h5 class="card-title fw-semibold text-warning">'.$v[0].'</h5>
                  <p class="card-text small text-muted">'.$v[1].'</p>
                </div>
              </div>
            </div>';
          }
        ?>
      </div>
    </div>
  </section>

  <?php include 'contactenos.php'; ?>
  <!-- 🔸 FOOTER -->
  <?php include 'Footer.php'; ?>

  <!-- 🔸 BOTÓN WHATSAPP -->
  <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos" class="whatsapp" target="_blank">
    <img src="imagenes/whatsapp.svg" alt="WhatsApp">
  </a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
