<?php
declare(strict_types=1);

/**
 * Middleware pipeline - REMTYS
 *
 * Compone en una sola llamada las verificaciones transversales que antes se
 * repetian al inicio de cada accion (login, rol, metodo HTTP, CSRF, rate limit,
 * ownership). Los helpers individuales siguen disponibles como defense in depth
 * si una accion necesita validaciones adicionales.
 *
 * Uso tipico:
 *   runPipeline([
 *       'rol'        => 'contribuyente',   // login|contribuyente|admin|admin_o_revisor|supergod
 *       'metodo'     => 'POST',            // GET | POST | ANY (default: ANY)
 *       'csrf'       => true,              // valida csrf_token solo en POST
 *       'rate_limit' => 'login',           // clave logica, delega a rateLimitPorClave()
 *   ]);
 */

function runPipeline(array $opts): void {
    $rol        = $opts['rol']        ?? 'login';
    $metodo     = $opts['metodo']     ?? 'ANY';
    $csrf       = (bool)($opts['csrf'] ?? false);
    $rateLimit  = $opts['rate_limit'] ?? null;

    // 1) Metodo HTTP
    verificarMetodo($metodo);

    // 2) Rol / sesion
    switch ($rol) {
        case 'contribuyente':     requireContribuyente(); break;
        case 'admin':             requireAdmin();          break;
        case 'admin_o_revisor':   requireAdminORevisor();  break;
        case 'supergod':
            requireLogin();
            if (!isSuperGod()) {
                logAccesoDenegado('REQUIERE_SUPERGOD', ['rol_actual' => getRolActual()]);
                header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
                exit;
            }
            break;
        case 'ninguno':
            // Ruta publica (login, registro, recuperacion). No valida sesion.
            break;
        case 'login':
        default:
            requireLogin();
            break;
    }

    // 3) CSRF (solo si es POST, la funcion interna respeta eso)
    if ($csrf) {
        verificarCSRF();
    }

    // 4) Rate limit por clave (delega si existe el helper de persistencia)
    if ($rateLimit !== null && $rateLimit !== '' && function_exists('rateLimitPorClave')) {
        rateLimitPorClave((string)$rateLimit);
    }
}

/**
 * Exige que el metodo HTTP este dentro de la lista permitida.
 * $metodos puede ser 'GET', 'POST', 'ANY' o un arreglo ['GET','POST'].
 */
function verificarMetodo($metodos): void {
    if ($metodos === 'ANY' || $metodos === '' || $metodos === null) {
        return;
    }
    $permitidos = is_array($metodos) ? array_map('strtoupper', $metodos) : [strtoupper((string)$metodos)];
    $actual = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($actual, $permitidos, true)) {
        auditLog('WARNING', 'SEGURIDAD', 'METODO_NO_PERMITIDO', [
            'actual'     => $actual,
            'permitidos' => $permitidos,
            'modulo'     => $_GET['modulo'] ?? null,
            'accion'     => $_GET['accion'] ?? null,
        ]);
        http_response_code(405);
        header('Allow: ' . implode(', ', $permitidos));
        echo 'Metodo no permitido.';
        exit;
    }
}

/**
 * Verifica ownership ejecutando una consulta SELECT parametrizada. Si no hay
 * filas, se registra un intento IDOR y se redirige (o 404 si no hay URL).
 *
 *   requireOwnership($pdo,
 *       'SELECT 1 FROM predios WHERE id = :id AND contribuyente_id = :cid',
 *       [':id' => $predioId, ':cid' => getContribuyenteId()],
 *       'IDOR_PREDIO',
 *       BASE_URL . '/?modulo=contribuyente&accion=predios'
 *   );
 *
 * Devuelve la primera fila si existe (util para capturar datos en una sola query).
 * Los administradores y supergod bypassan ownership por diseno.
 */
function requireOwnership(PDO $pdo, string $sql, array $params, string $logEvento, ?string $redirectUrl = null): array {
    if (isSuperGod() || isAdmin()) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila !== false ? $fila : [];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila === false) {
        $paramsLog = [];
        foreach ($params as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $paramsLog[$k] = is_string($v) ? substr($v, 0, 64) : $v;
            }
        }
        auditLog('WARNING', 'IDOR', $logEvento, [
            'usuario_id'       => $_SESSION['usuario_id'] ?? null,
            'contribuyente_id' => getContribuyenteId(),
            'rol'              => getRolActual(),
            'params'           => $paramsLog,
        ]);

        if ($redirectUrl !== null && $redirectUrl !== '') {
            setFlash('error', 'No tiene acceso al recurso solicitado.');
            header('Location: ' . $redirectUrl);
            exit;
        }
        http_response_code(404);
        require APP_PATH . '/views/shared/404.php';
        exit;
    }

    return $fila;
}

// ============================================================================
// Rate limit por IP persistente (tabla rate_limits, migracion 011)
// ============================================================================

/**
 * Perfiles por clave logica. Cada entrada define:
 *   max_intentos: intentos permitidos dentro de la ventana antes de bloquear
 *   ventana_seg:  duracion de la ventana deslizante en segundos
 *   bloqueo_seg:  duracion del bloqueo tras agotar intentos
 */
function rateLimitPerfil(string $clave): array {
    $perfiles = [
        'login'                 => ['max_intentos' => 10, 'ventana_seg' => 300,  'bloqueo_seg' => 900],
        'password_reset'        => ['max_intentos' => 5,  'ventana_seg' => 3600, 'bloqueo_seg' => 3600],
        'register'              => ['max_intentos' => 5,  'ventana_seg' => 3600, 'bloqueo_seg' => 3600],
        'email_verify_resend'   => ['max_intentos' => 5,  'ventana_seg' => 600,  'bloqueo_seg' => 1800],
    ];
    return $perfiles[$clave] ?? ['max_intentos' => 20, 'ventana_seg' => 600, 'bloqueo_seg' => 600];
}

function rateLimitIpActual(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '0.0.0.0';
    }
    return $ip;
}

/**
 * Verifica si la IP esta actualmente bloqueada para esa clave. Si lo esta,
 * aborta con 429 y log. Si no, refresca la ventana deslizante.
 * Se invoca desde runPipeline() cuando 'rate_limit' esta presente.
 */
function rateLimitPorClave(string $clave): void {
    $perfil = rateLimitPerfil($clave);
    $ip     = rateLimitIpActual();

    try {
        $db = getDBConnection();
    } catch (Throwable $e) {
        // Si BD cae, no bloqueamos trafico legitimo; solo log.
        auditLog('ERROR', 'SEGURIDAD', 'RATE_LIMIT_DB_UNAVAILABLE', ['clave' => $clave]);
        return;
    }

    try {
        $stmt = $db->prepare(
            'SELECT contador, ventana_inicio, bloqueado_hasta
             FROM rate_limits
             WHERE clave = :clave AND ip = :ip'
        );
        $stmt->execute([':clave' => $clave, ':ip' => $ip]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila && !empty($fila['bloqueado_hasta'])
            && strtotime((string)$fila['bloqueado_hasta']) > time()) {
            auditLog('WARNING', 'SEGURIDAD', 'RATE_LIMIT_BLOQUEADO', [
                'clave'           => $clave,
                'ip'              => $ip,
                'bloqueado_hasta' => $fila['bloqueado_hasta'],
            ]);
            http_response_code(429);
            header('Retry-After: ' . max(1, strtotime((string)$fila['bloqueado_hasta']) - time()));
            echo 'Demasiados intentos. Intente de nuevo mas tarde.';
            exit;
        }
    } catch (Throwable $e) {
        auditLog('ERROR', 'SEGURIDAD', 'RATE_LIMIT_QUERY_ERROR', [
            'clave' => $clave,
            'error' => substr($e->getMessage(), 0, 200),
        ]);
        return;
    }
}

/**
 * Registra un intento fallido para (clave, ip). Si excede max_intentos en la
 * ventana, marca bloqueado_hasta. Idempotente: reinicia la ventana si esta
 * caducada.
 */
function rateLimitRegistrarFallo(string $clave): void {
    $perfil = rateLimitPerfil($clave);
    $ip     = rateLimitIpActual();

    try {
        $db = getDBConnection();
    } catch (Throwable $e) {
        return;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            'SELECT contador, ventana_inicio, bloqueado_hasta
             FROM rate_limits
             WHERE clave = :clave AND ip = :ip
             FOR UPDATE'
        );
        $stmt->execute([':clave' => $clave, ':ip' => $ip]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        $ahora    = time();
        $ventana  = (int)$perfil['ventana_seg'];
        $maxInt   = (int)$perfil['max_intentos'];
        $bloqSeg  = (int)$perfil['bloqueo_seg'];

        if (!$fila) {
            $insert = $db->prepare(
                'INSERT INTO rate_limits (clave, ip, ventana_inicio, contador, ultimo_intento)
                 VALUES (:clave, :ip, NOW(), 1, NOW())
                 ON CONFLICT (clave, ip) DO UPDATE
                    SET contador        = rate_limits.contador + 1,
                        ultimo_intento  = NOW()'
            );
            $insert->execute([':clave' => $clave, ':ip' => $ip]);
            $db->commit();
            return;
        }

        $inicioVentana = strtotime((string)$fila['ventana_inicio']);
        $contadorPrev  = (int)$fila['contador'];

        if (($ahora - $inicioVentana) > $ventana) {
            // Ventana caducada: reinicio de contador
            $upd = $db->prepare(
                'UPDATE rate_limits
                 SET contador        = 1,
                     ventana_inicio  = NOW(),
                     ultimo_intento  = NOW(),
                     bloqueado_hasta = NULL
                 WHERE clave = :clave AND ip = :ip'
            );
            $upd->execute([':clave' => $clave, ':ip' => $ip]);
            $db->commit();
            return;
        }

        $nuevoContador = $contadorPrev + 1;
        $bloqueadoHasta = null;

        if ($nuevoContador >= $maxInt) {
            $bloqueadoHasta = date('Y-m-d H:i:sP', $ahora + $bloqSeg);
            auditLog('ALERTA', 'SEGURIDAD', 'RATE_LIMIT_BLOQUEO_ACTIVADO', [
                'clave'           => $clave,
                'ip'              => $ip,
                'intentos'        => $nuevoContador,
                'bloqueado_hasta' => $bloqueadoHasta,
            ]);
        }

        $upd = $db->prepare(
            'UPDATE rate_limits
             SET contador        = :c,
                 ultimo_intento  = NOW(),
                 bloqueado_hasta = :bh
             WHERE clave = :clave AND ip = :ip'
        );
        $upd->execute([
            ':c'     => $nuevoContador,
            ':bh'    => $bloqueadoHasta,
            ':clave' => $clave,
            ':ip'    => $ip,
        ]);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        auditLog('ERROR', 'SEGURIDAD', 'RATE_LIMIT_REGISTRAR_ERROR', [
            'clave' => $clave,
            'error' => substr($e->getMessage(), 0, 200),
        ]);
    }
}

/**
 * Limpia el contador para (clave, ip) tras un exito autenticado.
 * Evita que intentos legitimos con tipeo humano cuenten hacia bloqueo futuro.
 */
function rateLimitLimpiarExito(string $clave): void {
    $ip = rateLimitIpActual();
    try {
        $db = getDBConnection();
        $stmt = $db->prepare('DELETE FROM rate_limits WHERE clave = :clave AND ip = :ip');
        $stmt->execute([':clave' => $clave, ':ip' => $ip]);
    } catch (Throwable $e) {
        // Silencioso: la limpieza es best-effort.
    }
}
