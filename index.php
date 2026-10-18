<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alimentos Tita: Fábrica de Empanadas</title>

    <!-- 🎨 Estilos -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <!--  HEADER -->
    <?php include 'Header.php'; ?>


    <!--  CARTAS -->
    <?php include 'Cartas.php'; ?>

    <!-- CARRUSEL DE PRODUCTOS -->
    <?php include 'Carrusel_productos.php'; ?>

    <!-- CONTACTO -->
    <?php include 'Contactenos.php'; ?>
   





    <!--  Botón de WhatsApp -->
    <a href="https://wa.me/573134528378?text=Hola%20quiero%20más%20información%20sobre%20sus%20productos" 
       class="whatsapp" target="_blank">
        <img src="imagenes/whatsapp.svg" alt="WhatsApp">
</a>

     



    <!-- FOOTER -->
    <?php include 'Footer.php'; ?>


    <!-- Tus scripts personalizados -->
    <script src="script.js"></script>    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
  const swiper = new Swiper('.productos-swiper', {
    slidesPerView: 3,
    spaceBetween: 20,
    loop: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      992: { slidesPerView: 3 }
    }
  });
</script>
</body>
</html>
