<?php
/**
 * REMTYS APP - Front Controller
 * Punto de entrada unico de la aplicacion
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('PUBLIC_PATH', __DIR__);
define('APP_NAME', 'REMTYS');
define('APP_FULL_NAME', 'Sistema de Gestion Predial y Fiscal Municipal');
define('APP_VERSION', '1.0.0');
// Municipio/estado de operación (configurables; la versión de portafolio usa valores genéricos)
define('APP_MUNICIPIO', getenv('APP_MUNICIPIO') ?: 'Municipio Demo');
define('APP_ESTADO', getenv('APP_ESTADO') ?: 'Estado de México');
// BASE_URL vacío ("") = app servida en la raíz, p. ej. con php -S -t public
define('BASE_URL', getenv('BASE_URL') !== false ? rtrim((string)getenv('BASE_URL'), '/') : '/remtysapp/public');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/remtysapp/public');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024); // 20 MB

// Autoload helpers
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/mail.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/csrf.php';
require_once APP_PATH . '/helpers/csp.php';
require_once APP_PATH . '/helpers/flash.php';
require_once APP_PATH . '/helpers/sanitize.php';
require_once APP_PATH . '/helpers/contribuyente.php';
require_once APP_PATH . '/helpers/mailer.php';
require_once APP_PATH . '/helpers/audit.php';
require_once APP_PATH . '/helpers/env_check.php';
require_once APP_PATH . '/helpers/document_serve.php';
require_once APP_PATH . '/helpers/middleware.php';
require_once APP_PATH . '/helpers/paginacion.php';

// Validar entorno antes de cualquier otra operacion
validarEntornoProduccion();

// Iniciar sesion segura
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Captura global de excepciones no atrapadas
set_exception_handler(function (Throwable $e) {
    $isPDO = $e instanceof PDOException;
    $cat = $isPDO ? 'ERROR' : 'SISTEMA';
    $evt = $isPDO ? 'DB_QUERY_ERROR' : 'UNCAUGHT_EXCEPTION';
    auditLog('CRITICAL', $cat, $evt, [
        'class'   => get_class($e),
        'message' => substr($e->getMessage(), 0, 500),
        'file'    => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
    http_response_code(500);
    if (in_array(getenv('APP_ENV') ?: 'local', ['local', 'development', 'docker'], true)) {
        echo '<h1>Error interno</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    } else {
        echo 'Error interno del servidor.';
    }
});

// Captura de errores PHP fatales
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) return false;
    auditLog('ERROR', 'SISTEMA', 'PHP_ERROR', [
        'errno'   => $errno,
        'message' => substr($errstr, 0, 500),
        'file'    => basename($errfile) . ':' . $errline,
    ]);
    return false; // Dejar que PHP maneje el error también
});

startSecureSession();
header_remove('X-Powered-By');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Type: text/html; charset=UTF-8");
$cspNonce = cspNonce();
header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'nonce-{$cspNonce}'; "
    . "script-src-attr 'none'; "
    . "style-src 'self' 'nonce-{$cspNonce}' https://fonts.googleapis.com; "
    . "style-src-attr 'unsafe-inline'; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "frame-ancestors 'none'; "
    . "form-action 'self'; "
    . "base-uri 'self';"
);
$isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if ($isHTTPS) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Cache-Control: prevent caching of authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Router - extraer modulo y accion
$modulo = sanitizarTexto($_GET['modulo'] ?? 'home');
$accion = sanitizarTexto($_GET['accion'] ?? 'index');

// Whitelist de modulos y acciones permitidas
$rutasPermitidas = [
    'home'          => ['index'],
    'auth'          => ['login', 'registro', 'logout', 'acceso_denegado', 'procesar_login', 'procesar_registro', 'verificar_email', 'reenviar_verificacion', 'recuperar_password', 'procesar_recuperacion', 'resetear_password', 'procesar_reset'],
    'contribuyente' => ['dashboard', 'perfil', 'editar_perfil', 'predios', 'nuevo_predio', 'editar_predio', 'documentos', 'expedientes', 'ver_expediente', 'tramites', 'nuevo_tramite', 'requisitos_tramite', 'enviar_tramite', 'subir_documento', 'ver_documento', 'pagos', 'saldo', 'obligaciones', 'colonias_por_cp'],
    'admin'         => ['dashboard', 'contribuyentes', 'ver_contribuyente', 'editar_contribuyente', 'nuevo_contribuyente', 'predios', 'ver_predio', 'editar_predio', 'nuevo_predio', 'expedientes', 'ver_expediente', 'nuevo_expediente', 'editar_expediente', 'tramites', 'ver_tramite', 'nuevo_tramite', 'editar_tramite', 'validacion_docs', 'validar_documento', 'ver_documento', 'catalogos', 'tipos_tramite', 'nuevo_tipo_tramite', 'editar_tipo_tramite', 'tipos_documento', 'nuevo_tipo_documento', 'editar_tipo_documento', 'requisitos', 'nuevo_requisito', 'eliminar_requisito', 'fiscal', 'nuevo_periodo', 'obligaciones', 'nueva_obligacion', 'pagos', 'nuevo_pago', 'aplicar_pago', 'inspecciones', 'nueva_inspeccion', 'editar_inspeccion', 'ver_inspeccion', 'dependencias_tramite', 'nueva_dependencia', 'eliminar_dependencia', 'historial', 'reportes', 'colonias_por_cp'],
    'supergod'      => ['consola', 'consola_api'],
];

// Validar modulo
if (!array_key_exists($modulo, $rutasPermitidas)) {
    $modulo = 'home';
    $accion = 'index';
}

// Validar accion
if (!in_array($accion, $rutasPermitidas[$modulo], true)) {
    $accion = 'index';
}

// Dispatch
$controladorArchivo = APP_PATH . '/controllers/' . $modulo . '_controller.php';
if (file_exists($controladorArchivo)) {
    require_once $controladorArchivo;
    $funcionControlador = 'accion_' . $accion;
    if (function_exists($funcionControlador)) {
        call_user_func($funcionControlador);
    } else {
        http_response_code(404);
        auditLog('WARNING', 'SISTEMA', 'ROUTE_NOT_FOUND', ['modulo' => $modulo, 'accion' => $accion, 'motivo' => 'accion no existe']);
        require APP_PATH . '/views/shared/404.php';
    }
} else {
    http_response_code(404);
    auditLog('WARNING', 'SISTEMA', 'ROUTE_NOT_FOUND', ['modulo' => $modulo, 'accion' => $accion, 'motivo' => 'controlador no existe']);
    require APP_PATH . '/views/shared/404.php';
}

