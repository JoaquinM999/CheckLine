<?php
/**
 * ============================================================================
 * SISTEMA CHECK-LINE — CONFIGURACIÓN DE CORREO ELECTRÓNICO
 * ============================================================================
 * Archivo: config/mail.php
 * Propósito: Centralizar los datos de conexión SMTP y la función de envío.
 * Usamos PHPMailer (la librería más usada en PHP para mail con SMTP).
 *
 * PARA INSTALAR PHPMailer:
 *   Opción A (recomendada) — Composer:
 *     composer require phpmailer/phpmailer
 *
 *   Opción B — Sin Composer (descargar manualmente):
 *     1. Descargar desde https://github.com/PHPMailer/PHPMailer/releases
 *     2. Copiar la carpeta PHPMailer/src/ dentro de vendor/phpmailer/phpmailer/src/
 *     3. El require_once de abajo ya apunta a esa ruta
 *
 * CONFIGURACIÓN PARA GMAIL (la más común en proyectos UTN):
 *   - Activar "Contraseñas de aplicación" en tu cuenta Google
 *     (requiere tener activado 2FA en la cuenta)
 *   - Ir a: myaccount.google.com → Seguridad → Contraseñas de aplicaciones
 *   - Generar una contraseña para "Aplicación: Correo / Dispositivo: Windows"
 *   - Usar ESA contraseña (16 caracteres sin espacios) en MAIL_PASS abajo
 * ============================================================================
 */

// ─── Autoload ──────────────────────────────────────────────────────────────
// Si usás Composer, esto alcanza:
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Fallback: descarga manual de PHPMailer en /vendor/phpmailer/
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─── Credenciales SMTP ─────────────────────────────────────────────────────
// Completar con los datos reales antes de la entrega

define('MAIL_HOST',       'smtp.gmail.com');  // Para Gmail
define('MAIL_PORT',       587);               // TLS: 587 | SSL: 465
define('MAIL_SEGURIDAD',  'tls');             // 'tls' o 'ssl'
define('MAIL_USUARIO',    'checkline.sistema@gmail.com'); // Tu cuenta Gmail
define('MAIL_PASS',       'xxxx xxxx xxxx xxxx');         // Contraseña de aplicación
define('MAIL_REMITENTE',  'checkline.sistema@gmail.com'); // Igual al usuario
define('MAIL_NOMBRE',     'Check-Line — Sistema de Reservas');

// ─── URL base del sitio (para armar los links de activación) ───────────────
// En desarrollo local con XAMPP:   'http://localhost/CheckLine'
// En producción:                   'https://tudominio.com'
define('APP_URL', 'http://localhost/CheckLine');

// ─── Función de envío centralizada ────────────────────────────────────────
/**
 * Envía un correo electrónico usando PHPMailer + SMTP.
 *
 * @param string $destinatario  Email del receptor
 * @param string $nombre        Nombre del receptor (para personalizar el saludo)
 * @param string $asunto        Asunto del mensaje
 * @param string $cuerpoHtml    Cuerpo en HTML (se genera texto plano automáticamente)
 * @return bool                 true si se envió correctamente, false si falló
 */
function enviarMail(string $destinatario, string $nombre, string $asunto, string $cuerpoHtml): bool
{
    $mail = new PHPMailer(true); // true = lanza excepciones

    try {
        // ── Configuración del servidor SMTP ───────────────────────────────
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USUARIO;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SEGURIDAD;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // ── Remitente y destinatario ──────────────────────────────────────
        $mail->setFrom(MAIL_REMITENTE, MAIL_NOMBRE);
        $mail->addAddress($destinatario, $nombre);
        $mail->addReplyTo(MAIL_REMITENTE, MAIL_NOMBRE);

        // ── Contenido ─────────────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        // Versión texto plano (para clientes que no renderizan HTML)
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $cuerpoHtml));

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Loguear el error real sin exponerlo al usuario
        error_log('[CheckLine SMTP] Error al enviar mail a ' . $destinatario . ': ' . $mail->ErrorInfo);
        return false;
    }
}
