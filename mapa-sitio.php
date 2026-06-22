<?php
/**
 * ============================================================================
 * CHECK-LINE — MAPA DEL SITIO
 * ============================================================================
 * Archivo: mapa-sitio.php (raíz del proyecto)
 * Propósito: Listado completo y navegable de todas las secciones del sitio,
 *            organizado por tipo de usuario. Accesible sin login.
 *            Requerido por la guía de cátedra: debe estar siempre en el footer.
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();
$rol      = $logueado ? rolActual() : null;
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Mapa del Sitio</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:#f0f4f8;">

<!-- ── Navbar ──────────────────────────────────────────────────────────── -->
<header role="banner">
  <nav class="navbar navbar-expand-lg navbar-dark px-3 py-2"
       style="background-color:#0A2342;"
       aria-label="Navegación principal">

    <a class="navbar-brand fw-bold" href="index.php" aria-label="Volver a la portada">
      <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
    </a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMapa"
            aria-controls="navMapa" aria-expanded="false" aria-label="Alternar navegación">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMapa">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="vuelos.php">
            <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar Vuelos
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="novedades.php">
            <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Novedades
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="promociones_publicas.php">
            <i class="bi bi-tag me-1" aria-hidden="true"></i>Promociones
          </a>
        </li>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <?php if ($logueado): ?>
          <a href="perfil.php" class="btn btn-outline-light btn-sm"
             aria-label="Mi perfil">
            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
            <?= htmlspecialchars($_SESSION['nombre']) ?>
          </a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
          <a href="registro.php" class="btn btn-warning btn-sm fw-bold">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>

<main class="container py-5" role="main" id="contenido-principal">

  <!-- Breadcrumb -->
  <nav aria-label="Ruta de navegación" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
      <li class="breadcrumb-item active" aria-current="page">Mapa del Sitio</li>
    </ol>
  </nav>

  <!-- Título -->
  <div class="mb-5">
    <h1 class="h3 fw-bold" style="color:#0A2342;">
      <i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>Mapa del Sitio
    </h1>
    <p class="text-muted">
      Listado completo de todas las secciones de Check-Line.
      Las secciones marcadas con
      <span class="badge bg-secondary" style="font-size:11px;">🔒 Requiere sesión</span>
      necesitan que hayas iniciado sesión con el rol correspondiente.
    </p>
  </div>

  <div class="row g-4">

    <!-- ── Columna 1: Secciones públicas ──────────────────────────── -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header fw-bold text-white" style="background-color:#0A2342;">
          <i class="bi bi-globe me-2" aria-hidden="true"></i>Acceso Público
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush" role="list">

            <li class="list-group-item" role="listitem">
              <a href="index.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-house-fill text-primary" aria-hidden="true"></i>
                <span>Inicio</span>
              </a>
              <small class="text-muted d-block ms-4">Portada con buscador de vuelos</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="vuelos.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-airplane text-primary" aria-hidden="true"></i>
                <span>Buscar Vuelos</span>
              </a>
              <small class="text-muted d-block ms-4">Catálogo de vuelos con filtros por origen, destino y fecha</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="novedades.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-megaphone text-primary" aria-hidden="true"></i>
                <span>Novedades</span>
              </a>
              <small class="text-muted d-block ms-4">Anuncios y comunicados del sistema</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="promociones_publicas.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-tag text-primary" aria-hidden="true"></i>
                <span>Promociones</span>
              </a>
              <small class="text-muted d-block ms-4">Ofertas y descuentos vigentes por aerolínea</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="login.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-in-right text-primary" aria-hidden="true"></i>
                <span>Iniciar Sesión</span>
              </a>
              <small class="text-muted d-block ms-4">Acceso para usuarios registrados</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="registro.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-person-plus text-primary" aria-hidden="true"></i>
                <span>Registrarse</span>
              </a>
              <small class="text-muted d-block ms-4">Crear una cuenta nueva como pasajero</small>
            </li>

            <li class="list-group-item" role="listitem">
              <a href="recuperar_password.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="bi bi-key text-primary" aria-hidden="true"></i>
                <span>Recuperar Contraseña</span>
              </a>
              <small class="text-muted d-block ms-4">Restablecer contraseña olvidada por email</small>
            </li>

          </ul>
        </div>
      </div>
    </div>

    <!-- ── Columna 2: Pasajero ─────────────────────────────────────── -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header fw-bold text-white" style="background-color:#1a6b3c;">
          <i class="bi bi-person-fill me-2" aria-hidden="true"></i>
          Panel Pasajero
          <span class="badge bg-light text-dark ms-2" style="font-size:10px;">🔒 Requiere sesión</span>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush" role="list">

            <li class="list-group-item <?= ($logueado && $rol === 'pasajero') ? '' : 'text-muted' ?>" role="listitem">
              <?php if ($logueado && $rol === 'pasajero'): ?>
                <a href="perfil.php" class="text-decoration-none d-flex align-items-center gap-2">
                  <i class="bi bi-person-circle" style="color:#1a6b3c;" aria-hidden="true"></i>
                  <span>Mi Perfil</span>
                </a>
              <?php else: ?>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-person-circle text-muted" aria-hidden="true"></i>
                  <span>Mi Perfil</span>
                  <span class="badge bg-secondary ms-auto" style="font-size:10px;">🔒</span>
                </div>
              <?php endif; ?>
              <small class="text-muted d-block ms-4">Ver y editar datos personales y contraseña</small>
            </li>

            <li class="list-group-item <?= ($logueado && $rol === 'pasajero') ? '' : 'text-muted' ?>" role="listitem">
              <?php if ($logueado && $rol === 'pasajero'): ?>
                <a href="reservar.php" class="text-decoration-none d-flex align-items-center gap-2">
                  <i class="bi bi-cart-plus" style="color:#1a6b3c;" aria-hidden="true"></i>
                  <span>Reservar Vuelo</span>
                </a>
              <?php else: ?>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-cart-plus text-muted" aria-hidden="true"></i>
                  <span>Reservar Vuelo</span>
                  <span class="badge bg-secondary ms-auto" style="font-size:10px;">🔒</span>
                </div>
              <?php endif; ?>
              <small class="text-muted d-block ms-4">Seleccionar asiento y confirmar reserva</small>
            </li>

            <li class="list-group-item <?= ($logueado && $rol === 'pasajero') ? '' : 'text-muted' ?>" role="listitem">
              <?php if ($logueado && $rol === 'pasajero'): ?>
                <a href="mis_reservas.php" class="text-decoration-none d-flex align-items-center gap-2">
                  <i class="bi bi-ticket-perforated" style="color:#1a6b3c;" aria-hidden="true"></i>
                  <span>Mis Reservas</span>
                </a>
              <?php else: ?>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-ticket-perforated text-muted" aria-hidden="true"></i>
                  <span>Mis Reservas</span>
                  <span class="badge bg-secondary ms-auto" style="font-size:10px;">🔒</span>
                </div>
              <?php endif; ?>
              <small class="text-muted d-block ms-4">Consultar, cancelar y ver historial de reservas</small>
            </li>

          </ul>
        </div>
      </div>
    </div>

    <!-- ── Columna 3: Admin + CEO ──────────────────────────────────── -->
    <div class="col-md-12 col-lg-4">
      <div class="row g-4">

        <!-- Admin -->
        <div class="col-md-6 col-lg-12">
          <div class="card border-0 shadow-sm">
            <div class="card-header fw-bold text-white" style="background-color:#6c1a1a;">
              <i class="bi bi-shield-fill me-2" aria-hidden="true"></i>
              Panel Administrador
              <span class="badge bg-light text-dark ms-2" style="font-size:10px;">🔒 Requiere sesión</span>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush" role="list">

                <?php
                $esAdmin = $logueado && $rol === 'admin';
                $itemsAdmin = [
                  ['admin/aerolineas.php',  'bi-building',        'Gestión de Aerolíneas',   'Alta, baja y modificación de aerolíneas'],
                  ['admin/aprobaciones.php','bi-check2-square',   'Aprobar Promociones',      'Aprobar o denegar promociones de los CEOs'],
                  ['admin/novedades.php',   'bi-newspaper',       'Gestión de Novedades',     'Crear, editar y eliminar novedades'],
                ];
                foreach ($itemsAdmin as [$url, $icono, $titulo, $desc]):
                ?>
                <li class="list-group-item <?= $esAdmin ? '' : 'text-muted' ?>" role="listitem">
                  <?php if ($esAdmin): ?>
                    <a href="<?= $url ?>" class="text-decoration-none d-flex align-items-center gap-2">
                      <i class="bi <?= $icono ?>" style="color:#6c1a1a;" aria-hidden="true"></i>
                      <span><?= $titulo ?></span>
                    </a>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi <?= $icono ?> text-muted" aria-hidden="true"></i>
                      <span><?= $titulo ?></span>
                      <span class="badge bg-secondary ms-auto" style="font-size:10px;">🔒</span>
                    </div>
                  <?php endif; ?>
                  <small class="text-muted d-block ms-4"><?= $desc ?></small>
                </li>
                <?php endforeach; ?>

              </ul>
            </div>
          </div>
        </div>

        <!-- CEO -->
        <div class="col-md-6 col-lg-12">
          <div class="card border-0 shadow-sm">
            <div class="card-header fw-bold text-white" style="background-color:#7a4f00;">
              <i class="bi bi-briefcase-fill me-2" aria-hidden="true"></i>
              Panel CEO
              <span class="badge bg-light text-dark ms-2" style="font-size:10px;">🔒 Requiere sesión</span>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush" role="list">

                <?php
                $esCEO = $logueado && $rol === 'ceo';
                $itemsCEO = [
                  ['ceo/vuelos.php',      'bi-airplane',   'Gestión de Vuelos',      'Alta, baja y modificación de vuelos'],
                  ['ceo/promociones.php', 'bi-percent',    'Gestión de Promociones', 'Crear y gestionar promociones de la aerolínea'],
                ];
                foreach ($itemsCEO as [$url, $icono, $titulo, $desc]):
                ?>
                <li class="list-group-item <?= $esCEO ? '' : 'text-muted' ?>" role="listitem">
                  <?php if ($esCEO): ?>
                    <a href="<?= $url ?>" class="text-decoration-none d-flex align-items-center gap-2">
                      <i class="bi <?= $icono ?>" style="color:#7a4f00;" aria-hidden="true"></i>
                      <span><?= $titulo ?></span>
                    </a>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi <?= $icono ?> text-muted" aria-hidden="true"></i>
                      <span><?= $titulo ?></span>
                      <span class="badge bg-secondary ms-auto" style="font-size:10px;">🔒</span>
                    </div>
                  <?php endif; ?>
                  <small class="text-muted d-block ms-4"><?= $desc ?></small>
                </li>
                <?php endforeach; ?>

              </ul>
            </div>
          </div>
        </div>

      </div>
    </div>
    <!-- fin columna 3 -->

  </div>
  <!-- fin row -->

  <div class="mt-4 text-center">
    <a href="index.php" class="text-muted small text-decoration-none">
      <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al inicio
    </a>
  </div>

</main>

<!-- ── Footer ──────────────────────────────────────────────────────────── -->
<footer class="text-white py-3 mt-5" style="background-color:#0A2342;"
        role="contentinfo" aria-label="Pie de página">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small">
      <i class="bi bi-airplane-fill me-1" aria-hidden="true"></i>
      Check-Line &copy; <?= date('Y') ?>
    </span>
    <nav class="d-flex gap-3" aria-label="Navegación del pie de página">
      <a href="mapa-sitio.php" class="text-white fw-semibold text-decoration-none small"
         aria-current="page">Mapa del Sitio</a>
      <a href="privacidad.php" class="text-white-50 text-decoration-none small">Política de Privacidad</a>
    </nav>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
