<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - PORTADA PÚBLICA Y BUSCADOR
 * ============================================================================
 * Archivo: index.php
 * Propósito: Renderizar la página de inicio pública (Home) del sistema.
 * ============================================================================
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();

// --- Obtención de Novedades Activas ---
try {
    $pdo = getConexion();
    
    // Filtramos estrictamente por fecha actual y limitamos a 2
    $stmtNovedades = $pdo->query("
        SELECT titulo, contenido, fecha_fin 
        FROM novedades 
        WHERE CURDATE() >= fecha_inicio AND CURDATE() <= fecha_fin 
        ORDER BY fecha_fin ASC 
        LIMIT 2
    ");
    $novedadesActivas = $stmtNovedades->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $novedadesActivas = []; // Fallback silencioso
}
// --- Obtención de Promociones Aprobadas ---
// --- Obtención de Promociones Aprobadas ---
try {
    $stmtPromos = $pdo->query("
        SELECT id_vuelo, descuento_porcentaje 
        FROM promociones 
        WHERE estado = 'Aprobada' 
        ORDER BY descuento_porcentaje DESC 
        LIMIT 3
    ");
    $promocionesAprobadas = $stmtPromos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error cargando promociones: ' . $e->getMessage());
    $promocionesAprobadas = []; 
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="widtfh=device-width, initial-scale=1.0, shrink-to-fit=no">
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
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="vuelos.php">Vuelos</a></li>
        <li class="nav-item"><a class="nav-link" href="novedades.php">Novedades</a></li>
        <li class="nav-item"><a class="nav-link fw-semibold text-warning" href="promociones_publicas.php">Promociones</a></li>
      </ul>

        <div class="d-flex gap-2 align-items-center" role="group" aria-label="Controles de acceso y usuario">
          <?php if ($logueado): ?>
            <a href="perfil.php" class="btn btn-outline-light btn-sm"
               aria-label="Ver y editar mi perfil">
              <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
              <?= htmlspecialchars($_SESSION['nombre']) ?>
            </a>
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
      <form action="vuelos.php" method="GET" role="search" aria-label="Buscar vuelos disponibles">
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
  <?php if (empty($novedadesActivas)): ?>
    <div class="col-12 text-center p-5 bg-white shadow-sm rounded-3">
      <i class="bi bi-info-circle display-4 text-muted mb-3 d-block"></i>
      <p class="text-muted small mb-0">No hay avisos institucionales vigentes en este momento.</p>
    </div>
  <?php else: ?>
    <?php foreach ($novedadesActivas as $novedad): ?>
      <article class="col-md-6">
        <div class="card h-100 shadow-sm border-0 bg-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 200px;">
          <i class="bi bi-info-square display-4 text-primary mb-3" style="opacity: 0.8;" aria-hidden="true"></i>
          <h2 class="h5 text-dark fw-bold"><?= htmlspecialchars($novedad['titulo']) ?></h2>
          <p class="text-muted small mb-4 px-3"><?= htmlspecialchars($novedad['contenido']) ?></p>
          <span class="badge bg-light text-dark border mt-auto shadow-sm">
            <i class="bi bi-clock-history me-1"></i>Vigente hasta: <?= date('d/m/Y', strtotime($novedad['fecha_fin'])) ?>
          </span>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<section aria-labelledby="titulo-promociones" class="mb-5">
  <div class="d-flex align-items-center mb-4">
    <h2 id="titulo-promociones" class="h4 fw-bold mb-0" style="color: #0A2342;">Beneficios y Oportunidades</h2>
    <div class="flex-grow-1 border-bottom ms-4"></div>
  </div>

  <div class="row g-4">
    <?php if (empty($promocionesAprobadas)): ?>
      <div class="col-12 text-center p-5 bg-white shadow-sm rounded-3 border-0">
        <i class="bi bi-tags display-4 text-muted opacity-50 mb-3 d-block"></i>
        <p class="text-muted small mb-0">No hay beneficios activos publicados en este momento.</p>
      </div>
    <?php else: ?>
      <?php foreach ($promocionesAprobadas as $promo): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0 bg-white text-center">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
              <span class="badge bg-success bg-opacity-10 text-success border border-success fw-bold px-3 py-2">
                Promoción Autorizada
              </span>
            </div>
            <div class="card-body p-4 d-flex flex-column">
              <div class="mb-3">
                <!-- Ajuste estructural: Llamada a la columna real -->
                <span class="display-3 fw-bold text-dark">-<?= htmlspecialchars($promo['descuento_porcentaje']) ?>%</span>
              </div>
              <h3 class="h6 fw-bold mb-2" style="color: #0A2342;">Vuelo #<?= htmlspecialchars($promo['id_vuelo']) ?></h3>
              
              <!-- El bloque de descripción inexistente fue erradicado para evitar quiebres -->
              
              <a href="vuelos.php?id_vuelo=<?= $promo['id_vuelo'] ?>" 
                 class="btn btn-outline-primary btn-sm w-100 mt-auto fw-bold" 
                 style="border-color: #0A2342; color: #0A2342;">
                Consultar Vuelo
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>