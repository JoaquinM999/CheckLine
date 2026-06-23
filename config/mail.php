<?php
/**
 * ============================================================================
 * CHECK-LINE — CONFIGURACIÓN DE CORREO ELECTRÓNICO
 * ============================================================================
 *
 * PHPMailer ya está incluido en vendor/phpmailer/phpmailer/src/
 * No necesitás instalar nada extra.
 *
 * CONFIGURACIÓN HOTMAIL/OUTLOOK (una sola vez):
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. Crear cuenta en outlook.com (ej: checkline.sistema@outlook.com)
 * 2. Completar MAIL_USUARIO y MAIL_REMITENTE con ese email
 * 3. Completar MAIL_PASS con la contraseña normal de la cuenta Outlook
 *    (Outlook NO requiere App Password como Gmail, usa la contraseña directa)
 * 4. Completar APP_URL con tu URL de InfinityFree
 *
 * NOTA: Si Outlook bloquea el acceso SMTP, ir a:
 *   outlook.com → Configuración → Correo → Sincronización → POP e IMAP
 *   → Activar "Acceso SMTP autenticado"
 * ============================================================================
 */

// ─── Cargar PHPMailer (incluido en el repo) ────────────────────────────────
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

// ─── Credenciales SMTP Outlook/Hotmail ────────────────────────────────────
define('MAIL_HOST',      'smtp-mail.outlook.com');      // servidor Outlook
define('MAIL_PORT',      587);                          // TLS — no cambiar
define('MAIL_SEGURIDAD', 'tls');                        // no cambiar
define('MAIL_USUARIO',   'TU_CUENTA@outlook.com');      // ← tu email Outlook
define('MAIL_PASS',      'TU_CONTRASEÑA');              // ← contraseña normal Outlook
define('MAIL_REMITENTE', 'TU_CUENTA@outlook.com');      // ← igual que MAIL_USUARIO
define('MAIL_NOMBRE',    'Check-Line — Sistema de Reservas');

// ─── URL base del sitio ────────────────────────────────────────────────────
// Producción:  'https://TU_DOMINIO.infinityfreeapp.com'
// Local XAMPP: 'http://localhost/CheckLine'
define('APP_URL', 'https://TU_DOMINIO.infinityfreeapp.com');  // ← completar

// ─── Función de envío ──────────────────────────────────────────────────────
function enviarMail(string $destinatario, string $nombre, string $asunto, string $cuerpoHtml): bool
{
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USUARIO;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SEGURIDAD;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_REMITENTE, MAIL_NOMBRE);
        $mail->addAddress($destinatario, $nombre);
        $mail->addReplyTo(MAIL_REMITENTE, MAIL_NOMBRE);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />'], "\n", $cuerpoHtml
        ));

        $mail->send();
        return true;

    } catch (\Exception $e) {
        // Log detallado para diagnosticar problemas SMTP
        error_log('[CheckLine SMTP] Excepción: ' . $e->getMessage());
        error_log('[CheckLine SMTP] ErrorInfo: ' . $mail->ErrorInfo);
        error_log('[CheckLine SMTP] Destinatario: ' . $destinatario);
        return false;
    }
}
