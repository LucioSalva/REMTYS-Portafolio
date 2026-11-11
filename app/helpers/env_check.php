<?php
/**
 * Validacion de entorno al arranque — REMTYS
 * Falla de forma segura si faltan variables criticas en produccion.
 */

function validarEntornoProduccion(): void {
    $appEnv = getenv('APP_ENV') ?: 'local';

    // In local/development, skip strict validation but warn about mail
    if (in_array($appEnv, ['local', 'development', 'docker'], true)) {
        $mailHost = getenv('MAIL_SMTP_HOST') ?: getenv('MAIL_HOST') ?: '';
        if ($mailHost === '') {
            error_log('[REMTYS] WARNING: MAIL_SMTP_HOST no configurado. El envio de correos de verificacion fallara. Configure MAIL_SMTP_HOST o agregue Mailpit a docker-compose.yml.');
        }
        return;
    }

    $errores = [];

    // Database — all required in production
    $dbVars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($dbVars as $var) {
        $val = getenv($var);
        if ($val === false || $val === '') {
            $errores[] = $var;
        }
    }

    // Application
    $appUrl = getenv('APP_URL');
    if ($appUrl === false || $appUrl === '') {
        $errores[] = 'APP_URL';
    }

    // Warn if APP_URL doesn't start with https in production
    if ($appUrl && !str_starts_with($appUrl, 'https://')) {
        error_log('[REMTYS] WARNING: APP_URL does not use HTTPS in production: ' . $appUrl);
    }

    // Check DB_PASS is not the default
    $dbPass = getenv('DB_PASS');
    if ($dbPass === 'admin' || $dbPass === 'CHANGE_THIS_PASSWORD' || $dbPass === 'password') {
        $errores[] = 'DB_PASS (insecure default detected)';
    }

    // Mail — requerido en produccion, advertencia en dev/docker
    $mailHost = getenv('MAIL_SMTP_HOST') ?: getenv('MAIL_HOST') ?: '';
    if ($mailHost === '') {
        $errores[] = 'MAIL_SMTP_HOST (el envio de correos fallara sin SMTP configurado)';
    }

    if (!empty($errores)) {
        error_log('[REMTYS] CRITICAL: Missing or insecure environment variables: ' . implode(', ', $errores));
        http_response_code(503);
        die('Error de configuracion del servidor. Contacte al administrador.');
    }
}
