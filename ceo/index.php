<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE GESTIÓN CORPORATIVA (CEO)
 * ============================================================================
 * Archivo: index.php
 * Propósito: Interfaz principal y panel de control para los perfiles CEO
 * de las aerolíneas registradas en la plataforma.
 * * ESTADO ACTUAL: EN CONSTRUCCIÓN (Placeholder)
 * - Este módulo actuará como el punto de entrada para el ABMC de Vuelos
 * y la gestión integral de promociones.
 * * * Normativa de Seguridad y Acceso:
 * - Requiere validación estricta de sesión activa mediante auth.php.
 * - Invoca requerirRol('ceo') para bloquear accesos no autorizados de 
 * pasajeros, visitantes o administradores globales.
 * * @var string $tituloPagina Título oficial de la pestaña del navegador.
 * * @author Equipo de Desarrollo Check-Line
 * @version 0.1.0-alpha
 * ============================================================================
 */
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');
$tituloPagina = 'Check-Line — Panel CEO';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="Panel de Control para CEOs de Aerolíneas - Sistema Check-Line">
  <meta name="robots" content="noindex, nofollow">
  
  <title><?= htmlspecialchars($tituloPagina) ?></title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-light" data-bs-theme="light">

<header role="banner">
  <nav class="navbar navbar-dark px-3 py-2" style="background-color:#0A2342;" aria-label="Navegación del Panel CEO">
    
    <a class="navbar-brand fw-bold" href="/ceo/index.php" aria-label="Recargar el panel de control del CEO">
      <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line 
      <span class="badge bg-warning text-dark ms-1" style="font-size:10px;" aria-label="Rol de usuario actual">CEO</span>
    </a>
    
    <div class="d-flex align-items-center">
      <a href="/logout.php" class="btn btn-outline-light btn-sm" role="button" aria-label="Cerrar la sesión corporativa de forma segura">Salir</a>
    </div>
    
  </nav>
</header>

<main class="container mt-5 text-center" role="main" aria-live="polite">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      
      <i class="bi bi-tools display-4 text-muted" aria-hidden="true"></i>
      <h1 class="h5 mt-3" tabindex="0">Panel de CEO en construcción</h1>
      
      <p class="text-muted">
        La gestión integral de Vuelos (ABMC) se encuentra en fase de desarrollo activo. 
        Este constituirá el próximo módulo operativo del ecosistema Check-Line.
      </p>
      
      <div class="mt-4 p-3 border rounded bg-white shadow-sm" role="status" aria-label="Información de la sesión actual">
        <p class="small text-muted mb-0">
          <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
          Sesión corporativa iniciada como: <strong><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario no identificado') ?></strong>
        </p>
      </div>
      
    </div>
  </div>
</main>

</body>
</html>