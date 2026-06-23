<?php
/**
 * ============================================================================
 * CHECK-LINE — ABMC PROMOCIONES (CEO)
 * ============================================================================
 * Cada CEO gestiona solo las promociones de vuelos de SU aerolínea.
 * Solo se pueden editar/eliminar las que están en estado 'Pendiente'.
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

$pdo = getConexion();

// Aerolínea del CEO logueado
$stmtAerolinea = $pdo->prepare("SELECT id_aerolinea, nombre, codigo FROM aerolineas WHERE id_ceo = :id_ceo");
$stmtAerolinea->execute(['id_ceo' => $_SESSION['id_usuario']]);
$aerolineaCeo = $stmtAerolinea->fetch();

$tituloPagina  = 'Check-Line — Gestión de Promociones';
$seccionActiva = 'promociones';

if (!$aerolineaCeo) {
    require __DIR__ . '/../includes/header_ceo.php';
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Todavía no tenés una aerolínea asignada. Contactá al Administrador.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$idAerolinea = (int) $aerolineaCeo['id_aerolinea'];

// Vuelos activos de esta aerolínea (para los <select>)
$stmtVuelos = $pdo->prepare("
    SELECT id_vuelo, codigo_vuelo, origen, destino, fecha_salida
    FROM vuelos WHERE id_aerolinea = :id AND estado = 'activo'
    ORDER BY fecha_salida ASC
");
$stmtVuelos->execute(['id' => $idAerolinea]);
$vuelosDisponibles = $stmtVuelos->fetchAll();

// Paginación
$porPagina    = 8;
$paginaActual = max(1, (int) ($_GET['page'] ?? 1));
$offset       = ($paginaActual - 1) * $porPagina;

$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM promociones p
    INNER JOIN vuelos v ON v.id_vuelo = p.id_vuelo
    WHERE v.id_aerolinea = :id
");
$stmtCount->execute(['id' => $idAerolinea]);
$totalRegistros = (int) $stmtCount->fetchColumn();
$totalPaginas   = max(1, (int) ceil($totalRegistros / $porPagina));

// Listado filtrado por aerolínea del CEO
$stmtPromos = $pdo->prepare("
    SELECT p.id_promocion, p.id_vuelo, p.descuento_porcentaje,
           p.fecha_inicio, p.fecha_fin, p.estado, p.fecha_creacion,
           v.codigo_vuelo, v.origen, v.destino
    FROM promociones p
    INNER JOIN vuelos v ON v.id_vuelo = p.id_vuelo
    WHERE v.id_aerolinea = :id
    ORDER BY p.fecha_creacion DESC
    LIMIT :limite OFFSET :offset
");
$stmtPromos->bindValue(':id',     $idAerolinea, PDO::PARAM_INT);
$stmtPromos->bindValue(':limite', $porPagina,   PDO::PARAM_INT);
$stmtPromos->bindValue(':offset', $offset,      PDO::PARAM_INT);
$stmtPromos->execute();
$promociones = $stmtPromos->fetchAll();

require __DIR__ . '/../includes/header_ceo.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="vuelos.php">Inicio</a></li>
  <li class="breadcrumb-item active">Gestión de Promociones</li>
</ol></nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-semibold mb-0">
    <i class="bi bi-percent me-2 text-primary"></i>Gestión de Promociones
    <span class="text-muted fw-normal small">— <?= htmlspecialchars($aerolineaCeo['nombre']) ?></span>
  </h6>
  <?php if (!empty($vuelosDisponibles)): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
      <i class="bi bi-plus-circle me-1"></i>Nueva Promoción
    </button>
  <?php endif; ?>
</div>

<?php if (empty($vuelosDisponibles)): ?>
  <div class="alert alert-warning small">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No tenés vuelos activos disponibles. Creá al menos un vuelo antes de cargar promociones.
  </div>
<?php endif; ?>

<?php if (empty($promociones)): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No hay promociones cargadas todavía.
    <?= !empty($vuelosDisponibles) ? 'Hacé clic en "Nueva Promoción" para crear la primera.' : '' ?>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-striped table-hover table-bordered align-middle" style="font-size:12.5px;">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Vuelo</th>
        <th>Descuento</th>
        <th>Vigencia</th>
        <th>Estado</th>
        <th style="width:100px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($promociones as $p):
        $badgeEstado = match($p['estado']) {
            'Aprobada' => 'bg-success',
            'Denegada' => 'bg-danger',
            default    => 'bg-warning text-dark',
        };
        $editable = ($p['estado'] === 'Pendiente');
      ?>
      <tr>
        <td class="text-muted"><?= (int) $p['id_promocion'] ?></td>
        <td>
          <span class="badge bg-secondary me-1"><?= htmlspecialchars($p['codigo_vuelo']) ?></span>
          <small class="text-muted"><?= htmlspecialchars($p['origen']) ?> → <?= htmlspecialchars($p['destino']) ?></small>
        </td>
        <td><span class="badge bg-primary fs-6">-<?= htmlspecialchars($p['descuento_porcentaje']) ?>%</span></td>
        <td class="small text-muted">
          <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> → <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
        </td>
        <td><span class="badge <?= $badgeEstado ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
        <td>
          <?php if ($editable): ?>
            <button class="btn btn-sm btn-outline-primary py-0 me-1" title="Editar"
                    data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int) $p['id_promocion'] ?>">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger py-0" title="Eliminar"
                    onclick="confirmarEliminar(<?= (int) $p['id_promocion'] ?>, '<?= htmlspecialchars(addslashes($p['codigo_vuelo'])) ?>')">
              <i class="bi bi-trash"></i>
            </button>
          <?php else: ?>
            <span class="text-muted" title="No editable: ya fue auditada"><i class="bi bi-lock"></i></span>
          <?php endif; ?>
        </td>
      </tr>

      <?php if ($editable): ?>
      <!-- Modal Editar -->
      <div class="modal fade" id="modalEditar<?= (int) $p['id_promocion'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST" action="promocion_action.php" class="modal-content">
            <input type="hidden" name="accion"       value="editar">
            <input type="hidden" name="id_promocion" value="<?= (int) $p['id_promocion'] ?>">
            <div class="modal-header" style="background-color:#0A2342;">
              <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i>Editar Promoción</h6>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Vuelo <span class="text-danger">*</span></label>
                <select name="id_vuelo" class="form-select form-select-sm" required>
                  <?php foreach ($vuelosDisponibles as $vd): ?>
                    <option value="<?= (int) $vd['id_vuelo'] ?>"
                      <?= ((int) $vd['id_vuelo'] === (int) $p['id_vuelo']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($vd['codigo_vuelo'] . ' — ' . $vd['origen'] . ' → ' . $vd['destino']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Descuento (%) <span class="text-danger">*</span></label>
                <input type="number" name="descuento_porcentaje" class="form-control form-control-sm"
                       required min="1" max="100" step="0.01"
                       value="<?= htmlspecialchars($p['descuento_porcentaje']) ?>">
              </div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Fecha inicio <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                         required value="<?= htmlspecialchars($p['fecha_inicio']) ?>">
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Fecha fin <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_fin" class="form-control form-control-sm"
                         required value="<?= htmlspecialchars($p['fecha_fin']) ?>">
                </div>
              </div>
              <div class="form-text mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Al guardar, la promoción volverá a estado <strong>Pendiente</strong> para re-auditoría.
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-floppy me-1"></i>Guardar</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPaginas > 1): ?>
<nav><ul class="pagination pagination-sm">
  <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
    <a class="page-link" href="?page=<?= max(1, $paginaActual - 1) ?>">‹ Anterior</a>
  </li>
  <?php for ($p2 = 1; $p2 <= $totalPaginas; $p2++): ?>
    <li class="page-item <?= $p2 === $paginaActual ? 'active' : '' ?>">
      <a class="page-link" href="?page=<?= $p2 ?>"><?= $p2 ?></a>
    </li>
  <?php endfor; ?>
  <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
    <a class="page-link" href="?page=<?= min($totalPaginas, $paginaActual + 1) ?>">Siguiente ›</a>
  </li>
</ul></nav>
<?php endif; ?>
<?php endif; ?>

<!-- Modal Alta -->
<div class="modal fade" id="modalCrear" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="promocion_action.php" class="modal-content">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header" style="background-color:#0A2342;">
        <h6 class="modal-title text-white"><i class="bi bi-percent me-2"></i>Nueva Promoción</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info small py-2 mb-3">
          <i class="bi bi-clock-history me-1"></i>
          La promoción quedará en estado <strong>Pendiente</strong> hasta que el Administrador la apruebe.
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Vuelo <span class="text-danger">*</span></label>
          <select name="id_vuelo" class="form-select form-select-sm" required>
            <option value="" disabled selected>Seleccionar vuelo...</option>
            <?php foreach ($vuelosDisponibles as $vd): ?>
              <option value="<?= (int) $vd['id_vuelo'] ?>">
                <?= htmlspecialchars($vd['codigo_vuelo'] . ' — ' . $vd['origen'] . ' → ' . $vd['destino'] . ' (' . date('d/m/Y', strtotime($vd['fecha_salida'])) . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Descuento (%) <span class="text-danger">*</span></label>
          <input type="number" name="descuento_porcentaje" class="form-control form-control-sm"
                 required min="1" max="100" step="0.01" placeholder="Ej: 15">
        </div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Fecha inicio <span class="text-danger">*</span></label>
            <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
          </div>
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Fecha fin <span class="text-danger">*</span></label>
            <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Enviar a auditoría</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Baja -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="promocion_action.php" class="modal-content">
      <input type="hidden" name="accion"       value="eliminar">
      <input type="hidden" name="id_promocion" id="eliminarId" value="">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmar eliminación</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Seguro que querés eliminar la promoción del vuelo <strong id="eliminarCodigo"></strong>?</p>
        <p class="text-danger small mb-0"><i class="bi bi-info-circle me-1"></i>Esta acción no se puede deshacer.</p>
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
  document.getElementById('eliminarCodigo').textContent = codigo;
  new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
