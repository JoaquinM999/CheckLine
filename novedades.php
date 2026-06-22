<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO PÚBLICO DE NOVEDADES
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();

// Extracción del historial de novedades ordenadas por fecha reciente
try {
    $pdo = getConexion();
    $stmt = $pdo->query("
        SELECT titulo, contenido, fecha_inicio, fecha_fin 
        FROM novedades 
        ORDER BY fecha_inicio DESC
    ");
    $novedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error cargando página de novedades: " . $e->getMessage());
    $novedades = [];
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Novedades y Eventos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<header role="banner">
  <nav class="navbar navbar-expand-lg navbar-dark px-3 py-2" style="background-color:#0A2342;">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-airplane-fill me-2"></i>Check-Line</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPrincipal">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarPrincipal">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="vuelos.php">Vuelos</a></li>
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="novedades.php">Novedades</a></li>
        </ul>
        <div class="d-flex gap-2 align-items-center">
          <?php if ($logueado): ?>
            <span class="text-white-50 small align-self-center me-2"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
            <a href="registro.php" class="btn btn-warning btn-sm fw-bold">Registrarse</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="container mt-4 mb-5" role="main">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Inicio</a></li>
      <li class="breadcrumb-item active fw-semibold" aria-current="page">Novedades</li>
    </ol>
  </nav>

  <div class="row g-4">
    <aside class="col-md-3">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-3">
          <h2 class="h6 fw-bold text-muted mb-0 text-center">Categoría</h2>
        </div>
        <div class="list-group list-group-flush text-center pb-2">
          <a href="#" class="list-group-item list-group-item-action fw-bold" style="background-color: #e9ecef; color: #0A2342;">Noticias</a>
          <a href="#" class="list-group-item list-group-item-action text-muted">Eventos</a>
        </div>
      </div>
    </aside>

    <section class="col-md-9">
      <h1 class="h4 fw-bold mb-4 text-center" style="color: #0A2342;">Noticias Institucionales</h1>
      
      <div class="row g-4">
        <?php if (empty($novedades)): ?>
          <div class="col-12 text-center p-5 bg-white shadow-sm rounded-3">
            <p class="text-muted mb-0">No hay información publicada en esta sección.</p>
          </div>
        <?php else: ?>
          <?php foreach ($novedades as $item): ?>
            <article class="col-md-6">
              <div class="card h-100 shadow-sm border-0">
                <div class="bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center border-bottom" style="height: 160px;">
                  <i class="bi bi-image display-4 text-muted opacity-50"></i>
                </div>
                <div class="card-body">
                  <h3 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($item['titulo']) ?></h3>
                  <small class="text-muted d-block mb-3 fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($item['fecha_inicio'])) ?>
                  </small>
                  <p class="small text-muted mb-0"><?= htmlspecialchars($item['contenido']) ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <nav aria-label="Navegación de páginas" class="mt-5">
        <ul class="pagination justify-content-center shadow-sm">
          <li class="page-item disabled"><span class="page-link">Anterior</span></li>
          <li class="page-item active"><span class="page-link" style="background-color: #0A2342; border-color: #0A2342;">1</span></li>
          <li class="page-item"><a class="page-link text-dark" href="#">2</a></li>
          <li class="page-item"><a class="page-link text-dark" href="#">Siguiente</a></li>
        </ul>
      </nav>

    </section>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>