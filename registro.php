<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE REGISTRO
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();

if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
        $error = 'Todos los campos son obligatorios para completar el registro.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico ingresado no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe contener un mínimo de 8 caracteres por seguridad.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden. Verifique la escritura.';
    } else {
        try {
            $pdo = getConexion();
            
            $stmtCheck = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
            $stmtCheck->execute(['email' => $email]);
            
            if ($stmtCheck->fetch()) {
                $error = 'El correo electrónico ya se encuentra registrado en el sistema.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $tokenActivacion = bin2hex(random_bytes(16));
                
                $sqlInsert = "
                    INSERT INTO usuarios (nombre, apellido, email, password_hash, id_rol, activo, token_validacion, fecha_registro) 
                    VALUES (:nombre, :apellido, :email, :hash, 3, 0, :token, NOW())
                ";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $ejecucionEfectiva = $stmtInsert->execute([
                    'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'email'    => $email,
                    'hash'     => $hash,
                    'token'    => $tokenActivacion
                ]);
                
                if ($ejecucionEfectiva) {
                    $success = 'Registro exitoso. Su cuenta ha sido creada correctamente.';
                } else {
                    $error = 'Ocurrió un error interno al registrar la cuenta.';
                }
            }
        } catch (PDOException $e) {
            error_log('Error en registro.php: ' . $e->getMessage());
            $error = 'Ocurrió un problema con la base de datos. Intente nuevamente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Registro de Pasajero</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center py-5" style="min-height:100vh; background-color:#f0f4f8;">

<main class="container" role="main">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      
      <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none">
          <span class="fs-3 fw-bold" style="color:#0A2342;">
            <i class="bi bi-airplane-fill me-2"></i>Check-Line
          </span>
        </a>
      </div>
      
      <section class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
          <h1 class="h5 fw-bold mb-4 text-center" style="color:#0A2342;">
            <?= $success !== '' ? '¡Bienvenido a Check-Line!' : 'Crear una cuenta nueva' ?>
          </h1>

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2 small fw-semibold shadow-sm" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <?php if ($success !== ''): ?>
            <div class="alert alert-success p-4 text-center shadow-sm" role="alert">
              <i class="bi bi-check-circle-fill fs-1 d-block mb-3 text-success"></i>
              <p class="mb-4"><?= htmlspecialchars($success) ?></p>
              <a href="login.php" class="btn btn-primary w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ir al Inicio de Sesión
              </a>
            </div>
          <?php else: ?>

          <form method="POST" action="registro.php" novalidate>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-muted">Nombre</label>
                <input type="text" name="nombre" class="form-control form-control-sm" required value="<?= htmlspecialchars($nombre ?? '') ?>">
              </div>
              <div class="col-sm-6">
                <label class="form-label small fw-semibold text-muted">Apellido</label>
                <input type="text" name="apellido" class="form-control form-control-sm" required value="<?= htmlspecialchars($apellido ?? '') ?>">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">Correo Electrónico</label>
              <input type="email" name="email" class="form-control form-control-sm" required value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted">Contraseña</label>
              <input type="password" name="password" class="form-control form-control-sm" required>
            </div>
            <div class="mb-4">
              <label class="form-label small fw-semibold text-muted">Confirmar Contraseña</label>
              <input type="password" name="confirm_password" class="form-control form-control-sm" required>
            </div>
            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm">
              <i class="bi bi-person-plus-fill me-1"></i>Completar Registro
            </button>
          </form>
          <?php endif; ?>

        </div>
      </section>
    </div>
  </div>
</main>
</body>
</html>