<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - LISTADO PÚBLICO DE PROMOCIONES
 * ============================================================================
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();

// Extracción de promociones autorizadas ajustada al esquema físico
try {
    $pdo = getConexion();
    // Ajuste crítico de columnas: no pedimos 'descripcion' y usamos 'descuento_porcentaje'
    $stmt = $pdo->query("
        SELECT id_vuelo, descuento_porcentaje 
        FROM promociones 
        WHERE estado = 'Aprobada' 
        ORDER BY descuento_porcentaje DESC
    ");
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error cargando promociones: " . $e->getMessage());
    die("Error crítico de base de datos: " . $e->getMessage()); // Detenemos la ejecución si falla
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Promociones Vigentes</title>
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
          <li class="nav-item"><a class="nav-link" href="novedades.php">Novedades</a></li>
          <li class="nav-item"><a class="nav-link active fw-semibold text-warning" aria-current="page" href="promociones_publicas.php">Promociones</a></li>
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
      <li class="breadcrumb-item active fw-semibold" aria-current="page">Promociones</li>
    </ol>
  </nav>

  <div class="text-center mb-5">
    <h1 class="h3 fw-bold" style="color: #0A2342;">Beneficios y Oportunidades</h1>
    <p class="text-muted">Explore los descuentos vigentes autorizados por el sistema.</p>
  </div>

  <div class="row g-4">
    <?php if (empty($promociones)): ?>
      <div class="col-12 text-center p-5 bg-white shadow-sm rounded-3">
        <i class="bi bi-tags display-4 text-muted opacity-50 mb-3 d-block"></i>
        <p class="text-muted mb-0">No hay beneficios activos publicados en este momento.</p>
      </div>
    <?php else: ?>
      <?php foreach ($promociones as $promo): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0 bg-white text-center">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
              <span class="badge bg-success bg-opacity-10 text-success border border-success fw-bold px-3 py-2">
                Promoción Autorizada
              </span>
            </div>
<div class="card-body p-4 d-flex flex-column">
              <div class="mb-3">
                <span class="display-3 fw-bold text-dark">-<?= htmlspecialchars($promo['descuento_porcentaje']) ?>%</span>
              </div>
              <h3 class="h6 fw-bold mb-2" style="color: #0A2342;">Vuelo #<?= htmlspecialchars($promo['id_vuelo']) ?></h3>
              
              <a href="index.php?id_vuelo=<?= $promo['id_vuelo'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-auto fw-bold" style="border-color: #0A2342; color: #0A2342;">
                Consultar Vuelo
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>