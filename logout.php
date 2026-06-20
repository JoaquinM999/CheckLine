<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE CIERRE DE SESIÓN
 * ============================================================================
 */
require_once __DIR__ . '/includes/auth.php';

// Iniciamos para poder destruirla
iniciarSesionSiNoExiste();

// Limpiamos las variables de sesión
$_SESSION = [];

// Destruimos la sesión en el servidor
session_destroy();

// REDIRECCIÓN OBLIGATORIA al inicio
header('Location: index.php');
exit;