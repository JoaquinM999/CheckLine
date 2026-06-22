<?php
/**
 * ============================================================================
 * CHECK-LINE — RECUPERAR CONTRASEÑA (Paso 1: solicitud)
 * ============================================================================
 * Archivo: recuperar_password.php
 * Flujo completo:
 *   1. recuperar_password.php  ← este archivo: el usuario ingresa su email
 *   2. Se genera un token en BD y se envía el link por email
 *   3. reset_password.php      ← el usuario hace clic en el link y pone nueva contraseña
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail_templates.php';

iniciarSesionSiNoExiste();

if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Ingresá tu correo electrónico para continuar.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } else {
        try {
            $pdo = getConexion();

            // Buscar el usuario por email
            $stmt = $pdo->prepare("
                SELECT id_usuario, nombre, apellido, activo
                FROM usuarios
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            /*
             * IMPORTANTE — seguridad anti-enumeración:
             * Tanto si el email existe como si no, mostramos el MISMO mensaje de éxito.
             * Esto evita que alguien pueda descubrir qué emails están registrados
             * probando distintas direcciones.
             */
            if ($usuario && (int)$usuario['activo'] === 1) {
                // Generar token de reset (expira en 1 hora)
                $tokenReset  = bin2hex(random_bytes(16)); // 32 chars hex
                $expiracion  = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Guardar token y expiración en la BD
                $stmtToken = $pdo->prepare("
                    UPDATE usuarios
                    SET token_reset = :token, token_reset_expira = :expira
                    WHERE id_usuario = :id
                ");
                $stmtToken->execute([
                    'token'  => $tokenReset,
                    'expira' => $expiracion,
                    'id'     => $usuario['id_usuario'],
                ]);

                // Enviar el email
                $linkReset = APP_URL . '/reset_password.php?token=' . $tokenReset;
                $htmlMail  = templateResetPassword(
                    $usuario['nombre'],
                    $usuario['apellido'],
                    $linkReset
                );

                enviarMail(
                    destinatario: $email,
                    nombre:       $usuario['nombre'] . ' ' . $usuario['apellido'],
                    asunto:       'Check-Line — Restablecer contraseña',
                    cuerpoHtml:   $htmlMail
                );
                // No chequeamos el retorno del mail a propósito:
                // si falla no queremos revelar si el email existe o no.
            }

            // Mostrar siempre éxito (anti-enumeración)
            $success = true;

        } catch (PDOException $e) {
            error_log('Error en recuperar_password.php: ' . $e->getMessage());
            $error = 'Ocurrió un problema técnico. Intentá nuevamente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Recuperar Contraseña</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center py-5" style="min-height:100vh; background-color:#f0f4f8;">

<main class="container" role="main">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">

      <!-- Logo -->
      <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none" aria-label="Volver a la portada">
          <span class="fs-3 fw-bold" style="color:#0A2342;">
            <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
          </span>
        </a>
      </div>

      <section class="card shadow-sm border-0" aria-labelledby="titulo-recuperar">
        <div class="card-body p-4">

          <?php if ($success): ?>
            <!-- Pantalla de confirmación -->
            <div class="text-center py-2">
              <i class="bi bi-envelope-check-fill fs-1 text-success mb-3 d-block" aria-hidden="true"></i>
              <h1 id="titulo-recuperar" class="h5 fw-bold mb-3" style="color:#0A2342;">
                Revisá tu correo
              </h1>
              <p class="text-muted small mb-4">
                Si el correo ingresado está registrado en el sistema, vas a recibir
                un enlace para restablecer tu contraseña en los próximos minutos.
                Revisá también la carpeta de <strong>spam</strong>.
              </p>
              <a href="login.php" class="btn btn-primary btn-sm w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Volver al login
              </a>
            </div>

          <?php else: ?>
            <!-- Formulario de solicitud -->
            <h1 id="titulo-recuperar" class="h6 fw-semibold mb-1">Recuperar contraseña</h1>
            <p class="text-muted small mb-4">
              Ingresá el email de tu cuenta y te enviaremos un enlace para crear una nueva contraseña.
            </p>

            <?php if ($error !== ''): ?>
              <div class="alert alert-danger py-2 small" role="alert" aria-live="assertive">
                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="recuperar_password.php" novalidate>
              <div class="mb-4">
                <label for="email" class="form-label small fw-semibold">Correo Electrónico</label>
                <input type="email" id="email" name="email"
                       class="form-control form-control-sm" required
                       placeholder="tu@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
              <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm">
                <i class="bi bi-send me-1" aria-hidden="true"></i>Enviar enlace de restablecimiento
              </button>
            </form>

            <div class="text-center mt-3">
              <a href="login.php" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al login
              </a>
            </div>
          <?php endif; ?>

        </div>
      </section>

    </div>
  </div>
</main>

</body>
</html>
