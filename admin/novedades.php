<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - ABMC DE NOVEDADES (ADMINISTRADOR)
 * ============================================================================
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Control estricto de jerarquía
requerirRol('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo       = trim($_POST['titulo'] ?? '');
    $contenido    = trim($_POST['contenido'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin    = $_POST['fecha_fin'] ?? '';
    
    // El ID del admin logueado lo extraemos de la sesión, no del formulario
    $id_admin     = $_SESSION['id_usuario'];

    if ($titulo === '' || $contenido === '' || $fecha_inicio === '' || $fecha_fin === '') {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($fecha_inicio > $fecha_fin) {
        $error = 'La fecha de inicio no puede ser posterior a la fecha de finalización.';
    } else {
        try {
            $pdo = getConexion();
            $sql = "INSERT INTO novedades (titulo, contenido, fecha_inicio, fecha_fin, id_admin) 
                    VALUES (:titulo, :contenido, :inicio, :fin, :admin)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titulo'    => $titulo,
                'contenido' => $contenido,
                'inicio'    => $fecha_inicio,
                'fin'       => $fecha_fin,
                'admin'     => $id_admin
            ]);
            
            $success = 'Novedad publicada exitosamente en el sistema.';
        } catch (PDOException $e) {
            error_log('Error en ABMC Novedades: ' . $e->getMessage());
            $error = 'Falla interna de base de datos. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Gestión de Novedades</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<main class="container py-5" role="main">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold" style="color:#0A2342;">Gestión Operativa: Novedades</h1>
        <a href="../index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver al Inicio</a>
      </div>

      <section class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
          <h2 class="h6 fw-bold text-muted mb-0">Publicar Nuevo Aviso</h2>
        </div>
        <div class="card-body p-4">
          
          <?php if ($error): ?>
            <div class="alert alert-danger py-2 small fw-semibold shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success py-2 small fw-semibold shadow-sm"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <form method="POST" action="novedades.php">
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">Título del Aviso</label>
              <input type="text" name="titulo" class="form-control form-control-sm" required placeholder="Ej: Demoras por temporal">
            </div>
            
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">Contenido Institucional</label>
              <textarea name="contenido" class="form-control form-control-sm" rows="3" required placeholder="Detalle la información que visualizará el pasajero..."></textarea>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-muted">Fecha de Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-muted">Fecha de Finalización (Límite)</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background-color: #0A2342; border-color: #0A2342;">
              <i class="bi bi-megaphone-fill me-2"></i>Emitir Comunicado
            </button>
          </form>

        </div>
      </section>

    </div>
  </div>
</main>
</body>
</html>