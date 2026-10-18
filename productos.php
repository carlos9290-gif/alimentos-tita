<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Explorar Productos - Alimentos Tita</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tus estilos -->
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background-color: #fffdf5;
        }
        .card:hover {
            transform: translateY(-5px);
            transition: 0.3s;
        }
        .titulo {
            font-weight: bold;
            color: #d39e00;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

<?php $pagina_activa = 'productos'; include 'Navbar.php'; ?>


<!-- CONTENIDO -->
<div class="container py-5">

    <h2 class="text-center text-warning titulo  mb-4">Nuestras Opciones</h2>
    <p class="text-center text-secondary mb-5">Selecciona el tipo de compra que deseas realizar.</p>

    <div class="row g-4">

        <!-- CASA -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <img src="imagenes/casa.png" class="card-img-top" alt="Comprar para la casa">
                <div class="card-body text-center bg-warning rounded-bottom-3">
                    <h4 class="fw-bold text-white">Comprar para la Casa</h4>
                    <p class="text-light mb-3">Paquetes ideales para consumo diario.</p>
                    <a href="casa.php" class="btn btn-light fw-bold">Ver productos</a>
                </div>
            </div>
        </div>

        <!-- NEGOCIO -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <img src="imagenes/negocio.png" class="card-img-top" alt="Comprar para negocio">
                <div class="card-body text-center bg-warning rounded-bottom-3">
                    <h4 class="fw-bold text-white">Comprar para Negocio</h4>
                    <p class="text-light mb-3">Precios especiales y compra por volumen.</p>
                    <a href="negocio.php" class="btn btn-light fw-bold">Ver productos</a>
                </div>
            </div>
        </div>

        <!-- EVENTOS -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <img src="imagenes/evento.png" class="card-img-top" alt="Comprar para eventos">
                <div class="card-body text-center bg-warning rounded-bottom-3">
                    <h4 class="fw-bold text-white">Comprar para Eventos</h4>
                    <p class="text-light mb-3">Empanada coctelera para celebraciones.</p>
                    <a href="evento.php" class="btn btn-light fw-bold">Ver productos</a>
                </div>
            </div>
        </div>

    </div>

</div>
  <!-- 🔸 BOTÓN WHATSAPP -->
  <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos" class="whatsapp" target="_blank">
    <img src="imagenes/whatsapp.svg" alt="WhatsApp">
  </a>



<?php include 'Contactenos.php'; ?>
<?php include 'Footer.php'; ?>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
