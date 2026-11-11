<?php
/**
 * Configuracion de correo del sistema.
 * Puede sobrescribirse con variables de entorno del servidor.
 */

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost/remtysapp/public');
}

$defaultTransport = (getenv('MAIL_SMTP_HOST') || getenv('MAIL_HOST')) ? 'smtp' : 'mail';

define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: $defaultTransport);
define('MAIL_TRANSPORT', getenv('MAIL_TRANSPORT') ?: MAIL_DRIVER);

define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ejemplo.com');
define('MAIL_FROM_EMAIL', MAIL_FROM_ADDRESS);
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME . ' Notificaciones');

define('MAIL_REPLY_TO', getenv('MAIL_REPLY_TO') ?: MAIL_FROM_ADDRESS);
define('MAIL_REPLY_TO_EMAIL', MAIL_REPLY_TO);
define('MAIL_REPLY_TO_NAME', getenv('MAIL_REPLY_TO_NAME') ?: MAIL_FROM_NAME);

define('MAIL_VERIFY_EXPIRES_HOURS', max(1, (int)(getenv('MAIL_VERIFY_EXPIRES_HOURS') ?: 24)));
define('MAIL_VERIFY_TOKEN_HOURS', MAIL_VERIFY_EXPIRES_HOURS);
define('MAIL_RESEND_COOLDOWN_SECONDS', max(30, (int)(getenv('MAIL_RESEND_COOLDOWN_SECONDS') ?: 120)));
define('MAIL_VERIFY_RESEND_SECONDS', MAIL_RESEND_COOLDOWN_SECONDS);

define('MAIL_LOG_PATH', getenv('MAIL_LOG_PATH') ?: ((defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/storage/logs/mail.log'));

define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: getenv('MAIL_HOST') ?: '');
define('MAIL_HOST', MAIL_SMTP_HOST);
define('MAIL_SMTP_PORT', (int)(getenv('MAIL_SMTP_PORT') ?: getenv('MAIL_PORT') ?: 587));
define('MAIL_PORT', MAIL_SMTP_PORT);
define('MAIL_SMTP_USERNAME', getenv('MAIL_SMTP_USERNAME') ?: getenv('MAIL_USERNAME') ?: '');
define('MAIL_USERNAME', MAIL_SMTP_USERNAME);
define('MAIL_SMTP_PASSWORD', getenv('MAIL_SMTP_PASSWORD') ?: getenv('MAIL_PASSWORD') ?: '');
define('MAIL_PASSWORD', MAIL_SMTP_PASSWORD);
define('MAIL_SMTP_ENCRYPTION', strtolower((string)(getenv('MAIL_SMTP_ENCRYPTION') ?: getenv('MAIL_ENCRYPTION') ?: 'tls')));
define('MAIL_ENCRYPTION', MAIL_SMTP_ENCRYPTION);
define('MAIL_SMTP_TIMEOUT', max(5, (int)(getenv('MAIL_SMTP_TIMEOUT') ?: 15)));
