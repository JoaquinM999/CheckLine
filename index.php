<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - PORTADA PÚBLICA Y BUSCADOR
 * ============================================================================
 * Archivo: index.php
 * Propósito: Renderizar la página de inicio pública (Home) del sistema.
 * Contiene el motor principal de búsqueda de vuelos y la exhibición de
 * novedades o avisos destacados según la arquitectura del sitio.
 * * * Intervención Frontend: 
 * - Maquetación estructural basada en los requerimientos del Punto 7 (Bocetos).
 * - Integración completa de jerarquía W3C, roles ARIA y diseño Mobile-First.
 * * * Conexión Backend (Pendiente):
 * - El formulario actual es estructural. El endpoint de búsqueda se 
 * conectará una vez finalizado el módulo ABMC de Vuelos.
 * * * @var bool $logueado Determina el estado de la sesión actual.
 * @author Equipo de Desarrollo Check-Line
 * @version 1.0.1
 * ============================================================================
 */
require_once __DIR__ . '/includes/auth.php';
iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta name="description" content="Buscador principal de vuelos y reservas del sistema Check-Line">
  <title>Check-Line — Sistema de Reservas de Vuelos</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light" data-bs-theme="light">

<header role="banner">
  <nav class="navbar navbar-expand-lg navbar-dark px-3 py-2" style="background-color:#0A2342;" aria-label="Navegación principal del sitio">
    <div class="container-fluid">
      
      <a class="navbar-brand fw-bold" href="index.php" aria-label="Página de inicio de Check-Line">
        <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPrincipal" aria-controls="navbarPrincipal" aria-expanded="false" aria-label="Alternar navegación">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarPrincipal">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="index.php">Inicio</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Vuelos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Novedades</a>
          </li>
        </ul>

        <div class="d-flex gap-2 align-items-center" role="group" aria-label="Controles de acceso y usuario">
          <?php if ($logueado): ?>
            <span class="text-white-50 small align-self-center me-2" aria-live="polite">
              <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
              <?= htmlspecialchars($_SESSION['nombre']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm" aria-label="Cerrar sesión actual">Salir</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-light btn-sm" aria-label="Ingresar al sistema">Iniciar sesión</a>
            <a href="registro.php" class="btn btn-warning btn-sm fw-bold" aria-label="Crear una nueva cuenta">Registrarse</a>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </nav>
</header>

<main class="container mt-5" role="main">
  
  <section aria-labelledby="titulo-buscador" class="card shadow-sm mb-5 border-0 rounded-3">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
      <h1 id="titulo-buscador" class="h4 text-center fw-bold" style="color: #0A2342;">
        Formulario de Búsqueda de Vuelos
      </h1>
    </div>
    <div class="card-body p-4">
      <form action="#" method="GET" role="search" aria-label="Buscar vuelos disponibles">
        <div class="row g-3 align-items-end">
          
          <div class="col-md-3">
            <label for="inputOrigen" class="form-label fw-semibold small text-muted">Origen</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
              <input type="text" class="form-control" id="inputOrigen" name="origen" placeholder="Ciudad o Aeropuerto" aria-required="true">
            </div>
          </div>
          
          <div class="col-md-3">
            <label for="inputDestino" class="form-label fw-semibold small text-muted">Destino</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-pin-map"></i></span>
              <input type="text" class="form-control" id="inputDestino" name="destino" placeholder="Ciudad o Aeropuerto" aria-required="true">
            </div>
          </div>
          
          <div class="col-md-4">
            <label for="inputFechas" class="form-label fw-semibold small text-muted">Fecha de Viaje</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
              <input type="date" class="form-control" id="inputFechas" name="fecha" aria-required="true">
            </div>
          </div>
          
          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary fw-bold" style="background-color: #0A2342; border-color: #0A2342;">
              <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar
            </button>
          </div>
          
        </div>
      </form>
    </div>
  </section>

  <section aria-label="Novedades y avisos destacados" class="row g-4 mb-5">
    
    <article class="col-md-6">
      <div class="card h-100 shadow-sm border-0 bg-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 200px;">
        <i class="bi bi-info-circle display-4 text-muted mb-3" aria-hidden="true"></i>
        <h2 class="h5 text-muted">Aviso / Novedad Destacada 1</h2>
        <p class="text-muted small mb-0">Espacio reservado para la carga dinámica de noticias institucionales.</p>
      </div>
    </article>
    
    <article class="col-md-6">
      <div class="card h-100 shadow-sm border-0 bg-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 200px;">
        <i class="bi bi-star display-4 text-muted mb-3" aria-hidden="true"></i>
        <h2 class="h5 text-muted">Aviso / Novedad Destacada 2</h2>
        <p class="text-muted small mb-0">Espacio reservado para promociones o comunicados importantes.</p>
      </div>
    </article>
    
  </section>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>