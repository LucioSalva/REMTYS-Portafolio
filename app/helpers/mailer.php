<?php
/**
 * Helper de correo transaccional
 */

function obtenerBaseUrlAbsoluta(): string {
    return rtrim(APP_URL, '/');
}

function registrarEventoCorreo(string $mensaje): void {
    $directorio = dirname(MAIL_LOG_PATH);
    if (!is_dir($directorio)) {
        @mkdir($directorio, 0775, true);
    }

    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL;
    if (!@error_log($linea, 3, MAIL_LOG_PATH)) {
        error_log('[REMTYS-MAIL] ' . $mensaje);
    }
}

function limpiarEncabezadoCorreo(string $valor): string {
    return trim((string)preg_replace('/[\r\n]+/', ' ', $valor));
}

function codificarEncabezadoCorreo(string $valor): string {
    return '=?UTF-8?B?' . base64_encode($valor) . '?=';
}

function construirMensajeCorreo(string $htmlBody, ?string $textBody = null): array {
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . codificarEncabezadoCorreo(MAIL_FROM_NAME) . ' <' . MAIL_FROM_EMAIL . '>',
        'Reply-To: ' . codificarEncabezadoCorreo(MAIL_REPLY_TO_NAME) . ' <' . MAIL_REPLY_TO_EMAIL . '>',
        'X-Mailer: REMTYS Mailer',
    ];

    if ($textBody !== null && trim($textBody) !== '') {
        $boundary = 'remtys_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= '--' . $boundary . "--\r\n";
        return [$headers, $body];
    }

    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    return [$headers, $htmlBody];
}

function enviarCorreo(string $destinatarioEmail, string $destinatarioNombre, string $asunto, string $htmlBody, ?string $textBody = null): bool {
    $correo = sanitizarEmail($destinatarioEmail) ?? '';
    if ($correo === '') {
        return false;
    }

    $nombre = limpiarEncabezadoCorreo($destinatarioNombre !== '' ? $destinatarioNombre : $correo);
    $asunto = limpiarEncabezadoCorreo($asunto);

    if (MAIL_TRANSPORT === 'smtp' && MAIL_HOST !== '') {
        try {
            return enviarCorreoSMTP($correo, $nombre, $asunto, $htmlBody, $textBody);
        } catch (Throwable $e) {
            registrarEventoCorreo('Fallo SMTP para ' . $correo . ': ' . $e->getMessage());
            return false;
        }
    }

    // Sin SMTP configurado: intentar mail() nativo pero registrar advertencia
    registrarEventoCorreo('SMTP no configurado (MAIL_SMTP_HOST vacio). Intentando mail() nativo para ' . $correo . '. Configure MAIL_SMTP_HOST para entrega confiable.');
    return enviarCorreoNativo($correo, $nombre, $asunto, $htmlBody, $textBody);
}

function enviarCorreoNativo(string $correo, string $nombre, string $asunto, string $htmlBody, ?string $textBody = null): bool {
    [$headers, $body] = construirMensajeCorreo($htmlBody, $textBody);
    $destinatario = codificarEncabezadoCorreo($nombre) . ' <' . $correo . '>';
    $ok = mail($destinatario, codificarEncabezadoCorreo($asunto), $body, implode("\r\n", $headers));

    if (!$ok) {
        $lastError = error_get_last();
        $detalle = $lastError ? $lastError['message'] : 'sin detalle del sistema';
        registrarEventoCorreo('mail() fallo para ' . $correo . ': ' . $detalle);
    }

    return $ok;
}

function enviarCorreoSMTP(string $correo, string $nombre, string $asunto, string $htmlBody, ?string $textBody = null): bool {
    // ssl:// para conexion SSL directa, tcp:// para STARTTLS o sin encriptacion (none)
    $prefijo = (MAIL_ENCRYPTION === 'ssl') ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client($prefijo . MAIL_HOST . ':' . MAIL_PORT, $errno, $errstr, 15);
    if (!$socket) {
        throw new RuntimeException('No fue posible conectar con el servidor SMTP: ' . $errstr . ' (' . $errno . ')');
    }

    stream_set_timeout($socket, 15);
    smtpEsperarCodigo($socket, [220]);
    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    smtpEnviarLinea($socket, 'EHLO ' . $hostname);
    smtpEsperarCodigo($socket, [250]);

    if (MAIL_ENCRYPTION === 'tls') {
        smtpEnviarLinea($socket, 'STARTTLS');
        smtpEsperarCodigo($socket, [220]);
        $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoOk !== true) {
            throw new RuntimeException('No fue posible iniciar TLS en SMTP.');
        }
        smtpEnviarLinea($socket, 'EHLO ' . $hostname);
        smtpEsperarCodigo($socket, [250]);
    }
    // 'none' o '' = sin encriptacion (solo para desarrollo local con Mailpit/Mailhog)

    if (MAIL_USERNAME !== '') {
        smtpEnviarLinea($socket, 'AUTH LOGIN');
        smtpEsperarCodigo($socket, [334]);
        smtpEnviarLinea($socket, base64_encode(MAIL_USERNAME));
        smtpEsperarCodigo($socket, [334]);
        smtpEnviarLinea($socket, base64_encode(MAIL_PASSWORD));
        smtpEsperarCodigo($socket, [235]);
    }

    smtpEnviarLinea($socket, 'MAIL FROM:<' . MAIL_FROM_EMAIL . '>');
    smtpEsperarCodigo($socket, [250]);
    smtpEnviarLinea($socket, 'RCPT TO:<' . $correo . '>');
    smtpEsperarCodigo($socket, [250, 251]);
    smtpEnviarLinea($socket, 'DATA');
    smtpEsperarCodigo($socket, [354]);

    [$headers, $body] = construirMensajeCorreo($htmlBody, $textBody);
    $headers[] = 'Date: ' . date(DATE_RFC2822);
    $headers[] = 'To: ' . codificarEncabezadoCorreo($nombre) . ' <' . $correo . '>';
    $headers[] = 'Subject: ' . codificarEncabezadoCorreo($asunto);

    $mensaje = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $mensaje = preg_replace('/(?m)^\./', '..', str_replace(["\r\n", "\r", "\n"], "\r\n", $mensaje));
    fwrite($socket, $mensaje . "\r\n.\r\n");
    smtpEsperarCodigo($socket, [250]);
    smtpEnviarLinea($socket, 'QUIT');
    fclose($socket);

    return true;
}

function smtpEnviarLinea($socket, string $linea): void {
    fwrite($socket, $linea . "\r\n");
}

function smtpEsperarCodigo($socket, array $codigosValidos): void {
    $respuesta = smtpLeerRespuesta($socket);
    $codigo = (int)substr($respuesta, 0, 3);
    if (!in_array($codigo, $codigosValidos, true)) {
        throw new RuntimeException('Respuesta SMTP inesperada: ' . trim($respuesta));
    }
}

function smtpLeerRespuesta($socket): string {
    $respuesta = '';
    while (($linea = fgets($socket, 515)) !== false) {
        $respuesta .= $linea;
        if (strlen($linea) < 4 || $linea[3] === ' ') {
            break;
        }
    }

    if ($respuesta === '') {
        throw new RuntimeException('El servidor SMTP no devolvio respuesta.');
    }

    return $respuesta;
}

function construirCorreoVerificacion(string $nombre, string $verificationUrl): array {
    $nombreSeguro = htmlspecialchars($nombre !== '' ? $nombre : 'contribuyente', ENT_QUOTES, 'UTF-8');
    $urlSegura = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');
    $asunto = 'Verifique su correo para activar su cuenta REMTYS';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$asunto}</title>
</head>
<body style="margin:0;padding:0;background:#f6f2ec;font-family:Arial,Helvetica,sans-serif;color:#2d2a26;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #eadfce;border-radius:24px;overflow:hidden;box-shadow:0 18px 40px rgba(54,26,18,0.08);">
            <div style="padding:28px 32px;background:linear-gradient(135deg,#691C32 0%,#8b3a58 100%);color:#ffffff;">
                <div style="font-size:12px;letter-spacing:0.18em;text-transform:uppercase;font-weight:700;opacity:0.82;">REMTYS</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">Confirme su correo electronico</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.65;opacity:0.9;">Protegemos el acceso al portal validando la cuenta antes del primer inicio de sesion.</p>
            </div>
            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hola {$nombreSeguro},</p>
                <p style="margin:0 0 18px;font-size:15px;line-height:1.75;">Recibimos su registro en el portal del contribuyente. Para activar la cuenta y continuar con sus tramites, confirme este correo con el siguiente boton:</p>
                <p style="margin:28px 0;">
                    <a href="{$urlSegura}" style="display:inline-block;padding:14px 22px;border-radius:14px;background:#691C32;color:#ffffff;text-decoration:none;font-weight:700;">Verificar correo y activar cuenta</a>
                </p>
                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#6b655f;">Si el boton no abre correctamente, copie y pegue esta liga en su navegador:</p>
                <p style="margin:0 0 18px;font-size:13px;line-height:1.7;word-break:break-word;"><a href="{$urlSegura}" style="color:#691C32;">{$urlSegura}</a></p>
                <div style="padding:16px 18px;border-radius:16px;background:#fbf7f0;border:1px solid #eadfce;color:#665f58;font-size:13px;line-height:1.7;">
                    Este enlace vence en 24 horas. Si usted no solicito este registro, puede ignorar este mensaje.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    $text = "Hola {$nombre}.\n\n";
    $text .= "Para activar su cuenta en REMTYS debe verificar este correo.\n";
    $text .= "Abra el siguiente enlace en su navegador:\n{$verificationUrl}\n\n";
    $text .= "Este enlace vence en 24 horas. Si usted no solicito este registro, ignore este mensaje.\n";

    return [$asunto, $html, $text];
}

function enviarCorreoVerificacion(string $correo, string $nombre, string $verificationUrl): bool {
    [$asunto, $html, $text] = construirCorreoVerificacion($nombre, $verificationUrl);
    return enviarCorreo($correo, $nombre, $asunto, $html, $text);
}

function construirCorreoRecuperacionPassword(string $nombre, string $resetUrl, int $horasExpiracion): array {
    $nombreSeguro = htmlspecialchars($nombre !== '' ? $nombre : 'contribuyente', ENT_QUOTES, 'UTF-8');
    $urlSegura = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $asunto = 'Restablezca su contrasena de REMTYS';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$asunto}</title>
</head>
<body style="margin:0;padding:0;background:#f6f2ec;font-family:Arial,Helvetica,sans-serif;color:#2d2a26;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #eadfce;border-radius:24px;overflow:hidden;box-shadow:0 18px 40px rgba(54,26,18,0.08);">
            <div style="padding:28px 32px;background:linear-gradient(135deg,#691C32 0%,#8b3a58 100%);color:#ffffff;">
                <div style="font-size:12px;letter-spacing:0.18em;text-transform:uppercase;font-weight:700;opacity:0.82;">REMTYS</div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">Restablecer contrasena</h1>
                <p style="margin:12px 0 0;font-size:15px;line-height:1.65;opacity:0.9;">Recibimos una solicitud para cambiar la contrasena de su cuenta.</p>
            </div>
            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hola {$nombreSeguro},</p>
                <p style="margin:0 0 18px;font-size:15px;line-height:1.75;">Para definir una nueva contrasena, haga clic en el siguiente boton:</p>
                <p style="margin:28px 0;">
                    <a href="{$urlSegura}" style="display:inline-block;padding:14px 22px;border-radius:14px;background:#691C32;color:#ffffff;text-decoration:none;font-weight:700;">Restablecer contrasena</a>
                </p>
                <p style="margin:0 0 12px;font-size:14px;line-height:1.7;color:#6b655f;">Si el boton no abre correctamente, copie y pegue esta liga en su navegador:</p>
                <p style="margin:0 0 18px;font-size:13px;line-height:1.7;word-break:break-word;"><a href="{$urlSegura}" style="color:#691C32;">{$urlSegura}</a></p>
                <div style="padding:16px 18px;border-radius:16px;background:#fbf7f0;border:1px solid #eadfce;color:#665f58;font-size:13px;line-height:1.7;">
                    Este enlace vence en {$horasExpiracion} hora(s). Si usted no solicito este cambio, ignore el mensaje: su contrasena actual sigue vigente.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

    $text  = "Hola {$nombre}.\n\n";
    $text .= "Recibimos una solicitud para restablecer la contrasena de su cuenta REMTYS.\n";
    $text .= "Abra el siguiente enlace en su navegador para definir una nueva contrasena:\n";
    $text .= "{$resetUrl}\n\n";
    $text .= "El enlace vence en {$horasExpiracion} hora(s).\n";
    $text .= "Si usted no solicito este cambio, ignore este correo — su contrasena actual sigue vigente.\n";

    return [$asunto, $html, $text];
}

function enviarCorreoRecuperacionPassword(string $correo, string $nombre, string $resetUrl, int $horasExpiracion = 1): bool {
    [$asunto, $html, $text] = construirCorreoRecuperacionPassword($nombre, $resetUrl, $horasExpiracion);
    return enviarCorreo($correo, $nombre, $asunto, $html, $text);
}
