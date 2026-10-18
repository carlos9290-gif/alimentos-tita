<!-- 🔸 HEADER -->
<nav class="navbar navbar-expand-md fixed-top shadow-sm navbar-tita">
  <div class="container">
    <!-- LOGO -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="imagenes/logo.jpg" alt="Logo" width="55" height="55" class="rounded-circle me-2">
      <span class="fw-bold text-white d-none d-sm-inline">Alimentos Tita</span>
    </a>

    <!-- BOTÓN RESPONSIVE -->
    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- MENÚ -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link fw-bold text-white" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link fw-bold text-white" href="nosotros.php">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link fw-bold text-white" href="productos.php">Productos</a></li>
        <li class="nav-item"><a class="nav-link fw-bold text-white" href="contacto.php">Contacto</a></li>
      </ul>

      <!-- USUARIO -->
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (isset($_SESSION['cliente_id'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold text-white" href="#" id="clienteDropdown" data-bs-toggle="dropdown">
              👋 Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="index.php">Inicio</a></li>
              <li><a class="dropdown-item text-danger" href="cerrar_sesion.php">Cerrar sesión</a></li>
              <li><a class="dropdown-item" href="mis_pedidos.php">Mis pedidos 📦</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle fw-semibold text-white" href="#" id="sesionDropdown" data-bs-toggle="dropdown">
              👤 Cuenta
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="inicio_sesion.php">Iniciar sesión</a></li>
              <li><a class="dropdown-item" href="crear_cuenta.php">Crear cuenta</a></li>
              
            </ul>
          </li>
        <?php endif; ?>

        <!-- 🛒 CARRITO -->
        <li class="nav-item ms-3 position-relative">
          <a href="carrito.php" class="nav-link position-relative">
            <img src="imagenes/carrito.svg" alt="Carrito" width="32">
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito">
              0
            </span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<section class="hero position-relative text-white d-flex align-items-center">
  <img src="imagenes/fonfo4.jpg" class="img-fluid w-100 hero-bg" alt="Portada">
  <div class="hero-overlay"></div>

  <div class="hero-content position-absolute text-end" 
       style="right: 3%; max-width: 550px;">
       
    <h1 class="fw-bold display-5">
      Empanadas Congeladas 100% de Maíz
    </h1>

    <p class="lead">
      Deleitamos tu paladar con nuestras creaciones
    </p>

    <a href="productos.php" class="btn btn-tita btn-warning mt-3 shadow">
      ¡Haz tu pedido ahora!
    </a>
  </div>
</section>

