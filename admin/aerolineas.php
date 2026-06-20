<?php
/**
 * CHECK-LINE — ABMC Aerolíneas (Admin)
 * Listado + Búsqueda + Paginación + Alta/Modificación (modal) + Baja (confirmación)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

$pdo = getConexion();

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

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM aerolineas WHERE nombre LIKE :busq1 OR codigo LIKE :busq2");
$stmtCount->execute(['busq1' => $param, 'busq2' => $param]);
$totalRegistros = (int) $stmtCount->fetchColumn();
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

// ---------------------------------------------------------------
// Listado (JOIN con CEO + conteo de vuelos activos)
// ---------------------------------------------------------------
$sql = "
    SELECT a.id_aerolinea, a.nombre, a.codigo, a.pais, a.id_ceo,
           u.nombre AS ceo_nombre, u.apellido AS ceo_apellido, u.email AS ceo_email,
           (SELECT COUNT(*) FROM vuelos v
              WHERE v.id_aerolinea = a.id_aerolinea AND v.estado = 'activo') AS vuelos_activos
    FROM aerolineas a
    INNER JOIN usuarios u ON u.id_usuario = a.id_ceo
    WHERE a.nombre LIKE :busq1 OR a.codigo LIKE :busq2
    ORDER BY a.nombre ASC
    LIMIT :limite OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':busq1', $param, PDO::PARAM_STR);
$stmt->bindValue(':busq2', $param, PDO::PARAM_STR);
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$aerolineas = $stmt->fetchAll();

// ---------------------------------------------------------------
// Usuarios con rol CEO (para el <select> de los modales)
// ---------------------------------------------------------------
$ceosDisponibles = $pdo->query("
    SELECT u.id_usuario, u.nombre, u.apellido, u.email
    FROM usuarios u
    INNER JOIN roles r ON r.id_rol = u.id_rol
    WHERE r.nombre_rol = 'ceo'
    ORDER BY u.nombre
")->fetchAll();

$tituloPagina  = 'Check-Line — Gestión de Aerolíneas';
$seccionActiva = 'aerolineas';
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2">
  <li class="breadcrumb-item"><a href="/admin/index.php">Inicio</a></li>
  <li class="breadcrumb-item active">Gestión de Aerolíneas</li>
</ol></nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-semibold mb-0"><i class="bi bi-building me-2 text-primary"></i>Gestión de Aerolíneas</h6>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrear">
    <i class="bi bi-plus-circle me-1"></i>Nueva Aerolínea
  </button>
</div>

<form method="GET" class="row mb-3 g-2">
  <div class="col-md-5">
    <div class="input-group input-group-sm">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o código..."
             value="<?= htmlspecialchars($busqueda) ?>">
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
      <?php if ($busqueda !== ''): ?>
        <a href="/admin/aerolineas.php" class="btn btn-outline-danger">Limpiar</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if (empty($aerolineas)): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <?php if ($busqueda !== ''): ?>
      No se encontraron aerolíneas para "<?= htmlspecialchars($busqueda) ?>".
    <?php else: ?>
      Todavía no hay aerolíneas cargadas. Hacé clic en "Nueva Aerolínea" para crear la primera.
    <?php endif; ?>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-striped table-hover table-bordered align-middle" style="font-size:13px;">
    <thead class="table-dark">
      <tr>
        <th style="width:50px;">#</th>
        <th>Nombre</th>
        <th>Código</th>
        <th>País</th>
        <th>Vuelos activos</th>
        <th>CEO asignado</th>
        <th style="width:100px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($aerolineas as $a): ?>
      <tr>
        <td class="text-muted"><?= (int) $a['id_aerolinea'] ?></td>
        <td><strong><?= htmlspecialchars($a['nombre']) ?></strong></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['codigo']) ?></span></td>
        <td><i class="bi bi-flag me-1"></i><?= htmlspecialchars($a['pais']) ?></td>
        <td><span class="badge bg-primary rounded-pill"><?= (int) $a['vuelos_activos'] ?></span></td>
        <td class="text-muted"><?= htmlspecialchars($a['ceo_email']) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-primary py-0 me-1" title="Editar"
                  data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int) $a['id_aerolinea'] ?>">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger py-0" title="Eliminar"
                  onclick="confirmarEliminar(<?= (int) $a['id_aerolinea'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>')">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>

      <!-- Modal de Edición (prellenado server-side) -->
      <div class="modal fade" id="modalEditar<?= (int) $a['id_aerolinea'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST" action="/admin/aerolinea_action.php" class="modal-content">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_aerolinea" value="<?= (int) $a['id_aerolinea'] ?>">
            <div class="modal-header" style="background-color:#0A2342;">
              <h6 class="modal-title text-white"><i class="bi bi-pencil-square me-2"></i>Editar Aerolínea</h6>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control form-control-sm" required maxlength="100"
                       value="<?= htmlspecialchars($a['nombre']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">Código <span class="text-danger">*</span></label>
                <input type="text" name="codigo" class="form-control form-control-sm" required maxlength="3"
                       style="text-transform:uppercase;" value="<?= htmlspecialchars($a['codigo']) ?>">
                <div class="form-text">Máximo 3 caracteres, único en el sistema.</div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">País <span class="text-danger">*</span></label>
                <input type="text" name="pais" class="form-control form-control-sm" required maxlength="60"
                       value="<?= htmlspecialchars($a['pais']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-semibold">CEO asignado <span class="text-danger">*</span></label>
                <select name="id_ceo" class="form-select form-select-sm" required>
                  <?php foreach ($ceosDisponibles as $c): ?>
                    <option value="<?= (int) $c['id_usuario'] ?>"
                      <?= ((int) $c['id_usuario'] === (int) $a['id_ceo']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' — ' . $c['email']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
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
  <div class="modal-dialog">
    <form method="POST" action="/admin/aerolinea_action.php" class="modal-content">
      <input type="hidden" name="accion" value="crear">
      <div class="modal-header" style="background-color:#0A2342;">
        <h6 class="modal-title text-white"><i class="bi bi-building-add me-2"></i>Nueva Aerolínea</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (empty($ceosDisponibles)): ?>
          <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>
            No hay usuarios con rol "CEO" registrados todavía. El CEO debe registrarse en el sistema antes de poder asignarle una aerolínea.
          </div>
        <?php else: ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control form-control-sm" required maxlength="100"
                   placeholder="Nombre de la aerolínea">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Código <span class="text-danger">*</span></label>
            <input type="text" name="codigo" class="form-control form-control-sm" required maxlength="3"
                   style="text-transform:uppercase;" placeholder="Ej: AT">
            <div class="form-text">Máximo 3 caracteres, único en el sistema.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">País <span class="text-danger">*</span></label>
            <input type="text" name="pais" class="form-control form-control-sm" required maxlength="60"
                   placeholder="Ej: Argentina">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">CEO asignado <span class="text-danger">*</span></label>
            <select name="id_ceo" class="form-select form-select-sm" required>
              <option value="" disabled selected>Seleccionar CEO...</option>
              <?php foreach ($ceosDisponibles as $c): ?>
                <option value="<?= (int) $c['id_usuario'] ?>">
                  <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' — ' . $c['email']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm" <?= empty($ceosDisponibles) ? 'disabled' : '' ?>>
          <i class="bi bi-floppy me-1"></i>Guardar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de confirmación de Baja (compartido) -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="/admin/aerolinea_action.php" class="modal-content">
      <input type="hidden" name="accion" value="eliminar">
      <input type="hidden" name="id_aerolinea" id="eliminarId" value="">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirmar eliminación</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">¿Seguro que querés eliminar la aerolínea <strong id="eliminarNombre"></strong>?</p>
        <p class="text-danger small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Esta acción no se puede deshacer. No se podrá eliminar si la aerolínea tiene vuelos asociados.
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
function confirmarEliminar(id, nombre) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarNombre').textContent = nombre;
  new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
