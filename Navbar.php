<?php
/**
 * Barra de navegación compartida de la tienda.
 *
 * Antes este bloque estaba copiado tal cual en 10 páginas: cambiar un
 * ítem del menú obligaba a editarlas todas.
 *
 * Uso: definir $pagina_activa antes de incluirlo para resaltar el enlace
 * de la sección actual (valores: inicio, nosotros, productos, contacto).
 *
 *     <?php $pagina_activa = 'productos'; include 'Navbar.php'; ?>
 *
 * Si no se define, no se resalta ninguno (carrito, pedido, mis pedidos…).
 */

if (!isset($pagina_activa)) {
    $pagina_activa = '';
}

/** Clases del enlace según sea o no la sección actual. */
function nav_clase(string $seccion): string
{
    global $pagina_activa;
    return $seccion === $pagina_activa
        ? 'nav-link active fw-semibold text-warning'
        : 'nav-link';
}
?>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-md bg-body-tertiary shadow-sm">
  <div class="container">
    <!-- LOGO -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="imagenes/logo.jpg" alt="Logo Alimentos Tita" width="50" height="50" class="rounded-circle me-2">
      <span class="fw-bold text-warning">Alimentos Tita</span>
    </a>

    <!-- BOTÓN RESPONSIVE -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- MENÚ -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="<?= nav_clase('inicio') ?>" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="<?= nav_clase('nosotros') ?>" href="nosotros.php">Nosotros</a></li>
        <li class="nav-item"><a class="<?= nav_clase('productos') ?>" href="productos.php">Productos</a></li>
        <li class="nav-item"><a class="<?= nav_clase('contacto') ?>" href="contacto.php">Contacto</a></li>
      </ul>

      <!-- USUARIO -->
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-success fw-semibold" href="#" id="clienteDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              👋 Hola, <?= htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="clienteDropdown">
              <li><a class="dropdown-item" href="index.php">Inicio</a></li>
              <li><a class="dropdown-item" href="mis_pedidos.php">Mis pedidos 📦</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="cerrar_sesion.php">Cerrar sesión</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-dark fw-semibold" href="#" id="sesionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              👤 Cuenta
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sesionDropdown">
              <li><a class="dropdown-item" href="inicio_sesion.php">Iniciar sesión</a></li>
              <li><a class="dropdown-item" href="crear_cuenta.php">Crear cuenta</a></li>
            </ul>
          </li>
        <?php endif; ?>

        <!-- CARRITO -->
        <li class="nav-item ms-3">
          <div class="submenu">
            <a href="carrito.php">
              <img src="imagenes/carrito.svg" alt="Carrito" width="30">
            </a>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>
