<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - HISTORIAL Y GESTIÓN DE RESERVAS (PASAJERO)
 * ============================================================================
 * Incluye cancelación según RN #13 (hasta 72 horas antes del vuelo).
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requerirRol('pasajero');
$id_pasajero = $_SESSION['id_usuario'];
$mensaje = obtenerYLimpiarMensaje();

try {
    $pdo = getConexion();
    $sql = "
        SELECT
            r.id_reserva, r.fecha_reserva, r.estado, r.precio_final,
            v.id_vuelo, v.codigo_vuelo, v.origen, v.destino,
            v.fecha_salida, v.hora_salida
        FROM reservas r
        INNER JOIN vuelos v ON r.id_vuelo = v.id_vuelo
        WHERE r.id_usuario = :usuario
        ORDER BY r.fecha_reserva DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $id_pasajero]);
    $reservas = $stmt->fetchAll();
} catch (PDOException $e) {
    $reservas = [];
    error_log("Error cargando reservas: " . $e->getMessage());
}

// Calcular para cada reserva si la cancelación sigue siendo posible (RN #13)
$ahora = new DateTime();
foreach ($reservas as &$r) {
    $fechaSalida  = new DateTime($r['fecha_salida'] . ' ' . $r['hora_salida']);
    $diffHoras    = ($fechaSalida->getTimestamp() - $ahora->getTimestamp()) / 3600;
    $r['horas_para_salida']     = $diffHoras;
    $r['puede_cancelar']        = in_array($r['estado'], ['pendiente_pago', 'Confirmada'], true)
                                   && $diffHoras > 72;
    $r['cancelacion_bloqueada'] = in_array($r['estado'], ['pendiente_pago', 'Confirmada'], true)
                                   && $diffHoras <= 72;
}
unset($r);
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Mis Reservas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<header role="banner">
  <nav class="navbar navbar-expand-lg navbar-dark px-3 py-2" style="background-color:#0A2342;" aria-label="Navegación principal">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="index.php" aria-label="Volver a la portada">
        <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navReservas">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navReservas">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="vuelos.php">Vuelos</a></li>
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="mis_reservas.php">Mis Reservas</a></li>
          <li class="nav-item"><a class="nav-link" href="novedades.php">Novedades</a></li>
        </ul>
        <div class="d-flex gap-2 align-items-center">
          <a href="perfil.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['nombre']) ?>
          </a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="container py-5" role="main">
  <nav aria-label="Ruta de navegación" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
      <li class="breadcrumb-item active" aria-current="page">Mis Reservas</li>
    </ol>
  </nav>

  <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
      <h1 class="h3 fw-bold mb-1" style="color:#0A2342;">Mis Reservas</h1>
      <p class="text-muted small mb-0">Historial completo de tus pasajes. Podés cancelar hasta 72 hs antes del vuelo.</p>
    </div>
    <a href="vuelos.php" class="btn btn-primary btn-sm" style="background-color:#0A2342; border-color:#0A2342;">
      <i class="bi bi-plus-circle me-1"></i>Nueva reserva
    </a>
  </div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-<?= $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
      <?= htmlspecialchars($mensaje['texto']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($reservas)): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body p-5 text-center">
        <i class="bi bi-journal-x display-4 text-muted opacity-50 mb-3 d-block" aria-hidden="true"></i>
        <p class="text-muted mb-3">No tenés reservas registradas todavía.</p>
        <a href="vuelos.php" class="btn btn-outline-primary btn-sm" style="border-color:#0A2342; color:#0A2342;">
          <i class="bi bi-search me-1"></i>Explorar vuelos disponibles
        </a>
      </div>
    </div>

  <?php else: ?>

    <!-- KPIs rápidos -->
    <?php
    $totConfirmadas = count(array_filter($reservas, fn($r) => $r['estado'] === 'Confirmada'));
    $totPendientes  = count(array_filter($reservas, fn($r) => $r['estado'] === 'pendiente_pago'));
    $totCanceladas  = count(array_filter($reservas, fn($r) => $r['estado'] === 'cancelada'));
    ?>
    <div class="row g-3 mb-4">
      <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fw-bold fs-4 text-success"><?= $totConfirmadas ?></div>
          <div class="small text-muted">Confirmadas</div>
        </div>
      </div>
      <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fw-bold fs-4 text-warning"><?= $totPendientes ?></div>
          <div class="small text-muted">Pendientes de pago</div>
        </div>
      </div>
      <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fw-bold fs-4 text-secondary"><?= $totCanceladas ?></div>
          <div class="small text-muted">Canceladas</div>
        </div>
      </div>
    </div>

    <div class="d-flex flex-column gap-3">
    <?php foreach ($reservas as $r): ?>
      <div class="card border-0 shadow-sm <?= $r['estado'] === 'cancelada' ? 'opacity-75' : '' ?>">
        <div class="card-body p-4">
          <div class="row align-items-center">

            <!-- Ruta -->
            <div class="col-md-4 mb-3 mb-md-0">
              <div class="d-flex align-items-center gap-2 mb-1">
                <?php if ($r['estado'] === 'Confirmada'): ?>
                  <span class="badge bg-success">Confirmada</span>
                <?php elseif ($r['estado'] === 'pendiente_pago'): ?>
                  <span class="badge bg-warning text-dark">Pendiente de pago</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Cancelada</span>
                <?php endif; ?>
                <span class="text-muted small">Reserva #<?= (int) $r['id_reserva'] ?></span>
              </div>
              <h2 class="h6 fw-bold mb-0">
                <i class="bi bi-geo-alt text-danger me-1"></i>
                <?= htmlspecialchars($r['origen']) ?>
                <i class="bi bi-arrow-right mx-1 text-muted" style="font-size:11px;"></i>
                <?= htmlspecialchars($r['destino']) ?>
              </h2>
              <small class="text-muted">
                <i class="bi bi-tag me-1"></i><?= htmlspecialchars($r['codigo_vuelo']) ?>
              </small>
            </div>

            <!-- Fechas -->
            <div class="col-md-3 mb-3 mb-md-0">
              <div class="small text-muted mb-1">Salida del vuelo</div>
              <div class="fw-semibold">
                <i class="bi bi-calendar-event me-1 text-primary"></i>
                <?= date('d/m/Y', strtotime($r['fecha_salida'])) ?>
                <?= date('H:i', strtotime($r['hora_salida'])) ?>hs
              </div>
              <div class="small text-muted mt-1">
                Reservado: <?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?>
              </div>
            </div>

            <!-- Precio -->
            <div class="col-md-2 mb-3 mb-md-0 text-center">
              <div class="small text-muted mb-1">Importe</div>
              <div class="fw-bold fs-5" style="color:#0A2342;">
                $<?= number_format((float) $r['precio_final'], 0, ',', '.') ?>
              </div>
            </div>

            <!-- Acciones -->
            <div class="col-md-3 text-md-end">
              <?php if ($r['puede_cancelar']): ?>
                <?php
                $horas = $r['horas_para_salida'];
                $diasRestantes = floor($horas / 24);
                $textoHoras = $diasRestantes >= 1
                    ? "Quedan {$diasRestantes} día(s)"
                    : number_format($horas, 0) . 'hs para salida';
                ?>
                <div class="small text-muted mb-2">
                  <i class="bi bi-clock me-1"></i><?= $textoHoras ?>
                </div>
                <button class="btn btn-outline-danger btn-sm"
                        onclick="confirmarCancelacion(<?= (int) $r['id_reserva'] ?>, '<?= htmlspecialchars(addslashes($r['codigo_vuelo'])) ?>')">
                  <i class="bi bi-x-circle me-1"></i>Cancelar reserva
                </button>

              <?php elseif ($r['cancelacion_bloqueada']): ?>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:11px;">
                  <i class="bi bi-lock me-1"></i>Cancelación no disponible
                </span>
                <div class="small text-muted mt-1">Menos de 72 hs para el vuelo</div>

              <?php elseif ($r['estado'] === 'cancelada'): ?>
                <span class="text-muted small">
                  <i class="bi bi-check2 me-1"></i>Cancelada
                </span>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Modal confirmación cancelación -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="modalCancelarTitulo">
  <div class="modal-dialog">
    <form method="POST" action="cancelar_reserva.php" class="modal-content">
      <input type="hidden" name="id_reserva" id="cancelarId" value="">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalCancelarTitulo">
          <i class="bi bi-exclamation-triangle me-2"></i>Confirmar cancelación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Seguro que querés cancelar la reserva del vuelo <strong id="cancelarCodigo"></strong>?</p>
        <div class="alert alert-warning py-2 small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Esta acción no se puede deshacer. El asiento será liberado y la reserva quedará <strong>cancelada</strong>.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Volver</button>
        <button type="submit" class="btn btn-danger btn-sm fw-bold">
          <i class="bi bi-x-circle me-1"></i>Sí, cancelar reserva
        </button>
      </div>
    </form>
  </div>
</div>

<footer class="text-white py-3 mt-5" style="background-color:#0A2342;" role="contentinfo">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small"><i class="bi bi-airplane-fill me-1"></i>Check-Line &copy; <?= date('Y') ?></span>
    <nav class="d-flex gap-3">
      <a href="mapa-sitio.php" class="text-white-50 text-decoration-none small">Mapa del Sitio</a>
      <a href="privacidad.php" class="text-white-50 text-decoration-none small">Política de Privacidad</a>
    </nav>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
<script>
function confirmarCancelacion(id, codigo) {
  document.getElementById('cancelarId').value = id;
  document.getElementById('cancelarCodigo').textContent = codigo;
  new bootstrap.Modal(document.getElementById('modalCancelar')).show();
}
</script>
</body>
</html>
