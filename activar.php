<?php
/**
 * ============================================================================
 * CHECK-LINE — ACTIVACIÓN DE CUENTA
 * ============================================================================
 * Archivo: activar.php (en la raíz del proyecto)
 * Propósito: Recibe el token enviado por email, lo verifica contra la BD
 *            y activa la cuenta del usuario si es válido.
 *
 * URL de llegada: http://localhost/CheckLine/activar.php?token=abc123...
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();

// Si ya está logueado, no tiene sentido estar acá
if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$estado  = ''; // 'ok' | 'invalido' | 'expirado' | 'ya_activo'
$mensaje = '';

// ── Validar que llegó el parámetro token ─────────────────────────────────
$token = trim($_GET['token'] ?? '');

if ($token === '' || !ctype_xdigit($token) || strlen($token) !== 32) {
    // Token malformado (no es un hex de 32 chars = bin2hex de 16 bytes)
    $estado  = 'invalido';
    $mensaje = 'El enlace de activación no es válido o está incompleto.';
} else {
    try {
        $pdo = getConexion();

        // Buscar el usuario con ese token que todavía no esté activo
        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre, apellido, activo, fecha_registro
            FROM usuarios
            WHERE token_validacion = :token
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            // Token no existe en la BD (ya se usó, o fue modificado)
            $estado  = 'invalido';
            $mensaje = 'El enlace de activación no es válido. Puede que ya haya sido utilizado.';

        } elseif ((int)$usuario['activo'] === 1) {
            // Ya estaba activo: el usuario hizo clic dos veces
            $estado  = 'ya_activo';
            $mensaje = 'Tu cuenta ya se encontraba activa. Podés iniciar sesión normalmente.';

        } else {
            // Verificar expiración: el token es válido por 24 horas
            $fechaRegistro = new DateTime($usuario['fecha_registro']);
            $ahora         = new DateTime();
            $diffHoras     = ($ahora->getTimestamp() - $fechaRegistro->getTimestamp()) / 3600;

            if ($diffHoras > 24) {
                // Token expirado — se podría ofrecer reenviar el mail (mejora futura)
                $estado  = 'expirado';
                $mensaje = 'El enlace de activación expiró (válido por 24 horas). '
                         . 'Por favor registrate nuevamente o contactá al soporte.';
            } else {
                // ¡Todo OK! Activar la cuenta y limpiar el token
                $stmtActivar = $pdo->prepare("
                    UPDATE usuarios
                    SET activo = 1, token_validacion = NULL
                    WHERE id_usuario = :id
                ");
                $stmtActivar->execute(['id' => $usuario['id_usuario']]);

                $estado  = 'ok';
                $mensaje = '¡Tu cuenta fue activada con éxito, ' . htmlspecialchars($usuario['nombre']) . '! '
                         . 'Ya podés iniciar sesión.';
            }
        }

    } catch (PDOException $e) {
        error_log('Error en activar.php: ' . $e->getMessage());
        $estado  = 'invalido';
        $mensaje = 'Ocurrió un problema al procesar la activación. Intente nuevamente más tarde.';
    }
}

// ── Configuración visual según resultado ─────────────────────────────────
$config = match($estado) {
    'ok'       => ['icono' => 'bi-check-circle-fill', 'clase' => 'text-success', 'titulo' => '¡Cuenta activada!'],
    'ya_activo'=> ['icono' => 'bi-info-circle-fill',  'clase' => 'text-primary', 'titulo' => 'Cuenta ya activa'],
    'expirado' => ['icono' => 'bi-clock-history',     'clase' => 'text-warning', 'titulo' => 'Enlace expirado'],
    default    => ['icono' => 'bi-x-circle-fill',     'clase' => 'text-danger',  'titulo' => 'Enlace inválido'],
};
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Activación de Cuenta</title>
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

      <!-- Tarjeta de resultado -->
      <section class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5 text-center">

          <i class="bi <?= $config['icono'] ?> fs-1 <?= $config['clase'] ?> mb-3 d-block"
             aria-hidden="true"></i>

          <h1 class="h5 fw-bold mb-3" style="color:#0A2342;">
            <?= $config['titulo'] ?>
          </h1>

          <p class="text-muted mb-4">
            <?= $mensaje ?>
          </p>

          <?php if ($estado === 'ok' || $estado === 'ya_activo'): ?>
            <a href="login.php" class="btn btn-warning fw-bold w-100 shadow-sm">
              <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Iniciar Sesión
            </a>
          <?php elseif ($estado === 'expirado'): ?>
            <a href="registro.php" class="btn btn-outline-secondary w-100">
              <i class="bi bi-person-plus-fill me-2" aria-hidden="true"></i>Registrarme nuevamente
            </a>
          <?php else: ?>
            <a href="index.php" class="btn btn-outline-secondary w-100">
              <i class="bi bi-house me-2" aria-hidden="true"></i>Volver al inicio
            </a>
          <?php endif; ?>

        </div>
      </section>

    </div>
  </div>
</main>

</body>
</html>
