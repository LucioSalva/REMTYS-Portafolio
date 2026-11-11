<?php
/**
 * Sistema de Audit Logging — REMTYS
 * Registra eventos de seguridad, autenticación, acciones admin y errores.
 * NUNCA registra: contraseñas, tokens completos, session IDs, cookies, DB_PASS.
 */

define('AUDIT_LOG_PATH', STORAGE_PATH . '/logs/audit.log');
define('SECURITY_LOG_PATH', STORAGE_PATH . '/logs/security.log');
define('ERROR_LOG_PATH', STORAGE_PATH . '/logs/error.log');
define('ALERT_LOG_PATH', STORAGE_PATH . '/logs/alerts.log');

/**
 * Correlation ID por request — liga system_events, audit_cambios, historial y logs.
 * Se genera una vez por proceso. Respeta un header entrante si pasa validación estricta.
 */
function correlationId(): string {
    static $cid = null;
    if ($cid !== null) {
        return $cid;
    }
    $header = $_SERVER['HTTP_X_CORRELATION_ID'] ?? '';
    if (is_string($header) && $header !== '' && preg_match('/^[A-Za-z0-9\-]{8,40}$/', $header)) {
        $cid = $header;
    } else {
        try {
            $cid = substr(bin2hex(random_bytes(12)), 0, 24);
        } catch (Throwable $e) {
            $cid = substr(md5(uniqid((string)mt_rand(), true)), 0, 24);
        }
    }
    return $cid;
}

function auditLog(string $nivel, string $categoria, string $evento, array $contexto = []): void {
    $logDir = dirname(AUDIT_LOG_PATH);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    $usuarioEmail = $_SESSION['usuario_email'] ?? null;
    $rol = $_SESSION['usuario_rol'] ?? 'anonimo';

    // Sanitizar contexto - NUNCA loguear datos sensibles
    $contexto = sanitizarContextoLog($contexto);

    // Inyectar correlation_id en el contexto para que quede en logs de archivo
    // (permite correlacionar eventos aun si la escritura a BD falla).
    $cid = correlationId();
    if (!isset($contexto['correlation_id'])) {
        $contexto['correlation_id'] = $cid;
    }

    $entrada = [
        'timestamp'      => date('Y-m-d H:i:s'),
        'nivel'          => $nivel,
        'categoria'      => $categoria,
        'evento'         => $evento,
        'ip'             => $ip,
        'usuario_id'     => $usuarioId,
        'usuario_email'  => $usuarioEmail ? enmascararEmail($usuarioEmail) : null,
        'rol'            => $rol,
        'modulo'         => $_GET['modulo'] ?? null,
        'accion'         => $_GET['accion'] ?? null,
        'metodo'         => $_SERVER['REQUEST_METHOD'] ?? null,
        'user_agent'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
        'correlation_id' => $cid,
        'contexto'       => $contexto,
    ];

    $linea = json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    // Escribir al log correspondiente según categoría
    $archivo = AUDIT_LOG_PATH;
    if (in_array($categoria, ['AUTH', 'SEGURIDAD', 'IDOR', 'CSRF', 'ACCESO_DENEGADO'])) {
        $archivo = SECURITY_LOG_PATH;
    } elseif ($categoria === 'ERROR') {
        $archivo = ERROR_LOG_PATH;
    } elseif ($categoria === 'ALERTA') {
        $archivo = ALERT_LOG_PATH;
    }

    @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);

    // También escribir al audit general si es security/error
    if ($archivo !== AUDIT_LOG_PATH) {
        @file_put_contents(AUDIT_LOG_PATH, $linea, FILE_APPEND | LOCK_EX);
    }

    // Persistir en BD (system_events) para la consola supergod
    auditLogToDB($entrada);
}

function auditLogToDB(array $entrada): void {
    // Estado compartido en la request: solo para *throttling* del error_log.
    // NO se usa como "fail-and-skip-forever" — cada evento se reintenta
    // independientemente contra la BD para no perder registros cuando el
    // error es transitorio (reconexion, lock contention).
    static $ultimoErrorAt = 0;
    static $erroresEnRequest = 0;

    try {
        $db = getDBConnection();
        $stmt = $db->prepare('
            INSERT INTO system_events (
                created_at, nivel, categoria, evento, descripcion,
                ip, usuario_id, usuario_email, rol,
                modulo, accion, metodo_http, ruta, user_agent,
                correlation_id, contexto
            ) VALUES (
                NOW(), :nivel, :categoria, :evento, :descripcion,
                :ip, :usuario_id, :usuario_email, :rol,
                :modulo, :accion, :metodo_http, :ruta, :user_agent,
                :correlation_id, :contexto
            )
        ');
        $stmt->execute([
            ':nivel'           => $entrada['nivel'] ?? 'INFO',
            ':categoria'       => $entrada['categoria'] ?? 'SISTEMA',
            ':evento'          => $entrada['evento'] ?? '',
            ':descripcion'     => substr($entrada['evento'] . ': ' . json_encode($entrada['contexto'] ?? [], JSON_UNESCAPED_UNICODE), 0, 2000),
            ':ip'              => $entrada['ip'] ?? null,
            ':usuario_id'      => $entrada['usuario_id'] ?? null,
            ':usuario_email'   => $entrada['usuario_email'] ?? null,
            ':rol'             => $entrada['rol'] ?? null,
            ':modulo'          => $entrada['modulo'] ?? null,
            ':accion'          => $entrada['accion'] ?? null,
            ':metodo_http'     => $entrada['metodo'] ?? null,
            ':ruta'            => isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 500) : null,
            ':user_agent'      => $entrada['user_agent'] ?? null,
            ':correlation_id'  => $entrada['correlation_id'] ?? null,
            ':contexto'        => json_encode($entrada['contexto'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $e) {
        // La escritura a archivo ya ocurrio en auditLog() antes de llamar
        // a esta funcion, asi que el evento NO se pierde.
        // Throttle de error_log: maximo 1 linea por minuto para evitar flood.
        $erroresEnRequest++;
        $ahora = time();
        if (($ahora - $ultimoErrorAt) >= 60) {
            $ultimoErrorAt = $ahora;
            @error_log(sprintf(
                '[REMTYS-AUDIT] system_events INSERT fallo (%d errores en request): %s',
                $erroresEnRequest,
                substr($e->getMessage(), 0, 300)
            ));
        }
    }
}

function sanitizarContextoLog(array $contexto): array {
    $sensibles = ['password', 'contrasena', 'token', 'csrf_token', 'session_id', 'cookie', 'db_pass', 'secret', 'hash'];
    $limpio = [];
    foreach ($contexto as $clave => $valor) {
        $claveMin = strtolower($clave);
        if (in_array($claveMin, $sensibles, true)) {
            $limpio[$clave] = '[REDACTED]';
        } elseif (is_string($valor) && strlen($valor) > 500) {
            $limpio[$clave] = substr($valor, 0, 100) . '...[TRUNCATED]';
        } else {
            $limpio[$clave] = $valor;
        }
    }
    return $limpio;
}

function enmascararEmail(string $email): string {
    $partes = explode('@', $email);
    if (count($partes) !== 2) return '***@***';
    $usuario = $partes[0];
    $dominio = $partes[1];
    $len = strlen($usuario);
    if ($len <= 2) return $usuario . '@' . $dominio;
    return substr($usuario, 0, 2) . str_repeat('*', min($len - 2, 5)) . '@' . $dominio;
}

// === Funciones de logging por categoría ===

function logAuth(string $evento, array $contexto = []): void {
    auditLog('INFO', 'AUTH', $evento, $contexto);
}

function logAuthFail(string $evento, array $contexto = []): void {
    auditLog('WARNING', 'AUTH', $evento, $contexto);
    verificarAlertaLoginFallido();
}

function logSeguridad(string $evento, array $contexto = []): void {
    auditLog('WARNING', 'SEGURIDAD', $evento, $contexto);
}

function logIDOR(string $evento, array $contexto = []): void {
    auditLog('CRITICAL', 'IDOR', $evento, $contexto);
    generarAlerta('IDOR_DETECTADO', $evento, $contexto);
}

function logCSRF(string $evento, array $contexto = []): void {
    auditLog('WARNING', 'CSRF', $evento, $contexto);
}

function logAccesoDenegado(string $evento, array $contexto = []): void {
    auditLog('WARNING', 'ACCESO_DENEGADO', $evento, $contexto);
    verificarAlertaAccesosDenegados();
}

function logDocumento(string $evento, array $contexto = []): void {
    auditLog('INFO', 'DOCUMENTO', $evento, $contexto);
}

function logAdmin(string $evento, array $contexto = []): void {
    auditLog('INFO', 'ADMIN', $evento, $contexto);
}

function logError(string $evento, array $contexto = []): void {
    auditLog('ERROR', 'ERROR', $evento, $contexto);
    verificarAlertaErrores();
}

function logPago(string $evento, array $contexto = []): void {
    auditLog('INFO', 'PAGO', $evento, $contexto);
}

// === Sistema de alertas ===

function generarAlerta(string $tipo, string $descripcion, array $contexto = []): void {
    $alerta = [
        'timestamp'   => date('Y-m-d H:i:s'),
        'tipo'        => $tipo,
        'descripcion' => $descripcion,
        'ip'          => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'usuario_id'  => $_SESSION['usuario_id'] ?? null,
        'contexto'    => sanitizarContextoLog($contexto),
    ];

    $linea = '[ALERTA] ' . json_encode($alerta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $logDir = dirname(ALERT_LOG_PATH);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    @file_put_contents(ALERT_LOG_PATH, $linea, FILE_APPEND | LOCK_EX);

    // También al error_log de PHP para integración con monitores externos
    error_log('[REMTYS-ALERTA] ' . $tipo . ': ' . $descripcion);
}

function verificarAlertaLoginFallido(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $clave = '_login_fails_' . md5($ip);
    $intentos = $_SESSION[$clave] ?? 0;
    $_SESSION[$clave] = $intentos + 1;

    if ($_SESSION[$clave] >= 5) {
        generarAlerta('BRUTE_FORCE_SOSPECHA', 'Multiples logins fallidos desde IP: ' . $ip, [
            'intentos' => $_SESSION[$clave],
        ]);
        $_SESSION[$clave] = 0; // Reset para siguiente ventana
    }
}

function verificarAlertaAccesosDenegados(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $clave = '_denied_' . md5($ip);
    $_SESSION[$clave] = ($_SESSION[$clave] ?? 0) + 1;

    if ($_SESSION[$clave] >= 3) {
        generarAlerta('ACCESOS_DENEGADOS_MULTIPLES', 'Multiples accesos denegados desde IP: ' . $ip, [
            'intentos' => $_SESSION[$clave],
        ]);
        $_SESSION[$clave] = 0;
    }
}

function verificarAlertaErrores(): void {
    $clave = '_err_count';
    $_SESSION[$clave] = ($_SESSION[$clave] ?? 0) + 1;

    if ($_SESSION[$clave] >= 5) {
        generarAlerta('ERRORES_REPETIDOS', 'Multiples errores en sesion actual', [
            'total' => $_SESSION[$clave],
        ]);
        $_SESSION[$clave] = 0;
    }
}
