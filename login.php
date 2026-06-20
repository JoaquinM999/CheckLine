<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE - MÓDULO DE AUTENTICACIÓN
 * ============================================================================
 * Archivo: login.php
 * Propósito: Renderizar el formulario de acceso y procesar de forma segura 
 * las credenciales de los usuarios (Pasajeros, CEOs, Administradores).
 * * * Intervención Operativa y Seguridad:
 * - Prevención de fijación de sesión (Session Fixation) mediante la 
 * regeneración de ID tras validación exitosa.
 * - Enrutamiento relativo estandarizado para despliegues en subdirectorios
 * de servidores locales (XAMPP).
 * - Arquitectura de accesibilidad W3C en validación de formularios.
 * * @var string $error Almacena y expone mensajes de retroalimentación de fallos.
 * @author Equipo de Desarrollo Check-Line
 * @version 1.0.1
 * ============================================================================
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

iniciarSesionSiNoExiste();

// Redirección de contingencia: Si el usuario ya posee sesión, derivar a su panel
if (usuarioLogueado()) {
    header('Location: ' . urlSegunRol(rolActual()));
    exit;
}

$error = '';

// Captura de mensajes emitidos desde bloqueos de requerirRol()
if (isset($_GET['error']) && $_GET['error'] === 'sesion') {
    $error = 'Tenés que iniciar sesión para acceder a esa página.';
}

// Procesamiento de credenciales POST
// ... dentro de tu login.php ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Completá email y contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';
    } else {
        try {
            $pdo = getConexion();
            // Consulta para validar usuario y su rol
            $stmt = $pdo->prepare("
                SELECT u.id_usuario, u.nombre, u.apellido, u.password_hash, u.activo, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.email = :email
            ");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            // Verificación lógica
            if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
                $error = 'Email o contraseña incorrectos.';
            } elseif ((int) $usuario['activo'] !== 1) {
                // Aquí el sistema te detiene si el registro está en 0
                $error = 'Tu cuenta todavía no fue activada. Revisá tu casilla de mail.';
            } else {
                // --- Autenticación Exitosa ---
                session_regenerate_id(true); 
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre']     = $usuario['nombre'] . ' ' . $usuario['apellido'];
                $_SESSION['rol']        = $usuario['nombre_rol'];

                // Redirección centralizada
                header('Location: ' . urlSegunRol($usuario['nombre_rol']));
                exit;
            }
        } catch (PDOException $e) {
            error_log('Error login: ' . $e->getMessage());
            $error = 'Ocurrió un problema técnico. Intente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Check-Line — Iniciar sesión</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background-color:#f0f4f8;">

<main class="container" role="main">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      
      <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none" aria-label="Volver a la portada de Check-Line">
          <span class="fs-3 fw-bold" style="color:#0A2342;">
            <i class="bi bi-airplane-fill me-2" aria-hidden="true"></i>Check-Line
          </span>
        </a>
      </div>
      
      <section class="card shadow-sm border-0" aria-labelledby="titulo-login">
        <div class="card-body p-4">
          <h1 id="titulo-login" class="h6 fw-semibold mb-3">Iniciar sesión</h1>

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2 small" role="alert" aria-live="assertive">
              <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i><?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="login.php" novalidate aria-label="Formulario de acceso seguro">
            
            <div class="mb-3">
              <label for="inputEmail" class="form-label small fw-semibold">Email</label>
              <input type="email" name="email" id="inputEmail" class="form-control form-control-sm" required aria-required="true"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="tu@email.com">
            </div>
            
            <div class="mb-4">
              <label for="inputPassword" class="form-label small fw-semibold">Contraseña</label>
              <input type="password" name="password" id="inputPassword" class="form-control form-control-sm" required aria-required="true">
            </div>
            
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background-color: #0A2342; border-color: #0A2342;">
              <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Ingresar
            </button>
          </form>

          <div class="text-center mt-3">
            <a href="registro.php" class="small text-decoration-none">¿No tenés cuenta? Registrate</a>
          </div>
          
        </div>
      </section>
      
      <p class="text-center text-muted small mt-3">
        <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Volver al inicio</a>
      </p>
      
    </div>
  </div>
</main>

</body>
</html>