<?php
/**
 * CHECK-LINE — Panel CEO (en construcción).
 * Placeholder hasta que se desarrolle ABMC Vuelos.
 */
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');
$tituloPagina = 'Check-Line — Panel CEO';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloPagina) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;">
  <a class="navbar-brand fw-bold" href="/ceo/index.php">
    <i class="bi bi-airplane-fill me-2"></i>Check-Line <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">CEO</span>
  </a>
  <a href="/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
</nav>
<div class="container mt-5 text-center">
  <i class="bi bi-tools display-4 text-muted"></i>
  <h5 class="mt-3">Panel de CEO en construcción</h5>
  <p class="text-muted">La gestión de Vuelos (ABMC) se está desarrollando — próximo módulo del sistema.</p>
  <p class="small text-muted">Sesión iniciada como: <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></p>
</div>
</body>
</html>
