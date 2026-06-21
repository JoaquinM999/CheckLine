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
 * Calcula la URL base absoluta del proyecto (ej: /CheckLine) a partir de la
 * ubicación de este archivo (includes/auth.php) y la convierte en una ruta
 * de servidor válida sin importar si el proyecto está en la raíz del host
 * o dentro de un subdirectorio (caso típico de XAMPP: /htdocs/CheckLine/).
 */
function urlBase(): string
{
    // includes/auth.php está siempre un nivel por debajo de la raíz del proyecto
    $raizProyecto = dirname(__DIR__);
    $docRoot      = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $raiz         = rtrim(str_replace('\\', '/', $raizProyecto), '/');

    $base = ($docRoot !== '' && str_starts_with($raiz, $docRoot))
        ? substr($raiz, strlen($docRoot))
        : '';

    return $base === '' ? '/' : $base . '/';
}

/**
 * Corta la ejecución y redirige si el usuario no está logueado
 * o no tiene el rol requerido. Usar al inicio de cada página protegida.
 */
function requerirRol(string $rolRequerido): void
{
    iniciarSesionSiNoExiste();

    if (!usuarioLogueado()) {
        header('Location: ' . urlBase() . 'login.php?error=sesion');
        exit;
    }

    if (rolActual() !== $rolRequerido) {
        // No usamos login.php acá: el usuario YA está logueado,
        // el problema es de permisos, no de autenticación.
        header('Location: ' . urlBase() . 'acceso-denegado.php');
        exit;
    }
}

/**
 * Devuelve la URL del panel principal correspondiente a cada rol.
 * Centralizada acá para que login.php y acceso-denegado.php no la dupliquen.
 */
function urlSegunRol(string $rol): string
{
    return urlBase() . match ($rol) {
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
