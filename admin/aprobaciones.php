<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - AUDITORÍA DE PROMOCIONES (ADMIN)
 * ============================================================================
 */
// Forzamos visibilidad de errores para auditoría
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Control estricto de auditoría
requerirRol('admin');

$mensaje = obtenerYLimpiarMensaje();

// Procesamiento de Resolución (Aprobar o Denegar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_promocion = $_POST['id_promocion'] ?? null;
    $accion       = $_POST['accion'] ?? null;
    $id_admin     = $_SESSION['id_usuario']; // Firma obligatoria del auditor

    if ($id_promocion && in_array($accion, ['Aprobada', 'Denegada'])) {
        try {
            $pdo = getConexion();
            // Actualización con rastro de auditoría exigido por la BD
            $stmt = $pdo->prepare("
                UPDATE promociones 
                SET estado = :estado, 
                    id_aprobador = :admin, 
                    fecha_resolucion = NOW() 
                WHERE id_promocion = :id
            ");
            $stmt->execute([
                'estado' => $accion,
                'admin'  => $id_admin,
                'id'     => $id_promocion
            ]);
            setMensaje('success', "Resolución ejecutada: Promoción $accion.");
        } catch (PDOException $e) {
            error_log('Error Admin Aprobaciones: ' . $e->getMessage());
            setMensaje('danger', 'Falla de Integridad: ' . $e->getMessage());
        }
    }
    header('Location: aprobaciones.php');
    exit;
}

// Extracción exclusiva de elementos no aprobados
try {
    $pdo = getConexion();
    $stmt = $pdo->query("SELECT * FROM promociones WHERE estado = 'Pendiente' ORDER BY id_promocion ASC");
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendientes = [];
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Check-Line — Auditoría Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<main class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold" style="color:#0A2342;">Auditoría de Promociones</h1>
    <a href="../index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver al Inicio</a>
  </div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> shadow-sm">
      <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($mensaje['texto']) ?>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4">
      <h2 class="h6 fw-bold text-muted mb-0">Cola de Aprobación Pendiente</h2>
    </div>
    <div class="card-body p-0">
      <?php if (empty($pendientes)): ?>
        <div class="p-5 text-center">
          <i class="bi bi-check2-circle display-4 text-success opacity-50 mb-3 d-block"></i>
          <p class="text-muted mb-0">No hay promociones pendientes de auditoría.</p>
        </div>
      <?php else: ?>
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small">
            <tr>
              <th class="px-4">Vuelo</th>
              <th>Descuento</th>
              <th class="text-end px-4">Resolución</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendientes as $p): ?>
            <tr>
              <td class="px-4 fw-bold text-dark">#<?= htmlspecialchars($p['id_vuelo']) ?></td>
              <td><span class="badge bg-primary fs-6"><?= htmlspecialchars($p['descuento_porcentaje']) ?>%</span></td>
              <td class="text-end px-4">
                <form method="POST" action="aprobaciones.php" class="d-inline-flex gap-2">
                  <input type="hidden" name="id_promocion" value="<?= $p['id_promocion'] ?>">
                  
                  <button type="submit" name="accion" value="Aprobada" class="btn btn-sm btn-success fw-bold shadow-sm" title="Aprobar">
                    <i class="bi bi-check-lg"></i> Aprobar
                  </button>
                  
                  <button type="submit" name="accion" value="Denegada" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" title="Denegar">
                    <i class="bi bi-x-lg"></i> Denegar
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>