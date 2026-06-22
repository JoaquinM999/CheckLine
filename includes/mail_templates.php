<?php
/**
 * ============================================================================
 * CHECK-LINE — TEMPLATES DE EMAIL (versión actualizada)
 * ============================================================================
 * Archivo: includes/mail_templates.php
 * REEMPLAZA la versión anterior (que solo tenía templateActivacionCuenta).
 * Agrega: templateResetPassword()
 * ============================================================================
 */

/**
 * Template: email de activación de cuenta (registro nuevo).
 */
function templateActivacionCuenta(string $nombre, string $apellido, string $linkActivacion): string
{
    $nombreCompleto = htmlspecialchars($nombre . ' ' . $apellido);
    $linkSeguro     = htmlspecialchars($linkActivacion);
    $anio           = date('Y');

    return <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Activá tu cuenta en Check-Line</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f0f4f8; font-family: Arial, Helvetica, sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8; padding:40px 0;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0"
                 style="background:#fff; border-radius:8px; overflow:hidden;
                        box-shadow:0 2px 8px rgba(0,0,0,.08); max-width:600px; width:100%;">
            <tr>
              <td style="background:#0A2342; padding:32px 40px; text-align:center;">
                <p style="margin:0; font-size:26px; font-weight:bold; color:#fff; letter-spacing:1px;">✈ Check-Line</p>
                <p style="margin:8px 0 0; font-size:13px; color:#a8c4e0; letter-spacing:2px; text-transform:uppercase;">Sistema de Reservas de Vuelos</p>
              </td>
            </tr>
            <tr>
              <td style="padding:40px 40px 24px;">
                <h1 style="margin:0 0 16px; font-size:20px; color:#0A2342;">¡Bienvenido/a a Check-Line, {$nombreCompleto}!</h1>
                <p style="margin:0 0 16px; font-size:15px; color:#444; line-height:1.6;">
                  Tu cuenta fue creada exitosamente. Para poder iniciar sesión, confirmá tu correo haciendo clic en el botón de abajo.
                </p>
                <p style="margin:0 0 32px; font-size:15px; color:#444; line-height:1.6;">
                  Este enlace es válido durante <strong>24 horas</strong>.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:0 40px 40px; text-align:center;">
                <a href="{$linkSeguro}"
                   style="display:inline-block; background:#f0a500; color:#0A2342; font-weight:bold;
                          font-size:16px; text-decoration:none; padding:14px 36px; border-radius:6px;">
                  ✔ Activar mi cuenta
                </a>
              </td>
            </tr>
            <tr>
              <td style="padding:0 40px 32px;">
                <p style="margin:0; font-size:13px; color:#888; line-height:1.5;">Si el botón no funciona, copiá este enlace:</p>
                <p style="margin:6px 0 0; font-size:12px; word-break:break-all;">
                  <a href="{$linkSeguro}" style="color:#0A2342;">{$linkSeguro}</a>
                </p>
              </td>
            </tr>
            <tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #e8ecf0; margin:0;"></td></tr>
            <tr>
              <td style="padding:24px 40px; text-align:center;">
                <p style="margin:0; font-size:12px; color:#aaa; line-height:1.6;">
                  © {$anio} Check-Line — UTN Facultad Regional Rosario<br>
                  Este es un mensaje automático, por favor no respondas a este correo.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

/**
 * Template: email para restablecer contraseña olvidada.
 *
 * @param string $nombre    Nombre del usuario
 * @param string $apellido  Apellido del usuario
 * @param string $linkReset URL con el token de reset
 */
function templateResetPassword(string $nombre, string $apellido, string $linkReset): string
{
    $nombreCompleto = htmlspecialchars($nombre . ' ' . $apellido);
    $linkSeguro     = htmlspecialchars($linkReset);
    $anio           = date('Y');

    return <<<HTML
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Restablecer contraseña — Check-Line</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f0f4f8; font-family: Arial, Helvetica, sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8; padding:40px 0;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0"
                 style="background:#fff; border-radius:8px; overflow:hidden;
                        box-shadow:0 2px 8px rgba(0,0,0,.08); max-width:600px; width:100%;">

            <!-- Header -->
            <tr>
              <td style="background:#0A2342; padding:32px 40px; text-align:center;">
                <p style="margin:0; font-size:26px; font-weight:bold; color:#fff; letter-spacing:1px;">✈ Check-Line</p>
                <p style="margin:8px 0 0; font-size:13px; color:#a8c4e0; letter-spacing:2px; text-transform:uppercase;">Sistema de Reservas de Vuelos</p>
              </td>
            </tr>

            <!-- Ícono y título -->
            <tr>
              <td style="padding:40px 40px 8px; text-align:center;">
                <p style="font-size:48px; margin:0;" aria-hidden="true">🔒</p>
                <h1 style="margin:16px 0 0; font-size:20px; color:#0A2342;">Restablecer contraseña</h1>
              </td>
            </tr>

            <!-- Cuerpo -->
            <tr>
              <td style="padding:20px 40px 32px;">
                <p style="margin:0 0 16px; font-size:15px; color:#444; line-height:1.6;">
                  Hola <strong>{$nombreCompleto}</strong>,
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#444; line-height:1.6;">
                  Recibimos una solicitud para restablecer la contraseña de tu cuenta en Check-Line.
                  Hacé clic en el botón de abajo para crear una nueva contraseña.
                </p>
                <p style="margin:0 0 32px; font-size:14px; color:#888; line-height:1.6;">
                  Este enlace es válido durante <strong>1 hora</strong>.
                  Si no solicitaste este cambio, podés ignorar este mensaje: tu contraseña actual no será modificada.
                </p>
              </td>
            </tr>

            <!-- Botón CTA -->
            <tr>
              <td style="padding:0 40px 40px; text-align:center;">
                <a href="{$linkSeguro}"
                   style="display:inline-block; background:#0A2342; color:#ffffff; font-weight:bold;
                          font-size:16px; text-decoration:none; padding:14px 36px; border-radius:6px; letter-spacing:0.5px;">
                  🔑 Crear nueva contraseña
                </a>
              </td>
            </tr>

            <!-- Link de texto alternativo -->
            <tr>
              <td style="padding:0 40px 32px;">
                <p style="margin:0; font-size:13px; color:#888; line-height:1.5;">Si el botón no funciona, copiá este enlace en tu navegador:</p>
                <p style="margin:6px 0 0; font-size:12px; word-break:break-all;">
                  <a href="{$linkSeguro}" style="color:#0A2342;">{$linkSeguro}</a>
                </p>
              </td>
            </tr>

            <!-- Aviso de seguridad -->
            <tr>
              <td style="padding:0 40px 32px;">
                <div style="background:#fff8e1; border-left:4px solid #f0a500; padding:12px 16px; border-radius:4px;">
                  <p style="margin:0; font-size:13px; color:#555; line-height:1.5;">
                    <strong>¿No solicitaste este cambio?</strong><br>
                    Si no fuiste vos, ignorá este correo. Tu contraseña no cambiará hasta que hagas clic en el enlace de arriba.
                  </p>
                </div>
              </td>
            </tr>

            <tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #e8ecf0; margin:0;"></td></tr>
            <tr>
              <td style="padding:24px 40px; text-align:center;">
                <p style="margin:0; font-size:12px; color:#aaa; line-height:1.6;">
                  © {$anio} Check-Line — UTN Facultad Regional Rosario<br>
                  Este es un mensaje automático, por favor no respondas a este correo.
                </p>
              </td>
            </tr>

          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}
