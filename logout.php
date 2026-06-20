<?php
/**
 * CHECK-LINE — Logout
 */
require_once __DIR__ . '/includes/auth.php';
iniciarSesionSiNoExiste();

$_SESSION = [];
session_destroy();

header('Location: /login.php');
exit;
