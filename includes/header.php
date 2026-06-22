<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE ADMINISTRACIÓN
 * ============================================================================
 * Archivo: header.php
 * Propósito: Cabecera común y estructura base (Navbar + Sidebar) para el
 * panel de control central del Administrador.
 * * Normativa de Seguridad:
 * - Este archivo debe ser incluido estrictamente DESPUÉS de invocar la
 * función requerirRol('admin') para garantizar la protección del módulo.
 * * Dependencias de Interfaz:
 * - Requiere Bootstrap 5.3+ (Core CSS)
 * - Requiere Bootstrap Icons 1.11+
 * - Requiere style.css (Estilos globales personalizados)
 * * @var string $tituloPagina Define el título de la pestaña del navegador.
 * Si no se define explícitamente, utiliza el valor 
 * por defecto 'Check-Line — Admin'.
 * @var string $seccionActiva Variable opcional utilizada para resaltar el 
 * ítem actual en el menú de navegación lateral.
 * * @author Equipo de Desarrollo Check-Line
 * @version 1.0.0
 * ============================================================================
 */
$tituloPagina = $tituloPagina ?? 'Check-Line — Admin';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="Panel de Administración del Sistema de Reservas Check-Line">
  <meta name="robots" content="noindex, nofollow"> <title><?= htmlspecialchars($tituloPagina) ?></title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-light" data-bs-theme="light">

<header role="banner">
  <nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;" aria-label="Navegación principal del administrador">
    
    <a class="navbar-brand fw-bold" href="aerolineas.php" aria-label="Volver al inicio del panel de administración Check-Line">
      <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
      <span class="badge bg-warning text-dark ms-1" style="font-size:10px;" aria-label="Nivel de acceso actual: Administrador">Admin</span>
    </a>
    
    <div class="d-flex gap-2 align-items-center" role="group" aria-label="Opciones de sesión">
      <span class="text-white-50 small d-none d-md-block" aria-live="polite">
        <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
        <span class="visually-hidden">Usuario conectado: </span>
        <?= htmlspecialchars(nombreUsuarioActual()) ?>
      </span>
      <a href="../logout.php" class="btn btn-outline-light btn-sm" aria-label="Cerrar sesión de forma segura del sistema" role="button">Salir</a>
    </div>

  </nav>
</header>

<div class="container-fluid mt-3" role="main">
  <div class="row">
    
    <nav class="col-md-2 sidebar-nav" aria-label="Menú de navegación lateral de administración">
      <div class="list-group list-group-flush" role="menu">
        
        <a href="aerolineas.php"
           role="menuitem"
           aria-label="Gestionar base de datos de Aerolíneas"
           <?= ($seccionActiva ?? '') === 'aerolineas' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'aerolineas' ? ' active' : '' ?>">
          <i class="bi bi-building me-2" aria-hidden="true"></i>Aerolíneas
        </a>
        
        <a href="aprobaciones.php"
           role="menuitem"
           aria-label="Revisar y gestionar Aprobaciones de Promociones"
           <?= ($seccionActiva ?? '') === 'promociones' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'promociones' ? ' active' : '' ?>">
          <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>Aprobaciones
        </a>
        
        <a href="novedades.php"
           role="menuitem"
           aria-label="Administrar las Novedades y Avisos del sistema"
           <?= ($seccionActiva ?? '') === 'novedades' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'novedades' ? ' active' : '' ?>">
          <i class="bi bi-megaphone me-2" aria-hidden="true"></i>Novedades
        </a>
        
        <a href="reportes.php"
           role="menuitem"
           aria-label="Generar y visualizar Reportes del sistema"
           <?= ($seccionActiva ?? '') === 'reportes' ? 'aria-current="page"' : '' ?>
           class="list-group-item list-group-item-action<?= ($seccionActiva ?? '') === 'reportes' ? ' active' : '' ?>">
          <i class="bi bi-bar-chart-line me-2" aria-hidden="true"></i>Reportes
        </a>
        
      </div>
    </nav>
    <main class="col-md-10" id="contenido-principal" aria-live="polite">
<?php
/**
 * ----------------------------------------------------------------------------
 * GESTIÓN DE FEEDBACK Y AVISOS (Post-Redirect-Get)
 * ----------------------------------------------------------------------------
 * Este bloque intercepta los mensajes guardados en la sesión tras operaciones
 * de inserción, actualización o eliminación en la base de datos (CRUD).
 * Si existe un mensaje, despliega una alerta accesible de Bootstrap.
 */
$msg = obtenerYLimpiarMensaje();

if ($msg) {
    // Evaluación semántica para definir colores, iconos y prioridad de lectura (W3C)
    $clase = ($msg['tipo'] === 'success') ? 'alert-success' : 'alert-danger';
    $icono = ($msg['tipo'] === 'success') ? 'bi-check-circle' : 'bi-exclamation-triangle';
    $ariaLive = ($msg['tipo'] === 'success') ? 'polite' : 'assertive';
    
    // Generación dinámica del componente de alerta
    echo '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert" aria-live="' . $ariaLive . '">'
       . '<i class="bi ' . $icono . ' me-2" aria-hidden="true"></i>' 
       . htmlspecialchars($msg['texto'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar esta notificación"></button>'
       . '</div>';
}
?>