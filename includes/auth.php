<?php
/**
 * CHECK-LINE — Autenticación y control de acceso por rol
 */

function iniciarSesionSiNoExiste(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function usuarioLogueado(): bool
{
    iniciarSesionSiNoExiste();
    return isset($_SESSION['id_usuario']);
}

function rolActual(): ?string
{
    iniciarSesionSiNoExiste();
    return $_SESSION['rol'] ?? null;
}

/**
 * Corta la ejecución y redirige si el usuario no está logueado
 * o no tiene el rol requerido. Usar al inicio de cada página protegida.
 */
function requerirRol(string $rolRequerido): void
{
    iniciarSesionSiNoExiste();

    if (!usuarioLogueado()) {
        header('Location: /login.php?error=sesion');
        exit;
    }

    if (rolActual() !== $rolRequerido) {
        // No usamos login.php acá: el usuario YA está logueado,
        // el problema es de permisos, no de autenticación.
        header('Location: /acceso-denegado.php');
        exit;
    }
}

/**
 * Devuelve la URL del panel principal correspondiente a cada rol.
 * Centralizada acá para que login.php y acceso-denegado.php no la dupliquen.
 */
function urlSegunRol(string $rol): string
{
    return match ($rol) {
        'admin'    => 'admin/aerolineas.php',
        'ceo'      => 'ceo/index.php',
        'pasajero' => 'index.php',
        default    => 'login.php',
    };
}

function nombreUsuarioActual(): string
{
    iniciarSesionSiNoExiste();
    return $_SESSION['nombre'] ?? '';
}

/**
 * Mensajes de feedback (alert-success / alert-danger) que sobreviven
 * a un redirect (patrón Post-Redirect-Get para evitar reenvío de formularios).
 */
function setMensaje(string $tipo, string $texto): void
{
    iniciarSesionSiNoExiste();
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

function obtenerYLimpiarMensaje(): ?array
{
    iniciarSesionSiNoExiste();
    if (!isset($_SESSION['mensaje'])) {
        return null;
    }
    $m = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    return $m;
}
