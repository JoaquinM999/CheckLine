<?php
/**
 * ============================================================================
 * CHECK-LINE — RESET DE CONTRASEÑA (Paso 2: nueva contraseña)
 * ============================================================================
 * Archivo: reset_password.php
 * Llegada: el usuario hace clic en el link del email →
 *          reset_password.php?token=abc123...
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();

if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = false;
$tokenValido = false;
$usuario = null;

// ── Paso A: validar que el token llegó y tiene forma correcta ─────────────
if ($token === '' || !ctype_xdigit($token) || strlen($token) !== 32) {
    $error = 'El enlace de restablecimiento no es válido o está incompleto.';
} else {
    try {
        $pdo = getConexion();

        // Buscar usuario con ese token que no haya expirado
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre, apellido, token_reset_expira
            FROM usuarios
            WHERE token_reset = :token
              AND activo = 1
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $error = 'El enlace no es válido o ya fue utilizado. Solicitá uno nuevo.';
        } else {
            // Verificar expiración (1 hora)
            $expira = new DateTime($usuario['token_reset_expira']);
            $ahora  = new DateTime();

            if ($ahora > $expira) {
                // Limpiar el token expirado de la BD
                $pdo->prepare("
                    UPDATE usuarios SET token_reset = NULL, token_reset_expira = NULL
                    WHERE id_usuario = :id
                ")->execute(['id' => $usuario['id_usuario']]);

                $error = 'El enlace expiró (válido por 1 hora). '
                       . 'Solicitá un nuevo enlace desde la página de recuperación.';
            } else {
                $tokenValido = true;
            }
        }

    } catch (PDOException $e) {
        error_log('Error en reset_password.php (validación token): ' . $e->getMessage());
        $error = 'Ocurrió un problema técnico. Intentá nuevamente más tarde.';
    }
}

// ── Paso B: procesar el formulario de nueva contraseña ────────────────────
if ($tokenValido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevaPassword = $_POST['password']         ?? '';
    $confirmar     = $_POST['confirm_password'] ?? '';

    if ($nuevaPassword === '') {
        $error = 'Ingresá tu nueva contraseña.';
    } elseif (strlen($nuevaPassword) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($nuevaPassword !== $confirmar) {
        $error = 'Las contraseñas no coinciden. Verificá la escritura.';
    } else {
        try {
            $nuevoHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

            // Actualizar contraseña y limpiar el token (ya no sirve)
            $stmtUpdate = $pdo->prepare("
                UPDATE usuarios
                SET password_hash       = :hash,
                    token_reset         = NULL,
                    token_reset_expira  = NULL
                WHERE id_usuario = :id
            ");
            $stmtUpdate->execute([
                'hash' => $nuevoHash,
                'id'   => $usuario['id_usuario'],
            ]);

            $success = true;

        } catch (PDOException $e) {
            error_log('Error en reset_password.php (update): ' . $e->getMessage());
            $error = 'No se pudo actualizar la contraseña. Intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Nueva Contraseña</title>
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

      <section class="card shadow-sm border-0" aria-labelledby="titulo-reset">
        <div class="card-body p-4">

          <?php if ($success): ?>
            <!-- Contraseña cambiada con éxito -->
            <div class="text-center py-2">
              <i class="bi bi-shield-check fs-1 text-success mb-3 d-block" aria-hidden="true"></i>
              <h1 id="titulo-reset" class="h5 fw-bold mb-3" style="color:#0A2342;">
                ¡Contraseña actualizada!
              </h1>
              <p class="text-muted small mb-4">
                Tu contraseña fue cambiada exitosamente.
                Ya podés iniciar sesión con tu nueva contraseña.
              </p>
              <a href="login.php" class="btn btn-warning fw-bold w-100 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Iniciar Sesión
              </a>
            </div>

          <?php elseif (!$tokenValido): ?>
            <!-- Token inválido o expirado -->
            <div class="text-center py-2">
              <i class="bi bi-x-circle-fill fs-1 text-danger mb-3 d-block" aria-hidden="true"></i>
              <h1 id="titulo-reset" class="h5 fw-bold mb-3" style="color:#0A2342;">
                Enlace inválido
              </h1>
              <p class="text-muted small mb-4">
                <?= htmlspecialchars($error) ?>
              </p>
              <a href="recuperar_password.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Solicitar nuevo enlace
              </a>
            </div>

          <?php else: ?>
            <!-- Formulario de nueva contraseña -->
            <h1 id="titulo-reset" class="h6 fw-semibold mb-1">Nueva contraseña</h1>
            <p class="text-muted small mb-4">
              Hola <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>,
              ingresá tu nueva contraseña a continuación.
            </p>

            <?php if ($error !== ''): ?>
              <div class="alert alert-danger py-2 small" role="alert" aria-live="assertive">
                <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <!-- El token viaja como campo oculto para no perderlo en el POST -->
            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>" novalidate>

              <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Nueva contraseña</label>
                <input type="password" id="password" name="password"
                       class="form-control form-control-sm" required minlength="8"
                       aria-describedby="passHelp">
                <div id="passHelp" class="form-text">Mínimo 8 caracteres.</div>
              </div>

              <div class="mb-4">
                <label for="confirm_password" class="form-label small fw-semibold">Confirmar contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       class="form-control form-control-sm" required>
              </div>

              <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm">
                <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Guardar nueva contraseña
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
