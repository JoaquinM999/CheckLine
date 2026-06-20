<?php
/**
 * CHECK-LINE — Header común del panel Admin
 * Incluir DESPUÉS de requerirRol('admin') en cada página protegida.
 */
$tituloPagina = $tituloPagina ?? 'Check-Line — Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?= htmlspecialchars($tituloPagina) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;">
  <a class="navbar-brand fw-bold" href="/admin/index.php">
    <i class="bi bi-airplane-fill me-2"></i>Check-Line
    <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">Admin</span>
  </a>
  <div class="d-flex gap-2 align-items-center">
    <span class="text-white-50 small d-none d-md-block">
      <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars(nombreUsuarioActual()) ?>
    </span>
    <a href="/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
  </div>
</nav>

<div class="container-fluid mt-3">
  <div class="row">
    <div class="col-md-2 sidebar-nav">
      <div class="list-group list-group-flush">
        <a href="/admin/aerolineas.php"
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'aerolineas' ? ' active' : '' ?>">
          <i class="bi bi-building me-2"></i>Aerolíneas
        </a>
        <a href="/admin/promociones.php"
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'promociones' ? ' active' : '' ?>">
          <i class="bi bi-check2-circle me-2"></i>Aprobaciones
        </a>
        <a href="/admin/novedades.php"
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'novedades' ? ' active' : '' ?>">
          <i class="bi bi-megaphone me-2"></i>Novedades
        </a>
        <a href="/admin/reportes.php"
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'reportes' ? ' active' : '' ?>">
          <i class="bi bi-bar-chart-line me-2"></i>Reportes
        </a>
      </div>
    </div>
    <div class="col-md-10">
<?php
// --- Feedback Post-Redirect-Get ---
$msg = obtenerYLimpiarMensaje();
if ($msg) {
    $clase = $msg['tipo'] === 'success' ? 'alert-success' : 'alert-danger';
    $icono = $msg['tipo'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
    echo '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert">'
       . '<i class="bi ' . $icono . ' me-2"></i>' . htmlspecialchars($msg['texto'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>
