<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - GESTIÓN DE PROMOCIONES (CEO)
 * ============================================================================
 */
// 1. FORZAMOS LA VISIBILIDAD DE ERRORES (Rompe la pantalla en blanco)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Control de acceso jerárquico
requerirRol('ceo');

$mensaje = obtenerYLimpiarMensaje();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vuelo  = $_POST['id_vuelo'] ?? null;
    $descuento = $_POST['descuento'] ?? null;
    $detalle   = trim($_POST['detalle'] ?? '');

    if (!$id_vuelo || !$descuento) {
        setMensaje('danger', 'El ID de vuelo y el descuento son obligatorios.');
    } else {
        try {
            $pdo = getConexion();
            
            // 2. AJUSTE ESTRUCTURAL: Mapeo exacto según el diagrama de tu BD
            // Asignamos fechas automáticas para cumplir con la restricción de la tabla
            $sql = "INSERT INTO promociones (
                        id_vuelo, 
                        descuento_porcentaje, 
                        fecha_inicio, 
                        fecha_fin, 
                        estado, 
                        destacada, 
                        id_creador, 
                        fecha_creacion
                    ) VALUES (
                        :vuelo, 
                        :descuento, 
                        CURDATE(), 
                        DATE_ADD(CURDATE(), INTERVAL 15 DAY), 
                        'Pendiente', 
                        0, 
                        :creador, 
                        NOW()
                    )";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'vuelo'     => $id_vuelo,
                'descuento' => $descuento,
                'creador'   => $_SESSION['id_usuario']
            ]);
            
            setMensaje('success', 'Promoción enviada a auditoría. Aguarde la aprobación del Administrador.');
        } catch (PDOException $e) {
            error_log('Error CEO Promociones: ' . $e->getMessage());
            setMensaje('danger', 'Falla de Integridad: ' . $e->getMessage());
        }
    }
    header('Location: promociones.php');
    exit;
}

// Extracción del historial
try {
    $pdo = getConexion();
    $stmt = $pdo->query("SELECT * FROM promociones ORDER BY id_promocion DESC");
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $promociones = [];
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Check-Line — Panel CEO</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<main class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold" style="color:#0A2342;">Gestión de Promociones</h1>
    <a href="../index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver al Inicio</a>
  </div>

  <?php if ($mensaje): ?>
    <div class="alert alert-<?= $mensaje['tipo'] ?> shadow-sm">
      <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($mensaje['texto']) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-4 pb-0">
          <h2 class="h6 fw-bold text-muted">Nueva Propuesta</h2>
        </div>
        <div class="card-body">
          <form method="POST" action="promociones.php">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">ID de Vuelo Registrado</label>
              <input type="number" name="id_vuelo" class="form-control form-control-sm" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">Descuento (%)</label>
              <input type="number" name="descuento" class="form-control form-control-sm" min="1" max="100" required>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-semibold text-muted">Detalle Comercial (Opcional)</label>
              <input type="text" name="detalle" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background-color: #0A2342; border-color: #0A2342;">
              Solicitar Aprobación
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-0">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light text-muted small">
              <tr>
                <th class="px-4">Vuelo</th>
                <th>Descuento</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($promociones as $p): ?>
              <tr>
                <td class="px-4 fw-semibold">#<?= htmlspecialchars($p['id_vuelo']) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['descuento_porcentaje'] ?? '0') ?>%</span></td>
                <td>
                  <?php if ($p['estado'] === 'Aprobada'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Aprobada</span>
                  <?php elseif ($p['estado'] === 'Denegada'): ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Denegada</span>
                  <?php else: ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Pendiente</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>