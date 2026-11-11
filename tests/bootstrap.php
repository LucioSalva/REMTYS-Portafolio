<?php
/**
 * REMTYS — PHPUnit bootstrap
 *
 * - Define constantes mínimas requeridas por helpers que asumen el front controller.
 * - No conecta a BD por defecto: tests de unidad son puros; tests de integración
 *   activan la conexión explícitamente via TestCase.
 */

declare(strict_types=1);

define('ROOT_PATH',    dirname(__DIR__));
define('APP_PATH',     ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('APP_NAME',     'REMTYS');
define('APP_MUNICIPIO', 'Municipio Demo');
define('APP_ESTADO',    'Estado de México');
define('APP_VERSION',  'test');
define('BASE_URL',     '/remtysapp/public');
define('APP_URL',      'http://localhost/remtysapp/public');
define('UPLOAD_PATH',  STORAGE_PATH . '/uploads');
if (!defined('MAX_UPLOAD_BYTES')) {
    define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);
}

// Silence headers_sent() noise when helpers touch session
ini_set('session.use_cookies',       '0');
ini_set('session.use_only_cookies',  '0');
ini_set('display_errors',            '1');
error_reporting(E_ALL);

// Composer autoload if present
$composer = __DIR__ . '/../vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
}
