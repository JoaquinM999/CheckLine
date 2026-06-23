<?php
/**
 * ============================================================================
 * CHECK-LINE — CONFIGURACIÓN DE CORREO ELECTRÓNICO
 * ============================================================================
 * ============================================================================
 */

// ─── Cargar PHPMailer (incluido en el repo) ────────────────────────────────
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

// ─── Credenciales SMTP Outlook/Hotmail ────────────────────────────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_SEGURIDAD', 'tls');
define('MAIL_USUARIO',   'checklineae@gmail.com');
define('MAIL_PASS',      'pzed xomh znmm ynyr');  // App Password de 16 caracteres
define('MAIL_REMITENTE', 'checklinear@gmail.com');
define('MAIL_NOMBRE',    'Check-Line — Sistema de Reservas');
define('APP_URL',        'https://checkline.infinityfreeapp.com');

// ─── URL base del sitio ────────────────────────────────────────────────────
// Producción:  'https://checkline.infinityfreeapp.com'
// Local XAMPP: 'http://localhost/CheckLine'
define('APP_URL', 'https://checkline.infinityfreeapp.com');  // ← completar

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
        error_log('[CheckLine SMTP] Error al enviar a ' . $destinatario . ': ' . $mail->ErrorInfo);
        return false;
    }
}
