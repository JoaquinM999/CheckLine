<?php
/**
 * ============================================================================
 * CHECK-LINE — MI PERFIL (Pasajero)
 * ============================================================================
 * Archivo: perfil.php (en la raíz del proyecto)
 * Permite al pasajero logueado:
 *   - Ver y editar sus datos personales (nombre, apellido, teléfono)
 *   - Cambiar su contraseña (requiere ingresar la actual para confirmar)
 * Dos formularios independientes en la misma página (tabs Bootstrap).
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requerirRol('pasajero');

$id = $_SESSION['id_usuario'];
$pdo = getConexion();

// ── Cargar datos actuales del usuario ─────────────────────────────────────
$stmtUsuario = $pdo->prepare("
    SELECT nombre, apellido, email, telefono
    FROM usuarios
    WHERE id_usuario = :id
");
$stmtUsuario->execute(['id' => $id]);
$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    // Sesión huérfana (el usuario fue eliminado de la BD)
    session_destroy();
    header('Location: login.php');
    exit;
}

$errorDatos     = '';
$errorPassword  = '';
$tabActiva      = 'datos'; // qué pestaña mostrar tras el POST

// ═══════════════════════════════════════════════════════════════════════════
// POST: actualizar datos personales
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'datos') {
    $tabActiva = 'datos';

    $nombre   = trim($_POST['nombre']   ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '' || $apellido === '') {
        $errorDatos = 'El nombre y apellido son obligatorios.';
    } elseif ($telefono !== '' && !preg_match('/^[\d\s\+\-\(\)]{6,20}$/', $telefono)) {
        $errorDatos = 'El formato del teléfono no es válido.';
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE usuarios
            SET nombre = :nombre, apellido = :apellido, telefono = :telefono
            WHERE id_usuario = :id
        ");
        $stmtUpdate->execute([
            'nombre'   => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono !== '' ? $telefono : null,
            'id'       => $id,
        ]);

        // Actualizar el nombre en sesión para que el navbar lo refleje de inmediato
        $_SESSION['nombre'] = $nombre . ' ' . $apellido;

        // Recargar datos del formulario con los valores nuevos
        $usuario['nombre']   = $nombre;
        $usuario['apellido'] = $apellido;
        $usuario['telefono'] = $telefono;

        setMensaje('Tus datos fueron actualizados correctamente.', 'success');
        header('Location: perfil.php');
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// POST: cambiar contraseña
// ═══════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'password') {
    $tabActiva      = 'password';

    $actual         = $_POST['password_actual']  ?? '';
    $nueva          = $_POST['password_nueva']   ?? '';
    $confirmar      = $_POST['password_confirmar'] ?? '';

    if ($actual === '' || $nueva === '' || $confirmar === '') {
        $errorPassword = 'Todos los campos de contraseña son obligatorios.';
    } elseif (strlen($nueva) < 8) {
        $errorPassword = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $errorPassword = 'La nueva contraseña y su confirmación no coinciden.';
    } else {
        // Verificar que la contraseña actual sea correcta
        $stmtHash = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = :id");
        $stmtHash->execute(['id' => $id]);
        $hashActual = $stmtHash->fetchColumn();

        if (!password_verify($actual, $hashActual)) {
            $errorPassword = 'La contraseña actual ingresada es incorrecta.';
        } elseif (password_verify($nueva, $hashActual)) {
            $errorPassword = 'La nueva contraseña no puede ser igual a la actual.';
        } else {
            $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
            $pdo->prepare("
                UPDATE usuarios SET password_hash = :hash WHERE id_usuario = :id
            ")->execute(['hash' => $nuevoHash, 'id' => $id]);

            setMensaje('Tu contraseña fue actualizada correctamente.', 'success');
            header('Location: perfil.php');
            exit;
        }
    }
}

// ── Leer mensaje de sesión (PRG) ──────────────────────────────────────────
$msg = obtenerYLimpiarMensaje();
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Mi Perfil</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body style="background-color:#f0f4f8;">

<!-- ── Navbar ──────────────────────────────────────────────────────────── -->
<header role="banner">
  <nav class="navbar navbar-expand-lg navbar-dark px-3 py-2"
       style="background-color:#0A2342;"
       aria-label="Navegación principal">
    <a class="navbar-brand fw-bold" href="index.php" aria-label="Volver a la portada">
      <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
    </a>
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navPerfil"
            aria-controls="navPerfil" aria-expanded="false" aria-label="Alternar navegación">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPerfil">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="vuelos.php">
            <i class="bi bi-search me-1" aria-hidden="true"></i>Buscar Vuelos
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="mis_reservas.php">
            <i class="bi bi-ticket-perforated me-1" aria-hidden="true"></i>Mis Reservas
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="novedades.php">
            <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Novedades
          </a>
        </li>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <a href="perfil.php" class="btn btn-warning btn-sm fw-bold"
           aria-label="Mi perfil" aria-current="page">
          <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
          <?= htmlspecialchars($_SESSION['nombre']) ?>
        </a>
        <a href="logout.php" class="btn btn-outline-light btn-sm"
           aria-label="Cerrar sesión">Salir</a>
      </div>
    </div>
  </nav>
</header>

<main class="container py-5" role="main" id="contenido-principal">

  <!-- Breadcrumb -->
  <nav aria-label="Ruta de navegación" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
      <li class="breadcrumb-item active" aria-current="page">Mi Perfil</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-7">

      <!-- Título -->
      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:56px; height:56px; background-color:#0A2342;">
          <i class="bi bi-person-fill fs-4 text-white" aria-hidden="true"></i>
        </div>
        <div>
          <h1 class="h4 fw-bold mb-0" style="color:#0A2342;">
            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
          </h1>
          <p class="text-muted small mb-0">
            <i class="bi bi-envelope me-1" aria-hidden="true"></i>
            <?= htmlspecialchars($usuario['email']) ?>
          </p>
        </div>
      </div>

      <!-- Alerta PRG -->
      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['tipo'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
             role="alert" aria-live="polite">
          <i class="bi bi-<?= $msg['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"
             aria-hidden="true"></i>
          <?= htmlspecialchars($msg['texto']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"
                  aria-label="Cerrar notificación"></button>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-0" id="tabsPerfil" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $tabActiva === 'datos' ? 'active' : '' ?>"
                  id="tab-datos" data-bs-toggle="tab" data-bs-target="#panel-datos"
                  type="button" role="tab"
                  aria-controls="panel-datos"
                  aria-selected="<?= $tabActiva === 'datos' ? 'true' : 'false' ?>">
            <i class="bi bi-person me-1" aria-hidden="true"></i>Datos personales
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $tabActiva === 'password' ? 'active' : '' ?>"
                  id="tab-password" data-bs-toggle="tab" data-bs-target="#panel-password"
                  type="button" role="tab"
                  aria-controls="panel-password"
                  aria-selected="<?= $tabActiva === 'password' ? 'true' : 'false' ?>">
            <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Contraseña
          </button>
        </li>
      </ul>

      <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm p-4">

        <!-- ── Panel: Datos personales ──────────────────────────────── -->
        <div class="tab-pane fade <?= $tabActiva === 'datos' ? 'show active' : '' ?>"
             id="panel-datos" role="tabpanel" aria-labelledby="tab-datos">

          <?php if ($errorDatos !== ''): ?>
            <div class="alert alert-danger py-2 small" role="alert" aria-live="assertive">
              <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
              <?= htmlspecialchars($errorDatos) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="perfil.php" novalidate>
            <input type="hidden" name="accion" value="datos">

            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label for="nombre" class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="nombre" name="nombre"
                       class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($usuario['nombre']) ?>">
              </div>
              <div class="col-sm-6">
                <label for="apellido" class="form-label small fw-semibold">Apellido <span class="text-danger">*</span></label>
                <input type="text" id="apellido" name="apellido"
                       class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($usuario['apellido']) ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="email_display" class="form-label small fw-semibold">
                Correo electrónico
                <span class="badge bg-secondary ms-1" style="font-size:10px;">No editable</span>
              </label>
              <input type="email" id="email_display"
                     class="form-control form-control-sm bg-light"
                     value="<?= htmlspecialchars($usuario['email']) ?>"
                     disabled aria-readonly="true">
              <div class="form-text">El email no puede modificarse por razones de seguridad.</div>
            </div>

            <div class="mb-4">
              <label for="telefono" class="form-label small fw-semibold">Teléfono <span class="text-muted fw-normal">(opcional)</span></label>
              <input type="tel" id="telefono" name="telefono"
                     class="form-control form-control-sm"
                     placeholder="+54 341 000-0000"
                     value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary btn-sm fw-bold px-4"
                      style="background-color:#0A2342; border-color:#0A2342;">
                <i class="bi bi-floppy me-1" aria-hidden="true"></i>Guardar cambios
              </button>
            </div>
          </form>
        </div>

        <!-- ── Panel: Cambiar contraseña ────────────────────────────── -->
        <div class="tab-pane fade <?= $tabActiva === 'password' ? 'show active' : '' ?>"
             id="panel-password" role="tabpanel" aria-labelledby="tab-password">

          <?php if ($errorPassword !== ''): ?>
            <div class="alert alert-danger py-2 small" role="alert" aria-live="assertive">
              <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
              <?= htmlspecialchars($errorPassword) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="perfil.php" novalidate>
            <input type="hidden" name="accion" value="password">

            <div class="mb-3">
              <label for="password_actual" class="form-label small fw-semibold">
                Contraseña actual <span class="text-danger">*</span>
              </label>
              <input type="password" id="password_actual" name="password_actual"
                     class="form-control form-control-sm" required
                     autocomplete="current-password">
            </div>

            <div class="mb-3">
              <label for="password_nueva" class="form-label small fw-semibold">
                Nueva contraseña <span class="text-danger">*</span>
              </label>
              <input type="password" id="password_nueva" name="password_nueva"
                     class="form-control form-control-sm" required minlength="8"
                     autocomplete="new-password" aria-describedby="passNuevaHelp">
              <div id="passNuevaHelp" class="form-text">Mínimo 8 caracteres.</div>
            </div>

            <div class="mb-4">
              <label for="password_confirmar" class="form-label small fw-semibold">
                Confirmar nueva contraseña <span class="text-danger">*</span>
              </label>
              <input type="password" id="password_confirmar" name="password_confirmar"
                     class="form-control form-control-sm" required
                     autocomplete="new-password">
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-warning btn-sm fw-bold px-4">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Cambiar contraseña
              </button>
            </div>
          </form>
        </div>

      </div>
      <!-- fin tab-content -->

      <!-- Link volver -->
      <div class="mt-4 text-center">
        <a href="index.php" class="text-muted small text-decoration-none">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al inicio
        </a>
      </div>

    </div>
  </div>
</main>

<footer class="text-white py-3 mt-5" style="background-color:#0A2342;"
        role="contentinfo" aria-label="Pie de página">
  <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small">
      <i class="bi bi-airplane-fill me-1" aria-hidden="true"></i>
      Check-Line &copy; <?= date('Y') ?>
    </span>
    <nav class="d-flex gap-3" aria-label="Navegación del pie de página">
      <a href="mapa-sitio.php" class="text-white-50 text-decoration-none small">Mapa del Sitio</a>
      <a href="privacidad.php" class="text-white-50 text-decoration-none small">Política de Privacidad</a>
    </nav>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js" defer></script>

<?php
/*
 * Si el POST falló (hay error) y el usuario estaba en la pestaña "password",
 * activamos esa tab via JS para que no vea la pantalla en blanco.
 */
if ($tabActiva === 'password' && $errorPassword !== ''): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var tabPassword = document.getElementById('tab-password');
    if (tabPassword) {
      var tab = new bootstrap.Tab(tabPassword);
      tab.show();
    }
  });
</script>
<?php endif; ?>

</body>
</html>
