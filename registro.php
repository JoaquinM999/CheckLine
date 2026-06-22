<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE — MÓDULO DE REGISTRO
 * ============================================================================
 * Archivo: registro.php
 * Cambios respecto a la versión anterior:
 *   - Se integra config/mail.php y includes/mail_templates.php
 *   - Tras insertar el usuario en BD se envía el email de activación
 *   - El mensaje de éxito ahora indica al usuario que revise su correo
 *   - Si el mail falla se informa al usuario sin romper el flujo
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';           // ← nuevo
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail_templates.php'; // ← nuevo

iniciarSesionSiNoExiste();

if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$error        = '';
$success      = '';
$mailEnviado  = false;  // para diferenciar el mensaje de éxito

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre']           ?? '');
    $apellido = trim($_POST['apellido']          ?? '');
    $email    = trim($_POST['email']             ?? '');
    $password = $_POST['password']               ?? '';
    $confirm  = $_POST['confirm_password']       ?? '';

    // ── Validaciones ─────────────────────────────────────────────────────
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

            // Verificar email duplicado
            $stmtCheck = $pdo->prepare("
                SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1
            ");
            $stmtCheck->execute(['email' => $email]);

            if ($stmtCheck->fetch()) {
                $error = 'El correo electrónico ya se encuentra registrado en el sistema.';
            } else {
                // Generar hash y token
                $hash            = password_hash($password, PASSWORD_DEFAULT);
                $tokenActivacion = bin2hex(random_bytes(16)); // 32 chars hex

                // Insertar usuario con activo = 0 (pendiente de activación)
                $stmtInsert = $pdo->prepare("
                    INSERT INTO usuarios
                        (nombre, apellido, email, password_hash, id_rol, activo, token_validacion, fecha_registro)
                    VALUES
                        (:nombre, :apellido, :email, :hash, 3, 0, :token, NOW())
                ");
                $ok = $stmtInsert->execute([
                    'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'email'    => $email,
                    'hash'     => $hash,
                    'token'    => $tokenActivacion,
                ]);

                if ($ok) {
                    // ── Enviar email de activación ────────────────────────
                    $linkActivacion = APP_URL . '/activar.php?token=' . $tokenActivacion;

                    $htmlMail = templateActivacionCuenta($nombre, $apellido, $linkActivacion);

                    $mailEnviado = enviarMail(
                        destinatario: $email,
                        nombre:       $nombre . ' ' . $apellido,
                        asunto:       'Check-Line — Activá tu cuenta',
                        cuerpoHtml:   $htmlMail
                    );

                    if ($mailEnviado) {
                        $success = "Registro exitoso. Te enviamos un correo a <strong>"
                                 . htmlspecialchars($email)
                                 . "</strong> con el enlace de activación. "
                                 . "Revisá también la carpeta de spam.";
                    } else {
                        // El usuario quedó registrado en BD pero el mail falló.
                        // Le informamos y le damos el link directamente para no bloquearlo.
                        // (En producción real se reintentaría con una cola de emails)
                        $success = "Registro exitoso, pero no pudimos enviarte el email de confirmación. "
                                 . "Podés activar tu cuenta desde este enlace: "
                                 . "<a href='" . htmlspecialchars($linkActivacion) . "' class='alert-link'>Activar mi cuenta</a>.";
                    }
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

      <!-- Logo -->
      <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none">
          <span class="fs-3 fw-bold" style="color:#0A2342;">
            <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
          </span>
        </a>
      </div>

      <section class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
          <h1 class="h5 fw-bold mb-4 text-center" style="color:#0A2342;">
            <?= $success !== '' ? '¡Registro completado!' : 'Crear una cuenta nueva' ?>
          </h1>

          <!-- Error -->
          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2 small fw-semibold shadow-sm" role="alert" aria-live="assertive">
              <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <!-- Éxito: pantalla de confirmación -->
          <?php if ($success !== ''): ?>
            <div class="alert alert-success p-4 text-center shadow-sm" role="status" aria-live="polite">
              <i class="bi bi-envelope-check-fill fs-1 d-block mb-3 text-success" aria-hidden="true"></i>
              <p class="mb-4"><?= $success /* ya tiene htmlspecialchars aplicado arriba */ ?></p>
              <a href="login.php" class="btn btn-primary w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Ir al Inicio de Sesión
              </a>
            </div>

          <?php else: ?>

          <!-- Formulario -->
          <form method="POST" action="registro.php" novalidate>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label for="nombre" class="form-label small fw-semibold text-muted">Nombre</label>
                <input type="text" id="nombre" name="nombre"
                       class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($nombre ?? '') ?>">
              </div>
              <div class="col-sm-6">
                <label for="apellido" class="form-label small fw-semibold text-muted">Apellido</label>
                <input type="text" id="apellido" name="apellido"
                       class="form-control form-control-sm" required
                       value="<?= htmlspecialchars($apellido ?? '') ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label small fw-semibold text-muted">Correo Electrónico</label>
              <input type="email" id="email" name="email"
                     class="form-control form-control-sm" required
                     value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="mb-3">
              <label for="password" class="form-label small fw-semibold text-muted">Contraseña</label>
              <input type="password" id="password" name="password"
                     class="form-control form-control-sm" required minlength="8">
              <div class="form-text">Mínimo 8 caracteres.</div>
            </div>

            <div class="mb-4">
              <label for="confirm_password" class="form-label small fw-semibold text-muted">Confirmar Contraseña</label>
              <input type="password" id="confirm_password" name="confirm_password"
                     class="form-control form-control-sm" required>
            </div>

            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm">
              <i class="bi bi-person-plus-fill me-1" aria-hidden="true"></i>Completar Registro
            </button>

            <p class="text-center small text-muted mt-3 mb-0">
              ¿Ya tenés cuenta?
              <a href="login.php" class="fw-semibold" style="color:#0A2342;">Iniciá sesión</a>
            </p>
          </form>

          <?php endif; ?>
        </div>
      </section>

    </div>
  </div>
</main>

</body>
</html>
