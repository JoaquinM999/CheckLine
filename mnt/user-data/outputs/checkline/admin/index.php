<?php
/**
 * CHECK-LINE — Punto de entrada del panel Admin.
 * Por ahora redirige al único módulo terminado (Aerolíneas).
 * Cuando estén Promociones/Novedades/Reportes, esto pasa a ser un dashboard real.
 */
require_once __DIR__ . '/../includes/auth.php';
requerirRol('admin');

header('Location: /admin/aerolineas.php');
exit;
