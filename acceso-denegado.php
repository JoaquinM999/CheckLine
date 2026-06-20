<?php
/**
 * CHECK-LINE — Acceso Denegado (403)
 * El usuario está logueado pero su rol no tiene permiso sobre la página solicitada.
 */
require_once __DIR__ . '/includes/auth.php';
iniciarSesionSiNoExiste();

// Si por algún motivo llega acá sin sesión, lo mandamos a loguearse en vez de mostrar esta página
if (!usuarioLogueado()) {
    header('Location: /login.php?error=sesion');
    exit;
}

$urlPanel = urlSegunRol(rolActual());
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Acceso Denegado</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background-color:#f0f4f8;">
<div class="container text-center">
  <i class="bi bi-shield-lock display-3 text-danger"></i>
  <h5 class="mt-3">Acceso denegado</h5>
  <p class="text-muted">Tu cuenta (rol: <strong><?= htmlspecialchars(rolActual()) ?></strong>) no tiene permiso para acceder a esa sección del sistema.</p>
  <a href="<?= htmlspecialchars($urlPanel) ?>" class="btn btn-primary btn-sm mt-2">
    <i class="bi bi-arrow-left me-1"></i>Volver a mi panel
  </a>
</div>
</body>
</html>
