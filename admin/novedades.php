<?php
/**
 * ============================================================================
 * CHECK-LINE — ABMC NOVEDADES (ADMINISTRADOR)
 * ============================================================================
 * Listado + búsqueda + paginación + Alta (modal) + Edición (modal) + Baja.
 * Patrón Post-Redirect-Get.
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

$pdo = getConexion();

$busqueda = trim($_GET['q'] ?? '');
$param    = '%' . $busqueda . '%';

$porPagina    = 6;
$paginaActual = max(1, (int) ($_GET['page'] ?? 1));
$offset       = ($paginaActual - 1) * $porPagina;

$stmtCount = $pdo->prepare("
    SELECT COUNT(*) FROM novedades
    WHERE titulo LIKE :busq1 OR contenido LIKE :busq2
");
$stmtCount->execute(['busq1' => $param, 'busq2' => $param]);
$totalRegistros = (int) $stmtCount->fetchColumn();
$totalPaginas   = max(1, (int) ceil($totalRegistros / $porPagina));

$stmt = $pdo->prepare("
    SELECT n.id_novedad, n.titulo, n.contenido, n.fecha_inicio, n.fecha_fin,
           u.nombre AS admin_nombre, u.apellido AS admin_apellido
    FROM novedades n
    LEFT JOIN usuarios u ON u.id_usuario = n.id_admin
    WHERE n.titulo LIKE :busq1 OR n.contenido LIKE :busq2
    ORDER BY n.fecha_inicio DESC
    LIMIT :limite OFFSET :offset
");
$stmt->bindValue(':busq1',  $param,     PDO::PARAM_STR);
$stmt->bindValue(':busq2',  $param,     PDO::PARAM_STR);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
$stmt->execute();
$novedades = $stmt->fetchAll();

$hoy = date('Y-m-d');

$tituloPagina  = 'Check-Line — Gestión de Novedades';
$seccionActiva = 'novedades';
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="aerolineas.php">Inicio</a></li>
  <li class="breadcrumb-item active">Gestión de Novedades</li>
</ol></nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-semibold mb-0"><i class="bi bi-megaphone me-2 text-primary"></i>Gestión de Novedades</h6>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
    <i class="bi bi-plus-circle me-1"></i>Nueva Novedad
  </button>
</div>

<form method="GET" class="row mb-3 g-2">
  <div class="col-md-5">
    <div class="input-group input-group-sm">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" name="q" class="form-control" placeholder="Buscar por título o contenido..."
             value="<?= htmlspecialchars($busqueda) ?>">
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
      <?php if ($busqueda !== ''): ?>
        <a href="novedades.php" class="btn btn-outline-danger">Limpiar</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if (empty($novedades)): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <?= $busqueda !== ''
        ? 'No se encontraron novedades para "' . htmlspecialchars($busqueda) . '".'
        : 'No hay novedades cargadas. Hacé clic en "Nueva Novedad" para crear la primera.' ?>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-striped table-hover table-bordered align-middle" style="font-size:13px;">
    <thead class="table-dark">
      <tr>
        <th style="width:40px;">#</th>
        <th>Título</th>
        <th>Vigencia</th>
        <th>Estado</th>
        <th>Publicado por</th>
        <th style="width:90px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($novedades as $n):
        if ($hoy < $n['fecha_inicio']) {
            $estadoBadge = '<span class="badge bg-secondary">Programada</span>';
        } elseif ($hoy > $n['fecha_fin']) {
            $estadoBadge = '<span class="badge bg-danger">Vencida</span>';
        } else {
            $estadoBadge = '<span class="badge bg-success">Activa</span>';
        }
      ?>
      <tr>
        <td class="text-muted"><?= (int) $n['id_novedad'] ?></td>
        <td>
          <strong><?= htmlspecialchars($n['titulo']) ?></strong>
          <div class="text-muted small text-truncate" style="max-width:280px;">
            <?= htmlspecialchars($n['contenido']) ?>
          </div>
        </td>
        <td class="small">
          <i class="bi bi-calendar-range me-1 text-muted"></i>
          <?= date('d/m/Y', strtotime($n['fecha_inicio'])) ?>
          → <?= date('d/m/Y', strtotime($n['fecha_fin'])) ?>
        </td>
        <td><?= $estadoBadge ?></td>
        <td class="text-muted small"><?= htmlspecialchars(($n['admin_nombre'] ?? 'Admin eliminado') . ' ' . ($n['admin_apellido'] ?? '')) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-primary py-0 me-1" title="Editar"
                  data-bs-toggle="modal"
                  data-bs-target="#modalEditar<?= (int) $n['id_novedad'] ?>">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger py-0" title="Eliminar"
                  onclick="confirmarEliminar(<?= (int) $n['id_novedad'] ?>, '<?= htmlspecialchars(addslashes($n['titulo'])) ?>')">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>

      <!-- Modal Editar -->
      <div class="modal fade" id="modalEditar<?= (int) $n['id_novedad'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <form method="POST" action="novedad_action.php" class="modal-content">
            <input type="hidden" name="accion"     value="editar">
            <input type="hidden" name="id_novedad" value="<?= (int) $n['id_novedad'] ?>">
            <div class="modal-header" style="background-color:#0A2342;">
              <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i>Editar Novedad</h6>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Título <span class="text-danger">*</span></label>
                <input type="text" name="titulo" class="form-control form-control-sm"
                       required maxlength="150"
                       value="<?= htmlspecialchars($n['titulo']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Contenido <span class="text-danger">*</span></label>
                <textarea name="contenido" class="form-control form-control-sm"
                          rows="4" required><?= htmlspecialchars($n['contenido']) ?></textarea>
              </div>
              <div class="row g-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Fecha de inicio <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_inicio" class="form-control form-control-sm"
                         required value="<?= htmlspecialchars($n['fecha_inicio']) ?>">
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Fecha de vencimiento <span class="text-danger">*</span></label>
                  <input type="date" name="fecha_fin" class="form-control form-control-sm"
                         required value="<?= htmlspecialchars($n['fecha_fin']) ?>">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-floppy me-1"></i>Guardar cambios
              </button>
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

<!-- Modal Alta -->
<div class="modal fade" id="modalCrear" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="novedad_action.php" class="modal-content">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header" style="background-color:#0A2342;">
        <h6 class="modal-title text-white"><i class="bi bi-megaphone me-2"></i>Nueva Novedad</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Título <span class="text-danger">*</span></label>
          <input type="text" name="titulo" class="form-control form-control-sm"
                 required maxlength="150" placeholder="Ej: Demoras por temporal en el aeropuerto">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Contenido <span class="text-danger">*</span></label>
          <textarea name="contenido" class="form-control form-control-sm"
                    rows="4" required placeholder="Detalle el anuncio que verán los pasajeros..."></textarea>
        </div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Fecha de inicio <span class="text-danger">*</span></label>
            <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
          </div>
          <div class="col-sm-6">
            <label class="form-label small fw-semibold">Fecha de vencimiento <span class="text-danger">*</span></label>
            <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-floppy me-1"></i>Publicar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Baja -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="novedad_action.php" class="modal-content">
      <input type="hidden" name="accion"     value="eliminar">
      <input type="hidden" name="id_novedad" id="eliminarId" value="">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmar eliminación</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Seguro que querés eliminar la novedad <strong id="eliminarTitulo"></strong>?</p>
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
function confirmarEliminar(id, titulo) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarTitulo').textContent = titulo;
  new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
