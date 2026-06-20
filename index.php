<?php
/**
 * CHECK-LINE — Home pública (en construcción).
 * El diseño completo está en los Bocetos del Punto 7; el buscador real
 * de vuelos se conecta a la BD cuando esté ABMC Vuelos.
 */
require_once __DIR__ . '/includes/auth.php';
iniciarSesionSiNoExiste();
$logueado = usuarioLogueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check-Line — Sistema de Reservas de Vuelos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;">
  <a class="navbar-brand fw-bold" href="/index.php"><i class="bi bi-airplane-fill me-2"></i>Check-Line</a>
  <div class="d-flex gap-2">
    <?php if ($logueado): ?>
      <span class="text-white-50 small align-self-center"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
      <a href="/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    <?php else: ?>
      <a href="/login.php" class="btn btn-outline-light btn-sm">Iniciar sesión</a>
      <a href="/registro.php" class="btn btn-warning btn-sm fw-bold">Registrarse</a>
    <?php endif; ?>
  </div>
</nav>
<div class="container mt-5 text-center">
  <i class="bi bi-tools display-4 text-muted"></i>
  <h5 class="mt-3">Buscador de vuelos en construcción</h5>
  <p class="text-muted">El diseño está definido en el Punto 7 (Bocetos) — se conecta cuando esté listo ABMC Vuelos.</p>
</div>
</body>
</html>
