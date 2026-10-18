<?php
include 'conexion.php';
session_start();

// Si el usuario no está logueado, redirigir al inicio de sesión
if (!isset($_SESSION["cliente_id"])) {
    header("Location: inicio_sesion.php");
    exit();
}

// Catalogo del canal "negocio". La clasificacion vive en la tabla
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
$slug = 'negocio';
$stmt->bind_param("s", $slug);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
  die("<p style='color:red;'>Error en la consulta: " . $conn->error . "</p>");
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
  <title>Empanadas para tu negocio</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>

<?php include 'Navbar.php'; ?>

   <section class="position-relative">
    <img src="imagenes/negocio.png" alt="Equipo de trabajo en fábrica" class="img-fluid w-100" 
         style="height: 100vh; object-fit: cover; filter: brightness(65%);"> 
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white">
      <h1 class=" display-5">Surtimos Tu Negocio</h1>
    </div>
  </section>


<section class="position-relative py-5 bg-warning">
  <!-- Título productos -->
<h2 class="fw-bold text-white text-uppercase text-center mb-4">
      Nuestros Productos
    </h2>

<!-- Grid de productos -->
<div class="container mt-5">
    <div class="row g-4">

        <?php if (count($productos) > 0): ?>
            <?php foreach ($productos as $producto): ?>
                <?php if (strtolower(trim($producto['estado_producto'])) === 'activo'): ?>

                    <?php
                        $precio_original = $producto['precio'];
                        $precio_descuento = $precio_original * 0.9;
                    ?>

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 shadow-sm border-0">

                            <!-- Imagen producto -->
                            <img src="<?php echo htmlspecialchars($producto['imagen_empanada']); ?>"
                                 class="card-img-top"
                                 alt="Imagen de <?php echo htmlspecialchars($producto['nombre']); ?>">

                            <div class="card-body">

                                <!-- Nombre -->
                                <h5 class="card-title text-center fw-bold">
                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                </h5>

                                <!-- Descripción -->
                                <p class="text-center small">
                                    <?php echo htmlspecialchars($producto['descripcion']); ?>
                                </p>

                                <!-- Stock -->
                                <p class="text-center small text-muted">
                                    Stock: <?php echo htmlspecialchars($producto['stock']); ?>
                                </p>

                                <!-- Precio -->
                                <p class="precio text-center">
                                    <span class="text-decoration-line-through text-danger">
                                        COP $<?php echo number_format($precio_original, 0, ',', '.'); ?>
                                    </span><br>

                                    <span class="text-success fw-bold">
                                        10% OFF: COP $<?php echo number_format($precio_descuento, 0, ',', '.'); ?>
                                    </span>
                                </p>

                                <!-- Cantidad -->
                                <div class="text-center mb-2">
                                    <input type="number"
                                           id="cantidad-<?= (int) $producto['id_producto'] ?>"
                                           min="1"
                                           max="<?= (int) $producto['stock'] ?>"
                                           value="1"
                                           class="form-control w-50 mx-auto text-center">
                                </div>

                                <!-- Botón -->
                                <form action="agregar_carrito.php" method="GET">
                                    <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                    <input type="hidden" name="cantidad" id="cantidad-input-<?= (int) $producto['id_producto'] ?>">
                                    <button type="submit" class="btn btn-warning w-100 text-white fw-bold"
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
                <div class="alert alert-info text-center">
                    No hay productos disponibles en este momento.
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>
</section>

<div class="container mt-5">
  <div class="card mb-3 mx-auto shadow" style="max-width: 1200px;">
    <div class="row g-0">

     

      <!-- Texto -->
      <div class="col-md-8">
        <div class="card-body">

          <h2 class="card-title text-center">
            🥟 Empanadas Congeladas para tu Negocio en Bogotá
          </h2>

          <p class="mt-3 ms-md-4 me-md-4">
            Incrementa tus ventas con nuestras <strong>empanadas congeladas de calidad premium</strong>.
            Perfectas para restaurantes, cafeterías, tiendas y eventos.
          </p>

          <!-- Lista de beneficios -->
          <div class="beneficios ms-md-4">
            <div class="item">📦 Presentación en paquetes de 15 y 10 unidades</div>
            <div class="item">🔥 Pedido mínimo de 10 paquetes</div>
            <div class="item">💰 Precios especiales para distribuidores</div>
            <div class="item">🚚 Entrega rápida en toda Bogotá</div>
          </div>

          <!-- Botón -->
          <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos"
            class="btn btn-warning text-white fw-bold mt-3 ms-md-4">
            Contacta Ya!
          </a>

        </div>
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

  <script src="script.js"></script>


</body>

</html>