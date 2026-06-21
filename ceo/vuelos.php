<?php
/**
 * CHECK-LINE — ABMC Vuelos (CEO)
 * Cada CEO solo ve y gestiona los vuelos de SU aerolínea (filtro de pertenencia).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

$pdo = getConexion();

// ---------------------------------------------------------------
// Aerolínea del CEO logueado
// ---------------------------------------------------------------
$stmtAerolinea = $pdo->prepare("
    SELECT id_aerolinea, nombre, codigo
    FROM aerolineas
    WHERE id_ceo = :id_ceo
");
$stmtAerolinea->execute(['id_ceo' => $_SESSION['id_usuario']]);
$aerolineaCeo = $stmtAerolinea->fetch();

$tituloPagina  = 'Check-Line — Gestión de Vuelos';
$seccionActiva = 'vuelos';

// Caso borde: CEO sin aerolínea asignada todavía (el Admin no se la asignó)
if (!$aerolineaCeo) {
    require __DIR__ . '/../includes/header_ceo.php';
    ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      Todavía no tenés una aerolínea asignada en el sistema. Contactá al Administrador para que te la asigne
      antes de poder cargar vuelos.
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$idAerolinea = (int) $aerolineaCeo['id_aerolinea'];

// ---------------------------------------------------------------
// Búsqueda
// ---------------------------------------------------------------
$busqueda = trim($_GET['q'] ?? '');
$param = '%' . $busqueda . '%';

// ---------------------------------------------------------------
// Paginación
// ---------------------------------------------------------------
$porPagina = 5;
$paginaActual = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($paginaActual - 1) * $porPagina;

$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM vuelos
    WHERE id_aerolinea = :id_aerolinea
      AND (codigo_vuelo LIKE :busq1 OR origen LIKE :busq2 OR destino LIKE :busq3)
");
$stmtCount->execute([
    'id_aerolinea' => $idAerolinea,
    'busq1' => $param, 'busq2' => $param, 'busq3' => $param,
]);
$totalRegistros = (int) $stmtCount->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

// ---------------------------------------------------------------
// Listado (filtrado por pertenencia a la aerolínea del CEO)
// ---------------------------------------------------------------
$sql = "
    SELECT id_vuelo, codigo_vuelo, origen, destino, fecha_salida, hora_salida,
           fecha_llegada, hora_llegada, precio, asientos_totales, asientos_disponibles, estado
    FROM vuelos
    WHERE id_aerolinea = :id_aerolinea
      AND (codigo_vuelo LIKE :busq1 OR origen LIKE :busq2 OR destino LIKE :busq3)
    ORDER BY fecha_salida DESC, hora_salida DESC
    LIMIT :limite OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id_aerolinea', $idAerolinea, PDO::PARAM_INT);
$stmt->bindValue(':busq1', $param, PDO::PARAM_STR);
$stmt->bindValue(':busq2', $param, PDO::PARAM_STR);
$stmt->bindValue(':busq3', $param, PDO::PARAM_STR);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$vuelos = $stmt->fetchAll();

require __DIR__ . '/../includes/header_ceo.php';

$badgeEstado = [
    'activo'     => 'bg-success',
    'cancelado'  => 'bg-danger',
    'finalizado' => 'bg-secondary',
];
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="vuelos.php">Inicio</a></li>
  <li class="breadcrumb-item active">Gestión de Vuelos</li>
</ol></nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-semibold mb-0">
    <i class="bi bi-airplane me-2 text-primary"></i>Gestión de Vuelos
    <span class="text-muted fw-normal small">— <?= htmlspecialchars($aerolineaCeo['nombre']) ?></span>
  </h6>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
    <i class="bi bi-plus-circle me-1"></i>Nuevo Vuelo
  </button>
</div>

<form method="GET" class="row mb-3 g-2">
  <div class="col-md-5">
    <div class="input-group input-group-sm">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" name="q" class="form-control" placeholder="Buscar por código, origen o destino..."
             value="<?= htmlspecialchars($busqueda) ?>">
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
      <?php if ($busqueda !== ''): ?>
        <a href="vuelos.php" class="btn btn-outline-danger">Limpiar</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if (empty($vuelos)): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <?php if ($busqueda !== ''): ?>
      No se encontraron vuelos para "<?= htmlspecialchars($busqueda) ?>".
    <?php else: ?>
      Todavía no cargaste vuelos. Hacé clic en "Nuevo Vuelo" para crear el primero.
    <?php endif; ?>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-striped table-hover table-bordered align-middle" style="font-size:12.5px;">
    <thead class="table-dark">
      <tr>
        <th>Código</th>
        <th>Ruta</th>
        <th>Salida</th>
        <th>Llegada</th>
        <th>Precio</th>
        <th>Asientos</th>
        <th>Estado</th>
        <th style="width:90px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($vuelos as $v): ?>
      <tr>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($v['codigo_vuelo']) ?></span></td>
        <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($v['origen']) ?> → <?= htmlspecialchars($v['destino']) ?></td>
        <td><?= date('d/m/Y', strtotime($v['fecha_salida'])) ?><br><small class="text-muted"><?= substr($v['hora_salida'], 0, 5) ?>hs</small></td>
        <td><?= date('d/m/Y', strtotime($v['fecha_llegada'])) ?><br><small class="text-muted"><?= substr($v['hora_llegada'], 0, 5) ?>hs</small></td>
        <td class="fw-semibold">$<?= number_format((float) $v['precio'], 0, ',', '.') ?></td>
        <td><span class="badge bg-primary rounded-pill"><?= (int) $v['asientos_disponibles'] ?>/<?= (int) $v['asientos_totales'] ?></span></td>
        <td><span class="badge <?= $badgeEstado[$v['estado']] ?? 'bg-secondary' ?>"><?= ucfirst($v['estado']) ?></span></td>
        <td>
          <button class="btn btn-sm btn-outline-primary py-0 me-1" title="Editar"
                  data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int) $v['id_vuelo'] ?>">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger py-0" title="Eliminar"
                  onclick="confirmarEliminar(<?= (int) $v['id_vuelo'] ?>, '<?= htmlspecialchars(addslashes($v['codigo_vuelo'])) ?>')">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>

      <!-- Modal de Edición -->
      <div class="modal fade" id="modalEditar<?= (int) $v['id_vuelo'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <form method="POST" action="vuelo_action.php" class="modal-content">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_vuelo" value="<?= (int) $v['id_vuelo'] ?>">
            <div class="modal-header" style="background-color:#0A2342;">
              <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i>Editar Vuelo</h6>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Código <span class="text-danger">*</span></label>
                  <input type="text" name="codigo_vuelo" class="form-control form-control-sm" required maxlength="10"
                         value="<?= htmlspecialchars($v['codigo_vuelo']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Origen <span class="text-danger">*</span></label>
                  <input type="text" name="origen" class="form-control form-control-sm" required maxlength="60"
                         value="<?= htmlspecialchars($v['origen']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Destino <span class="text-danger">*</span></label>
                  <input type="text" name="destino" class="form-control form-control-sm" required maxlength="60"
                         value="<?= htmlspecialchars($v['destino']) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Fecha salida <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_salida" class="form-control form-control-sm" required
                         value="<?= htmlspecialchars($v['fecha_salida']) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Hora salida <span class="text-danger">*</span></label>
                  <input type="time" name="hora_salida" class="form-control form-control-sm" required
                         value="<?= htmlspecialchars(substr($v['hora_salida'], 0, 5)) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Fecha llegada <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_llegada" class="form-control form-control-sm" required
                         value="<?= htmlspecialchars($v['fecha_llegada']) ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Hora llegada <span class="text-danger">*</span></label>
                  <input type="time" name="hora_llegada" class="form-control form-control-sm" required
                         value="<?= htmlspecialchars(substr($v['hora_llegada'], 0, 5)) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Precio (ARS) <span class="text-danger">*</span></label>
                  <input type="number" name="precio" class="form-control form-control-sm" required min="1" step="0.01"
                         value="<?= htmlspecialchars($v['precio']) ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Asientos totales <span class="text-danger">*</span></label>
                  <input type="number" name="asientos_totales" class="form-control form-control-sm" required min="1" max="500"
                         value="<?= (int) $v['asientos_totales'] ?>">
                  <div class="form-text">Ocupados actualmente: <?= (int) $v['asientos_totales'] - (int) $v['asientos_disponibles'] ?></div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Estado</label>
                  <select name="estado" class="form-select form-select-sm">
                    <option value="activo" <?= $v['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="cancelado" <?= $v['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    <option value="finalizado" <?= $v['estado'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-floppy me-1"></i>Guardar</button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPaginas > 1): ?>
<nav><ul class="pagination pagination-sm">
  <?php $qParam = $busqueda !== '' ? '&q=' . urlencode($busqueda) : ''; ?>
  <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
    <a class="page-link" href="?page=<?= max(1, $paginaActual - 1) ?><?= $qParam ?>">‹ Anterior</a>
  </li>
  <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
    <li class="page-item <?= $p === $paginaActual ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p ?><?= $qParam ?>"><?= $p ?></a>
    </li>
  <?php endfor; ?>
  <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
    <a class="page-link" href="?page=<?= min($totalPaginas, $paginaActual + 1) ?><?= $qParam ?>">Siguiente ›</a>
  </li>
</ul></nav>
<?php endif; ?>
<?php endif; ?>

<!-- Modal de Alta -->
<div class="modal fade" id="modalCrear" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="vuelo_action.php" class="modal-content">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header" style="background-color:#0A2342;">
        <h6 class="modal-title text-white"><i class="bi bi-airplane-engines me-2"></i>Nuevo Vuelo</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Código <span class="text-danger">*</span></label>
            <input type="text" name="codigo_vuelo" class="form-control form-control-sm" required maxlength="10"
                   placeholder="Ej: <?= htmlspecialchars($aerolineaCeo['codigo']) ?>-001">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Origen <span class="text-danger">*</span></label>
            <input type="text" name="origen" class="form-control form-control-sm" required maxlength="60" placeholder="Buenos Aires">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Destino <span class="text-danger">*</span></label>
            <input type="text" name="destino" class="form-control form-control-sm" required maxlength="60" placeholder="Mendoza">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Fecha salida <span class="text-danger">*</span></label>
            <input type="date" name="fecha_salida" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Hora salida <span class="text-danger">*</span></label>
            <input type="time" name="hora_salida" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Fecha llegada <span class="text-danger">*</span></label>
            <input type="date" name="fecha_llegada" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Hora llegada <span class="text-danger">*</span></label>
            <input type="time" name="hora_llegada" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Precio (ARS) <span class="text-danger">*</span></label>
            <input type="number" name="precio" class="form-control form-control-sm" required min="1" step="0.01" placeholder="85000">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Asientos totales <span class="text-danger">*</span></label>
            <input type="number" name="asientos_totales" class="form-control form-control-sm" required min="1" max="500" placeholder="45">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-floppy me-1"></i>Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de confirmación de Baja -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="vuelo_action.php" class="modal-content">
      <input type="hidden" name="accion" value="eliminar">
      <input type="hidden" name="id_vuelo" id="eliminarId" value="">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmar eliminación</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Seguro que querés eliminar el vuelo <strong id="eliminarNombre"></strong>?</p>
        <p class="text-danger small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Esta acción no se puede deshacer. No se podrá eliminar si el vuelo tiene reservas asociadas.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmarEliminar(id, codigo) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarNombre').textContent = codigo;
  new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
