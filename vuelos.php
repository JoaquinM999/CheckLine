<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - CATÁLOGO PÚBLICO DE VUELOS
 * ============================================================================
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();

// Extracción de vuelos operativos con cruce de promociones aprobadas
// --- Extracción y Filtrado Dinámico de Vuelos ---
try {
    $pdo = getConexion();
    
    // Captura de filtros ingresados por el pasajero
    $origen   = trim($_GET['origen'] ?? '');
    $destino  = trim($_GET['destino'] ?? '');
    $fecha    = trim($_GET['fecha'] ?? '');
    $id_vuelo = trim($_GET['id_vuelo'] ?? ''); // Captura si viene desde un botón de promoción

    // Reglas base (seguridad operativa)
    $condiciones = ["v.estado = 'activo'", "v.fecha_salida >= CURDATE()"];
    $parametros  = [];

    // Inyección condicional de filtros
    if ($origen !== '') {
        $condiciones[] = "v.origen LIKE :origen";
        $parametros['origen'] = '%' . $origen . '%';
    }
    if ($destino !== '') {
        $condiciones[] = "v.destino LIKE :destino";
        $parametros['destino'] = '%' . $destino . '%';
    }
    if ($fecha !== '') {
        $condiciones[] = "v.fecha_salida = :fecha";
        $parametros['fecha'] = $fecha;
    }
    if ($id_vuelo !== '') {
        $condiciones[] = "v.id_vuelo = :id_vuelo";
        $parametros['id_vuelo'] = $id_vuelo;
    }

    // Armado final de la consulta
    $sqlWhere = implode(' AND ', $condiciones);
    $sql = "
        SELECT 
            v.id_vuelo, v.codigo_vuelo, v.origen, v.destino, 
            v.fecha_salida, v.hora_salida, v.fecha_llegada, v.hora_llegada, 
            v.precio, v.asientos_disponibles,
            a.nombre AS aerolinea,
            p.descuento_porcentaje
        FROM vuelos v
        INNER JOIN aerolineas a ON v.id_aerolinea = a.id_aerolinea
        LEFT JOIN promociones p ON v.id_vuelo = p.id_vuelo AND p.estado = 'Aprobada'
        WHERE $sqlWhere
        ORDER BY v.fecha_salida ASC, v.hora_salida ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $vuelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error cargando catálogo de vuelos: " . $e->getMessage());
    die("Falla de Integridad: No es posible conectar con el itinerario de vuelos.");
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Vuelos Disponibles</title>
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
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="vuelos.php">Vuelos</a></li>
          <li class="nav-item"><a class="nav-link" href="novedades.php">Novedades</a></li>
          <li class="nav-item"><a class="nav-link fw-semibold text-warning" href="promociones_publicas.php">Promociones</a></li>
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
      <li class="breadcrumb-item active fw-semibold" aria-current="page">Vuelos Disponibles</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-3">
    <div>
      <h1 class="h3 fw-bold mb-1" style="color: #0A2342;">Itinerario Operativo</h1>
      <p class="text-muted small mb-0">Seleccione su destino con total seguridad.</p>
    </div>
  </div>

  <div class="row g-4">
    <?php if (empty($vuelos)): ?>
      <div class="col-12 text-center p-5 bg-white shadow-sm rounded-3">
        <i class="bi bi-calendar-x display-4 text-muted opacity-50 mb-3 d-block"></i>
        <p class="text-muted mb-0">No hay vuelos programados para las fechas solicitadas.</p>
      </div>
    <?php else: ?>
      <?php foreach ($vuelos as $v): 
        // Cálculo de tarifa dinámica
        $precio_base = (float) $v['precio'];
        $descuento = $v['descuento_porcentaje'] ? (float) $v['descuento_porcentaje'] : 0;
        $precio_final = $precio_base - ($precio_base * ($descuento / 100));
        
        $agotado = (int)$v['asientos_disponibles'] <= 0;
      ?>
        <article class="col-12">
          <div class="card shadow-sm border-0 bg-white">
            <div class="card-body p-4">
              <div class="row align-items-center text-center text-md-start">
                
                <div class="col-md-2 mb-3 mb-md-0 border-md-end">
                  <span class="badge bg-light text-dark border mb-2 px-2 py-1"><i class="bi bi-building me-1"></i><?= htmlspecialchars($v['aerolinea']) ?></span>
                  <h2 class="h6 fw-bold mb-0 text-muted"><?= htmlspecialchars($v['codigo_vuelo']) ?></h2>
                </div>

                <div class="col-md-3 mb-3 mb-md-0">
                  <p class="small text-muted text-uppercase fw-semibold mb-1">Origen</p>
                  <h3 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($v['origen']) ?></h3>
                  <p class="small text-muted mb-0"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?> | <?= date('H:i', strtotime($v['hora_salida'])) ?> hs</p>
                </div>

                <div class="col-md-3 mb-3 mb-md-0">
                  <p class="small text-muted text-uppercase fw-semibold mb-1">Destino</p>
                  <h3 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($v['destino']) ?></h3>
                  <p class="small text-muted mb-0"><i class="bi bi-geo-alt-fill me-1"></i><?= date('d/m/Y', strtotime($v['fecha_llegada'])) ?> | <?= date('H:i', strtotime($v['hora_llegada'])) ?> hs</p>
                </div>

                <div class="col-md-4 text-md-end">
                  <?php if ($descuento > 0): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success mb-2">-<?= $descuento ?>% Aplicado</span>
                    <p class="small text-muted text-decoration-line-through mb-0">$<?= number_format($precio_base, 2, ',', '.') ?></p>
                  <?php endif; ?>
                  
                  <h4 class="h3 fw-bold mb-3" style="color: #0A2342;">$<?= number_format($precio_final, 2, ',', '.') ?></h4>
                  
                  <?php if ($agotado): ?>
                    <button class="btn btn-secondary w-100 fw-bold" disabled>Asientos Agotados</button>
                  <?php else: ?>
                    <form action="reservar.php" method="GET">
                      <input type="hidden" name="id_vuelo" value="<?= $v['id_vuelo'] ?>">
                      <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" style="background-color: #0A2342; border-color: #0A2342;">
                        Seleccionar
                      </button>
                    </form>
                  <?php endif; ?>
                  
                </div>

              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>