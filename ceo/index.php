<?php
/**
 * CHECK-LINE — Punto de entrada del panel CEO.
 * Redirige al único módulo terminado (Vuelos).
 */
require_once __DIR__ . '/../includes/auth.php';
requerirRol('ceo');

header('Location: vuelos.php');
exit;
