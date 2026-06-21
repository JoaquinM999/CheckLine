<?php
/**
 * CHECK-LINE — Header común del panel CEO
 * Incluir DESPUÉS de requerirRol('ceo') en cada página protegida.
 * Requiere que $aerolineaCeo esté seteado (nombre/código de la aerolínea del CEO logueado).
 */
$tituloPagina = $tituloPagina ?? 'Check-Line — CEO';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="Panel de Gestión para CEOs de Aerolíneas - Sistema Check-Line">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($tituloPagina) ?></title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-light" data-bs-theme="light">

<header role="banner">
  <nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;" aria-label="Navegación del panel CEO">

    <a class="navbar-brand fw-bold" href="vuelos.php" aria-label="Volver al inicio del panel CEO">
      <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
      <span class="badge bg-warning text-dark ms-1" style="font-size:10px;" aria-label="Nivel de acceso actual: CEO">CEO</span>
    </a>

    <div class="d-flex gap-2 align-items-center" role="group" aria-label="Información de sesión y opciones">
      <span class="text-white-50 small d-none d-md-block" aria-live="polite">
        <i class="bi bi-building me-1" aria-hidden="true"></i>
        <span class="visually-hidden">Aerolínea: </span>
        <?= htmlspecialchars($aerolineaCeo['nombre'] ?? '—') ?>
      </span>
      <span class="text-white-50 small d-none d-md-block" aria-live="polite">
        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
        <span class="visually-hidden">Usuario conectado: </span>
        <?= htmlspecialchars(nombreUsuarioActual()) ?>
      </span>
      <a href="../logout.php" class="btn btn-outline-light btn-sm" aria-label="Cerrar sesión de forma segura" role="button">Salir</a>
    </div>

  </nav>
</header>

<div class="container-fluid mt-3" role="main">
  <div class="row">

    <nav class="col-md-2 sidebar-nav" aria-label="Menú de navegación lateral del CEO">
      <div class="list-group list-group-flush" role="menu">

        <a href="vuelos.php"
           role="menuitem"
           aria-label="Gestionar Vuelos de mi aerolínea"
           <?= ($seccionActiva ?? '') === 'vuelos' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'vuelos' ? ' active' : '' ?>">
          <i class="bi bi-airplane me-2" aria-hidden="true"></i>Vuelos
        </a>

        <a href="promociones.php"
           role="menuitem"
           aria-label="Gestionar Promociones de mi aerolínea"
           <?= ($seccionActiva ?? '') === 'promociones' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'promociones' ? ' active' : '' ?>">
          <i class="bi bi-megaphone me-2" aria-hidden="true"></i>Promociones
        </a>

        <a href="reportes.php"
           role="menuitem"
           aria-label="Ver Reportes de mi aerolínea"
           <?= ($seccionActiva ?? '') === 'reportes' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'reportes' ? ' active' : '' ?>">
          <i class="bi bi-bar-chart-line me-2" aria-hidden="true"></i>Reportes
        </a>

      </div>
    </nav>
    <main class="col-md-10" id="contenido-principal" aria-live="polite">
<?php
/**
 * Gestión de feedback (Post-Redirect-Get): intercepta mensajes guardados en
 * sesión tras operaciones de alta/baja/modificación y los muestra como alerta.
 */
$msg = obtenerYLimpiarMensaje();

if ($msg) {
    $clase    = ($msg['tipo'] === 'success') ? 'alert-success' : 'alert-danger';
    $icono    = ($msg['tipo'] === 'success') ? 'bi-check-circle' : 'bi-exclamation-triangle';
    $ariaLive = ($msg['tipo'] === 'success') ? 'polite' : 'assertive';

    echo '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert" aria-live="' . $ariaLive . '">'
       . '<i class="bi ' . $icono . ' me-2" aria-hidden="true"></i>'
       . htmlspecialchars($msg['texto'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar esta notificación"></button>'
       . '</div>';
}
?>
