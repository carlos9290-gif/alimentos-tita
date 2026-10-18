<?php
include 'conexion.php';
session_start();

// Si el usuario no está logueado, redirigir al inicio de sesión
if (!isset($_SESSION["cliente_id"])) {
    header("Location: inicio_sesion.php");
    exit();
}

// Catalogo del canal "casa". La clasificacion vive en la tabla
// producto_categoria: antes se adivinaba a partir del nombre.
$sql = "SELECT p.id_producto, p.nombre, p.imagen_empanada, p.descripcion,
                p.precio, p.stock, p.estado_producto
           FROM producto p
           INNER JOIN producto_categoria pc ON p.id_producto = pc.id_producto
           INNER JOIN categoria cat        ON pc.id_categoria = cat.id_categoria
          WHERE cat.slug = ?
            AND p.estado_producto = 'activo'
          ORDER BY p.nombre";

$stmt = $conn->prepare($sql);
$slug = 'casa';
$stmt->bind_param("s", $slug);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
  die("<p style='color:red;'>No se pudo cargar el catálogo.</p>");
}

// Convertimos los resultados en un array asociativo
$productos = [];
while ($fila = $resultado->fetch_assoc()) {
  $productos[] = $fila;
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Empanadas para tu casa</title>
  <link rel="stylesheet" href="./style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>

<?php include 'Navbar.php'; ?>

   <section class="position-relative">
    <img src="imagenes/casa.png" alt="Equipo de trabajo en fábrica" class="img-fluid w-100" 
         style="height: 100vh; object-fit: cover; filter: brightness(65%);"> 
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white">
      <h1 class=" display-5">Disfruta De Tus Empanadas Tita En Casa!</h1>
    </div>
  </section>

 
<section class="position-relative py-5 bg-warning">
  <div class="container">
    <h2 class="fw-bold text-white text-uppercase text-center mb-4">
      Nuestros Productos
    </h2>

    <div class="row g-4">
      <?php if (count($productos) > 0): ?>
        <?php foreach ($productos as $producto): ?>
          <?php if (strtolower(trim($producto['estado_producto'])) === 'activo'): ?>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
              <div class="card h-100 shadow-sm">
                
                <img src="<?php echo htmlspecialchars($producto['imagen_empanada']); ?>" 
                     class="card-img-top"
                     style="object-fit: cover; height: 250px;"
                     alt="Imagen de <?php echo htmlspecialchars($producto['nombre']); ?>">

                <div class="card-body d-flex flex-column">

                  <h5 class="card-title text-center">
                    <?php echo htmlspecialchars($producto['nombre']); ?>
                  </h5>

                  <p class="text-center">
                    <?php echo htmlspecialchars($producto['descripcion']); ?>
                  </p>

                  <p class="text-center mb-1">
                    Stock disponible: <?php echo htmlspecialchars($producto['stock']); ?>
                  </p>

                  <p class="precio text-center fw-bold">
                    COP $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
                  </p>

                  <!-- Selector de cantidad -->
                  <div class="text-center">
                    <input type="number"
                      id="cantidad-<?= (int) $producto['id_producto'] ?>"
                      name="cantidad-visible"
                      min="1"
                      value="1"
                      class="cantidad-producto">

                  </div>
<!-- Botón agregar -->
<form action="agregar_carrito.php" method="GET" class="mt-2">
      <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
      <input type="hidden" name="cantidad" id="cantidad-input-<?= (int) $producto['id_producto'] ?>">

  <button type="submit" class="btn btn-warning text-light fw-bold w-100"
            onclick="document.getElementById('cantidad-input-<?= (int) $producto['id_producto'] ?>').value =
                     document.getElementById('cantidad-<?= (int) $producto['id_producto'] ?>').value;">
        Agregar al carrito
  </button>
</form>


                </div>
              </div>
            </div>

          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center">No hay productos disponibles en este momento.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>


<div class="card mb-4 mt-5 mx-auto shadow" style="max-width: 1200px;">
  <div class="row g-0">   

    <!-- Texto -->
    <div class="col-12 col-md-8">
      <div class="card-body">
        
      

        <h3 class="fw-semibold ms-md-5">🥟 Empanadas Congeladas Para Tu Casa</h3>

        <p class="ms-md-5">
          ¡Listas en minutos, con el sabor auténtico que conquista! <br>
          Disfruta de nuestras <strong>empanadas congeladas</strong>: crocantes,
          prácticas y hechas con ingredientes frescos.
        </p>

        <p class="ms-md-5">
          Haz tu pedido mínimo de <strong>$95.000</strong> en Bogotá y te lo llevamos gratis.
        </p>

        <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos"
           class="btn btn-warning text-white fw-bold ms-md-5 mt-3 px-4 py-2">
          ¡Contacta Ya!
        </a>

      </div>
    </div>

  </div>
</div>


 <div class="container my-5">
  <div class="row g-4">

    <!-- Card 1 -->
    <div class="col-12 col-md-6">
      <div class="card border-0 h-100 shadow-sm">
        
        <!-- Imagen -->
        <img src="imagenes/pedido24h.png" class="card-img-top" alt="Entrega rápida">
        
        <div class="card-body bg-warning rounded-bottom-3">
          <h2 class="card-title text-center text-dark">Tu Pedido en 24 Horas</h2>
          <p class="text-center mb-0">
            Haz Tu Pedido hoy en Bogotá y te lo entregamos mañana.
          </p>
        </div>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="col-12 col-md-6">
      <div class="card border-0 h-100 shadow-sm">
        
        <!-- Imagen -->
        <img src="imagenes/pagoenlinea.png" class="card-img-top" alt="Pagos en línea">

        <div class="card-body bg-warning rounded-bottom-3">
          <h2 class="card-title text-center text-dark">¿Cómo Pagar Mi Pedido en Línea?</h2>
          <p class="card-text text-center mb-0">
            Haz tu Pedido y cancela por PSE, PayU, Tarjetas Crédito y Débito,
            Bancolombia, Nequi, Daviplata.
          </p>
        </div>
      </div>
    </div>

  </div>
</div>




<?php include 'Contactenos.php'; ?>
<?php include 'Footer.php'; ?>


  <!-- Botón de WhatsApp -->
  <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos" class="whatsapp"
    target="_blank">
    <img src="imagenes/whatsapp.svg" alt="WhatsApp">
  </a>





  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

  <script src="./script.js"></script>

</body>

</html>