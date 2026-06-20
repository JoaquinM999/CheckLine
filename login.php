<?php
/**
 * CHECK-LINE — Login
 * Maneja GET (mostrar formulario) y POST (procesar credenciales) en un mismo archivo.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();

// Si ya está logueado, redirigir directo a su panel
if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$error = '';

// Mensajes desde redirecciones de requerirRol()
if (isset($_GET['error']) && $_GET['error'] === 'sesion') {
    $error = 'Tenés que iniciar sesión para acceder a esa página.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Validación server-side ---
    if ($email === '' || $password === '') {
        $error = 'Completá email y contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';
    } else {
        $pdo = getConexion();
        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.nombre, u.apellido, u.password_hash, u.activo, r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.email = :email
        ");
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $error = 'Email o contraseña incorrectos.';
        } elseif ((int) $usuario['activo'] !== 1) {
            $error = 'Tu cuenta todavía no fue activada. Revisá tu casilla de mail.';
        } else {
            // --- Login exitoso ---
            session_regenerate_id(true); // previene session fixation
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'] . ' ' . $usuario['apellido'];
            $_SESSION['rol']        = $usuario['nombre_rol'];

            header('Location: ' . urlSegunRol($usuario['nombre_rol']));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Iniciar sesión</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background-color:#f0f4f8;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="text-center mb-4">
        <a href="/index.php" class="text-decoration-none">
          <span class="fs-3 fw-bold" style="color:#0A2342;">
            <i class="bi bi-airplane-fill me-2"></i>Check-Line
          </span>
        </a>
      </div>
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h6 class="fw-semibold mb-3">Iniciar sesión</h6>

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2 small">
              <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="/login.php" novalidate>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" class="form-control form-control-sm" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="tu@email.com">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Contraseña</label>
              <input type="password" name="password" class="form-control form-control-sm" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
            </button>
          </form>

          <div class="text-center mt-3">
            <a href="/registro.php" class="small text-decoration-none">¿No tenés cuenta? Registrate</a>
          </div>
        </div>
      </div>
      <p class="text-center text-muted small mt-3">
        <a href="/index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
