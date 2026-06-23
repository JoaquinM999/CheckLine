<?php
/**
 * ============================================================================
 * CHECK-LINE — REPORTES ADMINISTRADOR
 * ============================================================================
 * Tres pestañas:
 *   A) Reporte de Ventas   — reservas confirmadas con filtro por fecha
 *   B) Reporte de Vuelos   — ocupación y estado de todos los vuelos
 *   C) Reporte de Usuarios — pasajeros con cantidad de reservas y gasto
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

$pdo = getConexion();

$fechaDesde = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['hasta'] ?? date('Y-m-d');
$tabActiva  = $_GET['tab']   ?? 'ventas';

// A) Ventas confirmadas en el período
$stmtVentas = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_reserva, r.precio_final,
           u.nombre AS pasajero_nombre, u.apellido AS pasajero_apellido, u.email AS pasajero_email,
           v.codigo_vuelo, v.origen, v.destino, v.fecha_salida,
           a.nombre AS aerolinea
    FROM reservas r
    INNER JOIN usuarios   u ON u.id_usuario   = r.id_usuario
    INNER JOIN vuelos     v ON v.id_vuelo     = r.id_vuelo
    INNER JOIN aerolineas a ON a.id_aerolinea = v.id_aerolinea
    WHERE r.estado = 'Confirmada'
      AND DATE(r.fecha_reserva) BETWEEN :desde AND :hasta
    ORDER BY r.fecha_reserva DESC
");
$stmtVentas->execute(['desde' => $fechaDesde, 'hasta' => $fechaHasta]);
$ventas = $stmtVentas->fetchAll();
$totalVentas   = count($ventas);
$totalIngresos = array_sum(array_column($ventas, 'precio_final'));

// B) Vuelos con ocupación
$vuelos = $pdo->query("
    SELECT v.id_vuelo, v.codigo_vuelo, v.origen, v.destino,
           v.fecha_salida, v.asientos_totales, v.asientos_disponibles, v.estado,
           a.nombre AS aerolinea,
           COUNT(r.id_reserva) AS total_reservas,
           SUM(CASE WHEN r.estado = 'Confirmada' THEN 1 ELSE 0 END) AS confirmadas,
           SUM(CASE WHEN r.estado = 'cancelada'  THEN 1 ELSE 0 END) AS canceladas
    FROM vuelos v
    INNER JOIN aerolineas a ON a.id_aerolinea = v.id_aerolinea
    LEFT  JOIN reservas r   ON r.id_vuelo     = v.id_vuelo
    GROUP BY v.id_vuelo
    ORDER BY v.fecha_salida DESC
")->fetchAll();

// C) Usuarios pasajeros
$usuarios = $pdo->query("
    SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.fecha_registro, u.activo,
           COUNT(r.id_reserva) AS total_reservas,
           SUM(CASE WHEN r.estado = 'Confirmada' THEN 1 ELSE 0 END) AS compras,
           COALESCE(SUM(CASE WHEN r.estado = 'Confirmada' THEN r.precio_final ELSE 0 END), 0) AS gasto_total
    FROM usuarios u
    INNER JOIN roles ro ON ro.id_rol = u.id_rol AND ro.nombre_rol = 'pasajero'
    LEFT  JOIN reservas r ON r.id_usuario = u.id_usuario
    GROUP BY u.id_usuario
    ORDER BY compras DESC, u.fecha_registro DESC
")->fetchAll();

$tituloPagina  = 'Check-Line — Reportes';
$seccionActiva = 'reportes';
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="aerolineas.php">Inicio</a></li>
  <li class="breadcrumb-item active">Reportes</li>
</ol></nav>

<h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Reportes del Sistema</h6>

<ul class="nav nav-tabs mb-0" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $tabActiva === 'ventas'   ? 'active' : '' ?>" href="?tab=ventas">
      <i class="bi bi-cash-coin me-1"></i>Ventas
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tabActiva === 'vuelos'   ? 'active' : '' ?>" href="?tab=vuelos">
      <i class="bi bi-airplane me-1"></i>Vuelos
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tabActiva === 'usuarios' ? 'active' : '' ?>" href="?tab=usuarios">
      <i class="bi bi-people me-1"></i>Usuarios
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
        <div class="fw-bold fs-3 text-primary"><?= $totalVentas ?></div>
        <div class="small text-muted">Ventas en el período</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-success">$<?= number_format($totalIngresos, 0, ',', '.') ?></div>
        <div class="small text-muted">Ingresos totales</div>
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
    <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>No hay ventas confirmadas en el período seleccionado.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle" style="font-size:12px;">
      <thead class="table-dark">
        <tr>
          <th>#</th><th>Fecha</th><th>Pasajero</th><th>Vuelo</th><th>Aerolínea</th><th>Salida</th><th class="text-end">Importe</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ventas as $v): ?>
        <tr>
          <td class="text-muted"><?= (int) $v['id_reserva'] ?></td>
          <td><?= date('d/m/Y', strtotime($v['fecha_reserva'])) ?></td>
          <td>
            <?= htmlspecialchars($v['pasajero_nombre'] . ' ' . $v['pasajero_apellido']) ?>
            <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($v['pasajero_email']) ?></div>
          </td>
          <td>
            <span class="badge bg-secondary"><?= htmlspecialchars($v['codigo_vuelo']) ?></span>
            <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($v['origen']) ?> → <?= htmlspecialchars($v['destino']) ?></div>
          </td>
          <td><?= htmlspecialchars($v['aerolinea']) ?></td>
          <td><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?></td>
          <td class="text-end fw-bold">$<?= number_format((float) $v['precio_final'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td colspan="6" class="text-end fw-bold">Total período:</td>
          <td class="text-end fw-bold text-success">$<?= number_format($totalIngresos, 0, ',', '.') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($tabActiva === 'vuelos'): ?>

  <?php
  $vActivos    = count(array_filter($vuelos, fn($v) => $v['estado'] === 'activo'));
  $totalAsient = array_sum(array_column($vuelos, 'asientos_totales'));
  $dispAsient  = array_sum(array_column($vuelos, 'asientos_disponibles'));
  $ocupacion   = $totalAsient > 0 ? round((($totalAsient - $dispAsient) / $totalAsient) * 100, 1) : 0;
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
        <div class="fw-bold fs-4 text-success"><?= $vActivos ?></div>
        <div class="small text-muted">Activos</div>
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
  </div>

  <?php if (empty($vuelos)): ?>
    <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>No hay vuelos registrados.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle" style="font-size:12px;">
      <thead class="table-dark">
        <tr><th>Código</th><th>Ruta</th><th>Aerolínea</th><th>Salida</th><th>Estado</th><th>Ocupación</th><th class="text-center">Confirmadas</th><th class="text-center">Canceladas</th></tr>
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
          <td class="text-muted"><?= htmlspecialchars($v['aerolinea']) ?></td>
          <td><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?></td>
          <td><span class="badge <?= $badgeEst ?>"><?= ucfirst($v['estado']) ?></span></td>
          <td style="min-width:130px;">
            <div class="d-flex align-items-center gap-1">
              <div class="progress flex-grow-1" style="height:8px;">
                <div class="progress-bar <?= $colorBar ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <small class="text-muted"><?= $ocupados ?>/<?= (int) $v['asientos_totales'] ?></small>
            </div>
          </td>
          <td class="text-center"><?= (int) $v['confirmadas'] ?></td>
          <td class="text-center"><?= (int) $v['canceladas'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php elseif ($tabActiva === 'usuarios'): ?>

  <?php
  $totalPasajeros = count($usuarios);
  $activados      = count(array_filter($usuarios, fn($u) => (int) $u['activo'] === 1));
  $conCompras     = count(array_filter($usuarios, fn($u) => (int) $u['compras'] > 0));
  ?>
  <div class="row g-3 mb-3">
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-primary"><?= $totalPasajeros ?></div>
        <div class="small text-muted">Pasajeros registrados</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4 text-success"><?= $activados ?></div>
        <div class="small text-muted">Cuentas activas</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 bg-light text-center py-3">
        <div class="fw-bold fs-4" style="color:#0A2342;"><?= $conCompras ?></div>
        <div class="small text-muted">Con al menos una compra</div>
      </div>
    </div>
  </div>

  <?php if (empty($usuarios)): ?>
    <div class="alert alert-info small"><i class="bi bi-info-circle me-2"></i>No hay pasajeros registrados.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle" style="font-size:12px;">
      <thead class="table-dark">
        <tr><th>#</th><th>Pasajero</th><th>Email</th><th>Registro</th><th>Estado</th><th class="text-center">Reservas</th><th class="text-center">Compras</th><th class="text-end">Gasto total</th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td class="text-muted"><?= (int) $u['id_usuario'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
          <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
          <td>
            <?php if ((int) $u['activo'] === 1): ?>
              <span class="badge bg-success">Activo</span>
            <?php else: ?>
              <span class="badge bg-secondary">Sin activar</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= (int) $u['total_reservas'] ?></td>
          <td class="text-center">
            <span class="badge <?= $u['compras'] > 0 ? 'bg-primary' : 'bg-light text-dark border' ?>">
              <?= (int) $u['compras'] ?>
            </span>
          </td>
          <td class="text-end fw-semibold">
            <?= $u['gasto_total'] > 0 ? '$' . number_format((float) $u['gasto_total'], 0, ',', '.') : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

<?php endif; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
