<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - HISTORIAL DE RESERVAS (PASAJERO)
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Exigencia estricta de identidad
requerirRol('pasajero');
$id_pasajero = $_SESSION['id_usuario'];
$mensaje = obtenerYLimpiarMensaje();

try {
    $pdo = getConexion();
    $sql = "
        SELECT 
            r.id_reserva, r.fecha_reserva, r.estado, r.precio_final,
            v.codigo_vuelo, v.origen, v.destino, v.fecha_salida
        FROM reservas r
        INNER JOIN vuelos v ON r.id_vuelo = v.id_vuelo
        WHERE r.id_usuario = :usuario
        ORDER BY r.fecha_reserva DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $id_pasajero]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservas = [];
    error_log("Error cargando reservas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Check-Line — Mis Reservas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<main class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
      <h1 class="h3 fw-bold mb-1" style="color: #0A2342;">Mis Reservas</h1>
      <p class="text-muted small mb-0">Historial completo de pasajes emitidos a su nombre.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house-door me-1"></i>Volver al Inicio</a>
  </div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> shadow-sm">
      <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <?php if (empty($reservas)): ?>
        <div class="p-5 text-center">
          <i class="bi bi-journal-x display-4 text-muted opacity-50 mb-3 d-block"></i>
          <p class="text-muted mb-0">No posee reservas registradas en la institución.</p>
          <a href="vuelos.php" class="btn btn-outline-primary mt-3 btn-sm" style="border-color: #0A2342; color: #0A2342;">Explorar Vuelos</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th class="px-4">Código Operativo</th>
                <th>Ruta</th>
                <th>Salida</th>
                <th>Inversión</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservas as $r): ?>
              <tr>
                <td class="px-4 fw-bold text-dark">#<?= htmlspecialchars($r['codigo_vuelo']) ?></td>
                <td class="small fw-semibold"><?= htmlspecialchars($r['origen']) ?> <i class="bi bi-arrow-right mx-1 text-muted"></i> <?= htmlspecialchars($r['destino']) ?></td>
                <td class="text-muted small"><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                <td class="fw-bold">$<?= number_format($r['precio_final'], 2, ',', '.') ?></td>
                <td>
                  <?php if ($r['estado'] === 'Confirmada'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Confirmada</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1"><?= htmlspecialchars($r['estado']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>