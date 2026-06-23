<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - PORTADA PÚBLICA, BUSCADOR Y EXHIBICIÓN DINÁMICA
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();
$rolActual = rolActual();

$novedad = null;
$promocion = null;

// Extracción de datos dinámicos vigentes
try {
    $pdo = getConexion();
    
    // 1. Traer la última Novedad institucional vigente hoy
    $stmtN = $pdo->query("
        SELECT titulo, contenido 
        FROM novedades 
        WHERE CURDATE() BETWEEN fecha_inicio AND fecha_fin 
        ORDER BY id_novedad DESC LIMIT 1
    ");
    $novedad = $stmtN->fetch(PDO::FETCH_ASSOC);

    // 2. Traer la última Promoción con estado 'Aprobada' vigente hoy
    $stmtP = $pdo->query("
        SELECT p.descuento_porcentaje, a.nombre AS nombre_aerolinea, v.origen, v.destino 
        FROM promociones p
        INNER JOIN vuelos v ON p.id_vuelo = v.id_vuelo
        INNER JOIN aerolineas a ON v.id_aerolinea = a.id_aerolinea
        WHERE p.estado = 'Aprobada' AND CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin
        ORDER BY p.id_promocion DESC LIMIT 1
    ");
    $promocion = $stmtP->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fallo silencioso para no romper la maqueta pública
}
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
<body class="bg-light d-flex flex-column min-vh-100" data-bs-theme="light">

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
            <a class="nav-link" href="vuelos.php">Vuelos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="novedades.php">Novedades</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-warning fw-semibold" href="promociones_publicas.php">
              <i class="bi bi-tags-fill me-1"></i>Ofertas
            </a>
          </li>
        </ul>

        <div class="d-flex gap-2 align-items-center" role="group" aria-label="Controles de acceso y usuario">
          <?php if ($logueado): ?>
            <span class="text-white-50 small align-self-center me-3" aria-live="polite">
              <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
              <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
            </span>
            
            <?php if ($rolActual === 'admin' || $rolActual === 'ceo'): ?>
              <a href="<?= htmlspecialchars(urlSegunRol($rolActual)) ?>" class="btn btn-outline-info btn-sm">Mi Panel</a>
            <?php else: ?>
              <a href="mis_reservas.php" class="btn btn-outline-info btn-sm">Mis Reservas</a>
            <?php endif; ?>

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

<main class="container mt-5 flex-grow-1" role="main">
  
  <section aria-labelledby="titulo-buscador" class="card shadow-sm mb-5 border-0 rounded-3">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
      <h1 id="titulo-buscador" class="h4 text-center fw-bold" style="color: #0A2342;">
        Búsqueda de Vuelos Disponibles
      </h1>
    </div>
    <div class="card-body p-4">
      <form action="vuelos.php" method="GET" role="search" aria-label="Buscar vuelos disponibles">
        <div class="row g-3 align-items-end">
          
          <div class="col-md-3">
            <label for="inputOrigen" class="form-label fw-semibold small text-muted">Origen</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
              <input type="text" class="form-control" id="inputOrigen" name="origen" placeholder="Ciudad o IATA">
            </div>
          </div>
          
          <div class="col-md-3">
            <label for="inputDestino" class="form-label fw-semibold small text-muted">Destino</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-pin-map"></i></span>
              <input type="text" class="form-control" id="inputDestino" name="destino" placeholder="Ciudad o IATA">
            </div>
          </div>
          
          <div class="col-md-4">
            <label for="inputFechas" class="form-label fw-semibold small text-muted">Fecha de Salida</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
              <input type="date" class="form-control" id="inputFechas" name="fecha">
            </div>
          </div>
          
          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary fw-bold shadow-sm" style="background-color: #0A2342; border-color: #0A2342;">
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
        <i class="bi bi-megaphone display-4 text-primary mb-3" aria-hidden="true"></i>
        <?php if ($novedad): ?>
          <h2 class="h5 fw-bold text-dark mb-2"><?= htmlspecialchars($novedad['titulo']) ?></h2>
          <p class="text-muted small mb-0"><?= htmlspecialchars($novedad['contenido']) ?></p>
        <?php else: ?>
          <h2 class="h5 text-muted mb-2">Información para Pasajeros</h2>
          <p class="text-muted small mb-0">Todos nuestros aeropuertos y rutas comerciales operan en horario programado.</p>
        <?php endif; ?>
      </div>
    </article>
    
    <article class="col-md-6">
      <div class="card h-100 shadow-sm border-0 bg-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 200px;">
        <i class="bi bi-tags display-4 text-warning mb-3" aria-hidden="true"></i>
        <?php if ($promocion): ?>
          <span class="badge bg-warning text-dark fw-bold mb-2">¡OFERTA VERIFICADA!</span>
          <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($promocion['nombre_aerolinea']) ?>: <?= htmlspecialchars($promocion['descuento_porcentaje']) ?>% OFF</h2>
          <p class="text-muted small mb-0">Ruta en promoción: <?= htmlspecialchars($promocion['origen']) ?> <i class="bi bi-arrow-right"></i> <?= htmlspecialchars($promocion['destino']) ?></p>
        <?php else: ?>
          <h2 class="h5 text-muted mb-2">Oportunidades Check-Line</h2>
          <p class="text-muted small mb-0">Consulta nuestra sección de Ofertas para conocer las próximas tarifas reducidas.</p>
        <?php endif; ?>
      </div>
    </article>
    
  </section>

</main>

<footer role="contentinfo" class="bg-white border-top py-4 mt-auto">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
    <div class="mb-2 mb-md-0">
      <span class="fw-bold" style="color: #0A2342;"><i class="bi bi-airplane-fill me-1"></i>Check-Line</span> &copy; <?= date('Y') ?> — Todos los derechos reservados.
    </div>
    <div class="d-flex gap-3 align-items-center">
      <span><i class="bi bi-geo-alt-fill me-1"></i>Zeballos 1341, Rosario</span>
      <span><i class="bi bi-telephone-fill me-1"></i>0800-555-CHECK (24hs)</span>
      <a href="mapa_sitio.php" class="text-decoration-none fw-bold text-primary" aria-label="Acceder al mapa del sitio web">
        <i class="bi bi-sitemap me-1"></i>Mapa del Sitio
      </a>
    </div>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>