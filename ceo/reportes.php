<?php
/**
 * ============================================================================
 * CHECK-LINE — REPORTES CEO
 * ============================================================================
 * Dos pestañas:
 *   A) Reporte de Ventas de mi Aerolínea
 *   B) Reporte de Ocupación de mis Vuelos
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

$pdo = getConexion();

$stmtAer = $pdo->prepare("SELECT id_aerolinea, nombre, codigo FROM aerolineas WHERE id_ceo = :id");
$stmtAer->execute(['id' => $_SESSION['id_usuario']]);
$aerolineaCeo = $stmtAer->fetch();

$tituloPagina  = 'Check-Line — Reportes CEO';
$seccionActiva = 'reportes';

if (!$aerolineaCeo) {
    require __DIR__ . '/../includes/header_ceo.php';
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Todavía no tenés una aerolínea asignada. Contactá al Administrador.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$idAerolinea = (int) $aerolineaCeo['id_aerolinea'];
$fechaDesde  = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta  = $_GET['hasta'] ?? date('Y-m-d');
$tabActiva   = $_GET['tab']   ?? 'ventas';

// A) Ventas confirmadas en vuelos de esta aerolínea
$stmtVentas = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_reserva, r.precio_final,
           u.nombre AS pasajero_nombre, u.apellido AS pasajero_apellido,
           v.codigo_vuelo, v.origen, v.destino, v.fecha_salida
    FROM reservas r
    INNER JOIN vuelos   v ON v.id_vuelo   = r.id_vuelo
    INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
    WHERE r.estado = 'Confirmada'
      AND v.id_aerolinea = :id_aerolinea
      AND DATE(r.fecha_reserva) BETWEEN :desde AND :hasta
    ORDER BY r.fecha_reserva DESC
");
$stmtVentas->execute(['id_aerolinea' => $idAerolinea, 'desde' => $fechaDesde, 'hasta' => $fechaHasta]);
$ventas        = $stmtVentas->fetchAll();
$totalVentas   = count($ventas);
$totalIngresos = array_sum(array_column($ventas, 'precio_final'));

// B) Ocupación de vuelos de la aerolínea
$stmtVuelos = $pdo->prepare("
    SELECT v.id_vuelo, v.codigo_vuelo, v.origen, v.destino,
           v.fecha_salida, v.asientos_totales, v.asientos_disponibles, v.estado,
           COUNT(r.id_reserva) AS total_reservas,
           SUM(CASE WHEN r.estado = 'Confirmada' THEN 1 ELSE 0 END) AS confirmadas,
           SUM(CASE WHEN r.estado = 'cancelada'  THEN 1 ELSE 0 END) AS canceladas,
           COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.precio_final ELSE 0 END), 0) AS ingresos
    FROM vuelos v
    LEFT JOIN reservas r ON r.id_vuelo = v.id_vuelo
    WHERE v.id_aerolinea = :id
    GROUP BY v.id_vuelo
    ORDER BY v.fecha_salida DESC
");
$stmtVuelos->execute(['id' => $idAerolinea]);
$vuelos = $stmtVuelos->fetchAll();

require __DIR__ . '/../includes/header_ceo.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="vuelos.php">Inicio</a></li>
  <li class="breadcrumb-item active">Reportes</li>
</ol></nav>

<h6 class="fw-semibold mb-3">
  <i class="bi bi-bar-chart-line me-2 text-primary"></i>Reportes
  <span class="text-muted fw-normal small">— <?= htmlspecialchars($aerolineaCeo['nombre']) ?></span>
</h6>

<ul class="nav nav-tabs mb-0" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $tabActiva === 'ventas'    ? 'active' : '' ?>" href="?tab=ventas">
      <i class="bi bi-cash-coin me-1"></i>Ventas de mi Aerolínea
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tabActiva === 'ocupacion' ? 'active' : '' ?>" href="?tab=ocupacion">
      <i class="bi bi-bar-chart me-1"></i>Ocupación de Vuelos
    </a>
  </li>
</ul>

<div class="border border-top-0 rounded-bottom bg-white p-3 shadow-sm mb-4">

<?php if ($tabActiva === 'ventas'): ?>

  <form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="tab" value="ventas">
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Desde</label>
      <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDesde) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Hasta</label>
      <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaHasta) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
    <div class="col-md-2">
      <a href="?tab=ventas" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
    </div>
  </form>

  <div class="row g-3 mb-3">
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-primary"><?= $totalVentas ?></div>
        <div class="small text-muted">Pasajes vendidos</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-success">$<?= number_format($totalIngresos, 0, ',', '.') ?></div>
        <div class="small text-muted">Ingresos del período</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4" style="color:#0A2342;">
          $<?= $totalVentas > 0 ? number_format($totalIngresos / $totalVentas, 0, ',', '.') : '0' ?>
        </div>
        <div class="small text-muted">Ticket promedio</div>
      </div>
    </div>
  </div>

  <?php if (empty($ventas)): ?>
    <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>No hay ventas confirmadas en el período.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle" style="font-size:12px;">
      <thead class="table-dark">
        <tr><th>#</th><th>Fecha</th><th>Pasajero</th><th>Vuelo</th><th>Salida</th><th class="text-end">Importe</th></tr>
      </thead>
      <tbody>
        <?php foreach ($ventas as $v): ?>
        <tr>
          <td class="text-muted"><?= (int) $v['id_reserva'] ?></td>
          <td><?= date('d/m/Y', strtotime($v['fecha_reserva'])) ?></td>
          <td><?= htmlspecialchars($v['pasajero_nombre'] . ' ' . $v['pasajero_apellido']) ?></td>
          <td>
            <span class="badge bg-secondary"><?= htmlspecialchars($v['codigo_vuelo']) ?></span>
            <small class="text-muted ms-1"><?= htmlspecialchars($v['origen']) ?> → <?= htmlspecialchars($v['destino']) ?></small>
          </td>
          <td><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?></td>
          <td class="text-end fw-bold">$<?= number_format((float) $v['precio_final'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td colspan="5" class="text-end fw-bold">Total período:</td>
          <td class="text-end fw-bold text-success">$<?= number_format($totalIngresos, 0, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($tabActiva === 'ocupacion'): ?>

  <?php
  $totalAsient = array_sum(array_column($vuelos, 'asientos_totales'));
  $dispAsient  = array_sum(array_column($vuelos, 'asientos_disponibles'));
  $ocupacion   = $totalAsient > 0 ? round((($totalAsient - $dispAsient) / $totalAsient) * 100, 1) : 0;
  $totIngresos = array_sum(array_column($vuelos, 'ingresos'));
  ?>
  <div class="row g-3 mb-3">
    <div class="col-sm-3">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-primary"><?= count($vuelos) ?></div>
        <div class="small text-muted">Total vuelos</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4" style="color:#0A2342;"><?= $totalAsient - $dispAsient ?></div>
        <div class="small text-muted">Asientos vendidos</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-warning"><?= $ocupacion ?>%</div>
        <div class="small text-muted">Ocupación global</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-success">$<?= number_format($totIngresos, 0, ',', '.') ?></div>
        <div class="small text-muted">Ingresos totales</div>
      </div>
    </div>
  </div>

  <?php if (empty($vuelos)): ?>
    <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>No hay vuelos registrados para tu aerolínea.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle" style="font-size:12px;">
      <thead class="table-dark">
        <tr><th>Código</th><th>Ruta</th><th>Salida</th><th>Estado</th><th>Ocupación</th><th class="text-center">Confirmadas</th><th class="text-center">Canceladas</th><th class="text-end">Ingresos</th></tr>
      </thead>
      <tbody>
        <?php foreach ($vuelos as $v):
          $ocupados = (int) $v['asientos_totales'] - (int) $v['asientos_disponibles'];
          $pct      = $v['asientos_totales'] > 0 ? round(($ocupados / $v['asientos_totales']) * 100) : 0;
          $colorBar = $pct >= 90 ? 'bg-danger' : ($pct >= 60 ? 'bg-warning' : 'bg-success');
          $badgeEst = match($v['estado']) { 'activo' => 'bg-success', 'cancelado' => 'bg-danger', default => 'bg-secondary' };
        ?>
        <tr>
          <td><span class="badge bg-secondary"><?= htmlspecialchars($v['codigo_vuelo']) ?></span></td>
          <td><?= htmlspecialchars($v['origen']) ?> → <?= htmlspecialchars($v['destino']) ?></td>
          <td><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?></td>
          <td><span class="badge <?= $badgeEst ?>"><?= ucfirst($v['estado']) ?></span></td>
          <td style="min-width:140px;">
            <div class="d-flex align-items-center gap-1">
              <div class="progress flex-grow-1" style="height:8px;">
                <div class="progress-bar <?= $colorBar ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <small class="text-muted"><?= $ocupados ?>/<?= (int) $v['asientos_totales'] ?> (<?= $pct ?>%)</small>
            </div>
          </td>
          <td class="text-center"><?= (int) $v['confirmadas'] ?></td>
          <td class="text-center"><?= (int) $v['canceladas'] ?></td>
          <td class="text-end fw-semibold">
            <?= $v['ingresos'] > 0 ? '$' . number_format((float) $v['ingresos'], 0, ',', '.') : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td colspan="7" class="text-end fw-bold">Total ingresos:</td>
          <td class="text-end fw-bold text-success">$<?= number_format($totIngresos, 0, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>

<?php endif; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
