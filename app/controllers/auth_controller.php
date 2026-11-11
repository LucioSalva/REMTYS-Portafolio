<?php
/**
 * Controlador de Autenticacion
 */

function accion_login(): void {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    renderLoginPage();
}

function accion_procesar_login(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }
    verificarCSRF();

    // Rate limit por IP persistente (migra 011). Aborta 429 si la IP esta bloqueada.
    rateLimitPorClave('login');

    $email = sanitizarEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        renderLoginPage('Correo y contrasena son requeridos.');
        return;
    }

    $db = getDBConnection();
    $usuario = obtenerUsuarioPorEmail($db, $email);

    if (!$usuario) {
        rateLimitRegistrarFallo('login');
        logAuthFail('LOGIN_USUARIO_NO_ENCONTRADO', ['email_intento' => $email]);
        renderLoginPage('Credenciales incorrectas.');
        return;
    }

    if ($usuario['bloqueado_hasta'] && new DateTimeImmutable($usuario['bloqueado_hasta']) > new DateTimeImmutable()) {
        rateLimitRegistrarFallo('login');
        logAuthFail('LOGIN_CUENTA_BLOQUEADA', ['usuario_id' => $usuario['id']]);
        renderLoginPage('Cuenta temporalmente bloqueada por multiples intentos fallidos. Intente mas tarde.');
        return;
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        $intentos = (int)$usuario['intentos_fallidos'] + 1;
        $bloqueado = null;
        if ($intentos >= 5) {
            $bloqueado = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $intentos = 0;
            generarAlerta('CUENTA_BLOQUEADA', 'Cuenta bloqueada por intentos fallidos', ['usuario_id' => $usuario['id']]);
        }

        $upd = $db->prepare('UPDATE usuarios SET intentos_fallidos = :i, bloqueado_hasta = :b WHERE id = :id');
        $upd->execute([':i' => $intentos, ':b' => $bloqueado, ':id' => $usuario['id']]);

        rateLimitRegistrarFallo('login');
        logAuthFail('LOGIN_PASSWORD_INCORRECTO', ['usuario_id' => $usuario['id'], 'intentos' => $intentos]);
        renderLoginPage('Credenciales incorrectas.');
        return;
    }

    if (!valorBooleano($usuario['email_verificado'])) {
        renderLoginPage(null, $email, 'Su cuenta existe, pero el correo aun no ha sido verificado.');
        return;
    }

    $upd = $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id');
    $upd->execute([':id' => $usuario['id']]);

    rateLimitLimpiarExito('login');
    loginUsuario($usuario);
    logAuth('LOGIN_EXITOSO', ['usuario_id' => $usuario['id'], 'rol' => $usuario['rol']]);
    setFlash('exito', 'Bienvenido, ' . $usuario['nombre_completo']);

    if (in_array($usuario['rol'], ['admin', 'revisor', 'supergod'], true)) {
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=dashboard');
    } else {
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=dashboard');
    }
    exit;
}

function accion_registro(): void {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    $error = null;
    require APP_PATH . '/views/shared/registro.php';
}

function accion_procesar_registro(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=registro');
        exit;
    }
    verificarCSRF();

    // Rate limit por IP persistente (migra 011). Aborta 429 si la IP esta bloqueada.
    rateLimitPorClave('register');

    $errores = [];

    $nombre = sanitizarTexto($_POST['nombre_completo'] ?? '');
    $email = sanitizarEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $rfc = sanitizarRFC($_POST['rfc'] ?? '');
    $tipo_persona = in_array($_POST['tipo_persona'] ?? '', ['fisica', 'moral'], true) ? $_POST['tipo_persona'] : 'fisica';
    $detalleContribuyente = normalizarDetalleContribuyente($_POST);
    $telefono = sanitizarTexto($_POST['telefono'] ?? '');
    $calle = sanitizarTexto($_POST['calle'] ?? '');
    $numero_ext = sanitizarTexto($_POST['numero_exterior'] ?? '');
    $colonia = sanitizarTexto($_POST['colonia'] ?? '');
    $municipio = sanitizarTexto($_POST['municipio'] ?? '');
    $estado_dom = sanitizarTexto($_POST['estado'] ?? '');
    $cp = sanitizarTexto($_POST['codigo_postal'] ?? '');

    if (estaVacio($nombre) || !validarLongitudTexto($nombre, 3, 400)) {
        $errores[] = 'El nombre completo es requerido (3-400 caracteres).';
    }
    if (!$email) {
        $errores[] = 'El correo electronico no es valido.';
    }
    $errores = array_merge($errores, validarPasswordSegura($password));
    if ($password !== $passwordConfirm) {
        $errores[] = 'Las contrasenas no coinciden.';
    }
    if (estaVacio($rfc) || !validarRFC($rfc)) {
        $errores[] = 'El RFC no tiene un formato valido.';
    }
    if ($cp !== '' && !validarCP($cp)) {
        $errores[] = 'El codigo postal debe tener 5 digitos.';
    }
    $errores = array_merge($errores, validarDetalleContribuyente($tipo_persona, $detalleContribuyente));

    if (!empty($errores)) {
        $error = implode('<br>', $errores);
        require APP_PATH . '/views/shared/registro.php';
        return;
    }

    $db = getDBConnection();

    $chkEmail = $db->prepare('SELECT id, email_verificado FROM usuarios WHERE email = :email LIMIT 1');
    $chkEmail->execute([':email' => $email]);
    $usuarioExistente = $chkEmail->fetch();
    if ($usuarioExistente) {
        $error = valorBooleano($usuarioExistente['email_verificado'])
            ? 'El correo electronico ya esta registrado.'
            : 'El correo ya fue registrado y esta pendiente de verificacion. Inicie sesion para reenviar el correo de verificacion.';
        require APP_PATH . '/views/shared/registro.php';
        return;
    }

    $chkRFC = $db->prepare('SELECT id FROM contribuyentes WHERE rfc = :rfc');
    $chkRFC->execute([':rfc' => $rfc]);
    if ($chkRFC->fetch()) {
        $error = 'El RFC ya esta registrado en el sistema.';
        require APP_PATH . '/views/shared/registro.php';
        return;
    }

    $verificacion = crearDatosVerificacionCorreo();

    try {
        $db->beginTransaction();

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $insUser = $db->prepare("INSERT INTO usuarios (email, password_hash, nombre_completo, rol, email_verificado, email_verificacion_token_hash, email_verificacion_expires_at, ultimo_envio_verificacion_at) VALUES (:email, :hash, :nombre, 'contribuyente', FALSE, :token_hash, :token_expires, NOW()) RETURNING id");
        $insUser->execute([
            ':email' => $email,
            ':hash' => $hash,
            ':nombre' => $nombre,
            ':token_hash' => $verificacion['token_hash'],
            ':token_expires' => $verificacion['expires_at'],
        ]);
        $usuarioId = (int)$insUser->fetchColumn();

        $insContrib = $db->prepare('
            INSERT INTO contribuyentes (usuario_id, tipo_persona, nombre_completo, rfc, calle, numero_exterior, colonia, municipio, estado, codigo_postal, telefono, email_contacto)
            VALUES (:uid, :tp, :nombre, :rfc, :calle, :nex, :col, :mun, :est, :cp, :tel, :email)
            RETURNING id
        ');
        $insContrib->execute([
            ':uid' => $usuarioId,
            ':tp' => $tipo_persona,
            ':nombre' => $nombre,
            ':rfc' => $rfc,
            ':calle' => $calle ?: null,
            ':nex' => $numero_ext ?: null,
            ':col' => $colonia ?: null,
            ':mun' => $municipio ?: null,
            ':est' => $estado_dom ?: null,
            ':cp' => $cp ?: null,
            ':tel' => $telefono ?: null,
            ':email' => $email,
        ]);
        $contribuyenteId = (int)$insContrib->fetchColumn();
        guardarDetalleContribuyente($db, $contribuyenteId, $tipo_persona, $detalleContribuyente);

        $db->commit();

        logAuth('USER_REGISTERED', ['usuario_id' => $usuarioId, 'email' => $email, 'tipo_persona' => $tipo_persona]);

        $correoEnviado = enviarVerificacionAUsuario([
            'id' => $usuarioId,
            'email' => $email,
            'nombre_completo' => $nombre,
        ], $verificacion['token']);
        if ($correoEnviado) {
            auditLog('SUCCESS', 'MAIL', 'EMAIL_VERIFICATION_SENT', ['usuario_id' => $usuarioId, 'email' => $email]);
            setFlash('exito', 'Registro exitoso. Enviamos un correo de verificacion a la direccion capturada.');
        } else {
            auditLog('ERROR', 'MAIL', 'EMAIL_VERIFICATION_FAILED', ['usuario_id' => $usuarioId, 'email' => $email]);
            setFlash('advertencia', 'Registro exitoso, pero no fue posible entregar el correo de verificacion. Desde inicio de sesion podra reenviarlo.');
        }

        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&pendiente=1&email=' . urlencode($email));
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('USER_REGISTER_FAILED', ['email' => $email, 'error' => $e->getMessage()]);
        $error = 'Error interno al procesar el registro. Intente nuevamente.';
        require APP_PATH . '/views/shared/registro.php';
    }
}

function accion_verificar_email(): void {
    $token = trim((string)($_GET['token'] ?? ''));
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        auditLog('WARNING', 'AUTH', 'EMAIL_VERIFICATION_INVALID_TOKEN', ['motivo' => 'formato invalido']);
        setFlash('error', 'El enlace de verificacion no es valido.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }

    $db = getDBConnection();
    $stmt = $db->prepare('SELECT id, email, email_verificado, email_verificacion_expires_at FROM usuarios WHERE email_verificacion_token_hash = :token LIMIT 1');
    $stmt->execute([':token' => hash('sha256', $token)]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        auditLog('WARNING', 'AUTH', 'EMAIL_VERIFICATION_TOKEN_NOT_FOUND', ['motivo' => 'token no existe en BD']);
        setFlash('error', 'El enlace de verificacion es invalido, ya expiro o fue reemplazado por uno mas reciente.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }

    if (valorBooleano($usuario['email_verificado'])) {
        setFlash('info', 'Su correo ya estaba verificado. Puede iniciar sesion.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&email=' . urlencode($usuario['email']));
        exit;
    }

    $ahora = new DateTimeImmutable();
    $expira = !empty($usuario['email_verificacion_expires_at']) ? new DateTimeImmutable($usuario['email_verificacion_expires_at']) : null;
    if ($expira && $expira < $ahora) {
        $clear = $db->prepare('UPDATE usuarios SET email_verificacion_token_hash = NULL, email_verificacion_expires_at = NULL WHERE id = :id');
        $clear->execute([':id' => $usuario['id']]);
        auditLog('WARNING', 'AUTH', 'EMAIL_VERIFICATION_TOKEN_EXPIRED', ['usuario_id' => $usuario['id'], 'email' => $usuario['email']]);
        setFlash('advertencia', 'El enlace de verificacion ya vencio. Solicite uno nuevo para activar su cuenta.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&pendiente=1&email=' . urlencode($usuario['email']));
        exit;
    }

    $upd = $db->prepare('UPDATE usuarios SET email_verificado = TRUE, email_verificado_at = NOW(), email_verificacion_token_hash = NULL, email_verificacion_expires_at = NULL WHERE id = :id');
    $upd->execute([':id' => $usuario['id']]);

    auditLog('SUCCESS', 'AUTH', 'EMAIL_VERIFIED', ['usuario_id' => $usuario['id'], 'email' => $usuario['email']]);
    setFlash('exito', 'Correo verificado correctamente. Ya puede iniciar sesion.');
    header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&email=' . urlencode($usuario['email']));
    exit;
}

function accion_reenviar_verificacion(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }
    verificarCSRF();

    $email = sanitizarEmail($_POST['email'] ?? '');
    if (!$email) {
        setFlash('error', 'Indique un correo electronico valido.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }

    $db = getDBConnection();
    $usuario = obtenerUsuarioPorEmail($db, $email);
    if (!$usuario) {
        setFlash('info', 'Si la cuenta existe y sigue pendiente, enviaremos un nuevo correo de verificacion.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&pendiente=1&email=' . urlencode($email));
        exit;
    }

    if (valorBooleano($usuario['email_verificado'])) {
        setFlash('info', 'El correo ya esta verificado. Puede iniciar sesion.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&email=' . urlencode($email));
        exit;
    }

    $segundosRestantes = segundosRestantesReenvio($usuario);
    if ($segundosRestantes > 0) {
        $minutos = (int)ceil($segundosRestantes / 60);
        setFlash('advertencia', 'Ya enviamos un correo recientemente. Espere ' . $minutos . ' minuto(s) antes de reenviar.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&pendiente=1&email=' . urlencode($email));
        exit;
    }

    $verificacion = renovarVerificacionCorreo($db, (int)$usuario['id']);
    $correoEnviado = enviarVerificacionAUsuario($usuario, $verificacion['token']);

    if ($correoEnviado) {
        auditLog('SUCCESS', 'MAIL', 'EMAIL_VERIFICATION_RESENT', ['usuario_id' => $usuario['id'], 'email' => $email]);
        setFlash('exito', 'Reenviamos el correo de verificacion a ' . $email . '.');
    } else {
        auditLog('ERROR', 'MAIL', 'EMAIL_VERIFICATION_RESEND_FAILED', ['usuario_id' => $usuario['id'], 'email' => $email]);
        setFlash('advertencia', 'No fue posible entregar el correo de verificacion. Revise la configuracion de correo e intente nuevamente.');
    }

    header('Location: ' . BASE_URL . '/?modulo=auth&accion=login&pendiente=1&email=' . urlencode($email));
    exit;
}

function accion_logout(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }
    verificarCSRF();
    logAuth('LOGOUT', ['usuario_id' => $_SESSION['usuario_id'] ?? null]);
    logoutUsuario();
    setFlash('info', 'Ha cerrado sesion correctamente.');
    header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
    exit;
}

function accion_acceso_denegado(): void {
    http_response_code(403);
    require APP_PATH . '/views/shared/403.php';
}

// ─────────────────────────────────────────────────────────────────────────
// Recuperación de contraseña — flujo completo
// Tabla password_reset_tokens (migration 010)
// ─────────────────────────────────────────────────────────────────────────

const PASSWORD_RESET_TOKEN_TTL_HOURS = 1;
const PASSWORD_RESET_MAX_SOLICITUDES_HORA = 3;

function accion_recuperar_password(): void {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
    $error = null;
    $info  = null;
    require APP_PATH . '/views/shared/recuperar_password.php';
}

function accion_procesar_recuperacion(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }
    verificarCSRF();

    // Rate limit por IP persistente (migra 011). Impide enumeracion masiva.
    rateLimitPorClave('password_reset');

    $email = sanitizarEmail($_POST['email'] ?? '');
    $ip    = $_SERVER['REMOTE_ADDR'] ?? null;

    // Siempre devolvemos el mismo mensaje genérico: NO leak de existencia de usuario.
    $mensajeGenerico = 'Si la cuenta existe y está activa, enviamos un correo con instrucciones para restablecer la contrasena.';

    if (!$email) {
        rateLimitRegistrarFallo('password_reset');
        setFlash('error', 'Indique un correo electronico valido.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }

    $db = getDBConnection();

    // Rate limit por usuario (si existe): máximo N solicitudes por hora.
    $stmtUsr = $db->prepare('SELECT id, nombre_completo, email, activo, email_verificado FROM usuarios WHERE email = :email LIMIT 1');
    $stmtUsr->execute([':email' => $email]);
    $usuario = $stmtUsr->fetch(PDO::FETCH_ASSOC);

    if ($usuario && valorBooleano($usuario['activo']) && valorBooleano($usuario['email_verificado'])) {
        $stmtCount = $db->prepare('
            SELECT COUNT(*) FROM password_reset_tokens
            WHERE usuario_id = :uid
              AND created_at > NOW() - INTERVAL \'1 hour\'
        ');
        $stmtCount->execute([':uid' => $usuario['id']]);
        $reciente = (int)$stmtCount->fetchColumn();

        if ($reciente < PASSWORD_RESET_MAX_SOLICITUDES_HORA) {
            try {
                $db->beginTransaction();

                // Invalidar tokens previos activos del usuario (defensa en profundidad:
                // si un atacante con correo comprometido generó uno, el usuario real lo anula).
                $stmtInv = $db->prepare('
                    UPDATE password_reset_tokens
                    SET invalidated_at = NOW()
                    WHERE usuario_id = :uid
                      AND used_at IS NULL
                      AND invalidated_at IS NULL
                      AND expires_at > NOW()
                ');
                $stmtInv->execute([':uid' => $usuario['id']]);

                // Nuevo token: 32 bytes random → hex 64 chars; se envía el hex al usuario,
                // en BD se guarda SHA-256 del hex (no reversible).
                $tokenPlano = bin2hex(random_bytes(32));
                $tokenHash  = hash('sha256', $tokenPlano);
                $expiresAt  = (new DateTimeImmutable('+' . PASSWORD_RESET_TOKEN_TTL_HOURS . ' hours'))->format('Y-m-d H:i:sP');

                $stmtIns = $db->prepare('
                    INSERT INTO password_reset_tokens
                        (usuario_id, token_hash, expires_at, ip_solicitud)
                    VALUES
                        (:uid, :hash, :expires, :ip)
                ');
                $stmtIns->execute([
                    ':uid'     => $usuario['id'],
                    ':hash'    => $tokenHash,
                    ':expires' => $expiresAt,
                    ':ip'      => $ip ? substr((string)$ip, 0, 45) : null,
                ]);

                $db->commit();

                $url = obtenerBaseUrlAbsoluta() . '/?modulo=auth&accion=resetear_password&token=' . urlencode($tokenPlano);
                $ok  = enviarCorreoRecuperacionPassword($usuario['email'], $usuario['nombre_completo'] ?? '', $url, PASSWORD_RESET_TOKEN_TTL_HOURS);

                if ($ok) {
                    auditLog('SUCCESS', 'MAIL', 'PASSWORD_RESET_REQUESTED', ['usuario_id' => $usuario['id']]);
                } else {
                    auditLog('ERROR', 'MAIL', 'PASSWORD_RESET_MAIL_FAILED', ['usuario_id' => $usuario['id']]);
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                logError('PASSWORD_RESET_REQUEST_FAILED', [
                    'usuario_id' => $usuario['id'] ?? null,
                    'error'      => substr($e->getMessage(), 0, 300),
                ]);
            }
        } else {
            auditLog('WARNING', 'AUTH', 'PASSWORD_RESET_RATE_LIMIT', [
                'usuario_id' => $usuario['id'],
                'solicitudes_ultima_hora' => $reciente,
            ]);
        }
    } else {
        // No existe, inactivo o no verificado — no distinguir al cliente.
        rateLimitRegistrarFallo('password_reset');
        auditLog('INFO', 'AUTH', 'PASSWORD_RESET_UNKNOWN_EMAIL', ['email_hash' => substr(hash('sha256', $email), 0, 16)]);
    }

    setFlash('info', $mensajeGenerico);
    header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
    exit;
}

function accion_resetear_password(): void {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    $tokenPlano = trim((string)($_GET['token'] ?? ''));
    if ($tokenPlano === '' || !preg_match('/^[a-f0-9]{64}$/i', $tokenPlano)) {
        auditLog('WARNING', 'AUTH', 'PASSWORD_RESET_TOKEN_MALFORMED', ['motivo' => 'formato']);
        setFlash('error', 'El enlace de restablecimiento es invalido.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }

    $valido = passwordResetTokenValido($tokenPlano);
    if (!$valido) {
        setFlash('error', 'El enlace es invalido, ya fue usado o expiro. Solicite uno nuevo.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }

    $token = $tokenPlano;   // Se re-envía en el form para procesar_reset
    $error = null;
    require APP_PATH . '/views/shared/resetear_password.php';
}

function accion_procesar_reset(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }
    verificarCSRF();

    $tokenPlano     = trim((string)($_POST['token'] ?? ''));
    $password       = (string)($_POST['password'] ?? '');
    $passwordConfirm= (string)($_POST['password_confirm'] ?? '');
    $ip             = $_SERVER['REMOTE_ADDR'] ?? null;

    if ($tokenPlano === '' || !preg_match('/^[a-f0-9]{64}$/i', $tokenPlano)) {
        setFlash('error', 'Token invalido.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }

    $registro = passwordResetTokenValido($tokenPlano);
    if (!$registro) {
        setFlash('error', 'El enlace es invalido, ya fue usado o expiro. Solicite uno nuevo.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }

    $errores = validarPasswordSegura($password);
    if ($password !== $passwordConfirm) {
        $errores[] = 'Las contrasenas no coinciden.';
    }

    if (!empty($errores)) {
        $error = implode('<br>', $errores);
        $token = $tokenPlano;
        require APP_PATH . '/views/shared/resetear_password.php';
        return;
    }

    $db = getDBConnection();
    try {
        $db->beginTransaction();

        // Re-check bajo lock para evitar race (doble-uso del mismo token).
        $stmtLock = $db->prepare('
            SELECT id, usuario_id, used_at, invalidated_at, expires_at
            FROM password_reset_tokens
            WHERE token_hash = :hash
            FOR UPDATE
        ');
        $stmtLock->execute([':hash' => hash('sha256', $tokenPlano)]);
        $tokRow = $stmtLock->fetch(PDO::FETCH_ASSOC);

        if ($tokRow === false
            || $tokRow['used_at'] !== null
            || $tokRow['invalidated_at'] !== null
            || new DateTimeImmutable($tokRow['expires_at']) <= new DateTimeImmutable()
        ) {
            $db->rollBack();
            auditLog('WARNING', 'AUTH', 'PASSWORD_RESET_TOKEN_CONSUMED_OR_EXPIRED', [
                'token_id' => $tokRow['id'] ?? null,
            ]);
            setFlash('error', 'El enlace ya no es valido. Solicite uno nuevo.');
            header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
            exit;
        }

        // Actualizar contrasena + limpiar bloqueo por intentos fallidos previos.
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmtUpd = $db->prepare('
            UPDATE usuarios
            SET password_hash      = :hash,
                intentos_fallidos  = 0,
                bloqueado_hasta    = NULL,
                password_updated_at= NOW()
            WHERE id = :id
        ');
        $stmtUpd->execute([':hash' => $hash, ':id' => $tokRow['usuario_id']]);

        // Marcar token como usado.
        $stmtUse = $db->prepare('
            UPDATE password_reset_tokens
            SET used_at = NOW(),
                ip_uso  = :ip
            WHERE id = :id
        ');
        $stmtUse->execute([
            ':ip' => $ip ? substr((string)$ip, 0, 45) : null,
            ':id' => $tokRow['id'],
        ]);

        // Invalidar cualquier otro token pendiente del mismo usuario.
        $stmtInv = $db->prepare('
            UPDATE password_reset_tokens
            SET invalidated_at = NOW()
            WHERE usuario_id = :uid
              AND id <> :id
              AND used_at IS NULL
              AND invalidated_at IS NULL
        ');
        $stmtInv->execute([':uid' => $tokRow['usuario_id'], ':id' => $tokRow['id']]);

        $db->commit();

        auditLog('SUCCESS', 'AUTH', 'PASSWORD_RESET_DONE', ['usuario_id' => $tokRow['usuario_id']]);
        setFlash('exito', 'Su contrasena fue actualizada. Ya puede iniciar sesion.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('PASSWORD_RESET_APPLY_FAILED', [
            'error' => substr($e->getMessage(), 0, 300),
        ]);
        setFlash('error', 'No fue posible actualizar la contrasena. Intente nuevamente.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=recuperar_password');
        exit;
    }
}

/**
 * Devuelve la fila del token si es válido (no usado, no invalidado, no expirado),
 * o null en caso contrario.
 *
 * @return array<string,mixed>|null
 */
function passwordResetTokenValido(string $tokenPlano): ?array {
    $db = getDBConnection();
    $stmt = $db->prepare('
        SELECT id, usuario_id, expires_at, used_at, invalidated_at
        FROM password_reset_tokens
        WHERE token_hash = :hash
          AND used_at IS NULL
          AND invalidated_at IS NULL
          AND expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([':hash' => hash('sha256', $tokenPlano)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function obtenerUsuarioPorEmail(PDO $db, string $email): ?array {
    $sql = 'SELECT u.*, c.id AS contribuyente_id FROM usuarios u LEFT JOIN contribuyentes c ON c.usuario_id = u.id WHERE u.email = :email AND u.activo = TRUE LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();
    return $usuario ?: null;
}

function renderLoginPage(?string $error = null, ?string $correoPendienteVerificacion = null, ?string $mensajePendiente = null): void {
    $correoDesdeQuery = (($_GET['pendiente'] ?? '') === '1') ? sanitizarEmail($_GET['email'] ?? '') : null;
    if ($correoPendienteVerificacion === null && $correoDesdeQuery) {
        $correoPendienteVerificacion = $correoDesdeQuery;
    }

    if ($mensajePendiente === null && $correoPendienteVerificacion) {
        $mensajePendiente = 'Su cuenta esta pendiente de verificacion. Revise su bandeja de entrada para activar el acceso.';
    }

    $prefillEmail = sanitizarEmail($_POST['email'] ?? '')
        ?? $correoPendienteVerificacion
        ?? sanitizarEmail($_GET['email'] ?? '')
        ?? '';

    require APP_PATH . '/views/shared/login.php';
}

function crearDatosVerificacionCorreo(): array {
    $token = bin2hex(random_bytes(32));
    $expira = (new DateTimeImmutable('+' . MAIL_VERIFY_TOKEN_HOURS . ' hours'))->format('Y-m-d H:i:sP');

    return [
        'token' => $token,
        'token_hash' => hash('sha256', $token),
        'expires_at' => $expira,
    ];
}

function construirUrlVerificacionCorreo(string $token): string {
    return obtenerBaseUrlAbsoluta() . '/?modulo=auth&accion=verificar_email&token=' . urlencode($token);
}

function segundosRestantesReenvio(array $usuario): int {
    if (empty($usuario['ultimo_envio_verificacion_at'])) {
        return 0;
    }

    $ultimoEnvio = new DateTimeImmutable($usuario['ultimo_envio_verificacion_at']);
    $proximoEnvio = $ultimoEnvio->modify('+' . MAIL_VERIFY_RESEND_SECONDS . ' seconds');
    return max(0, $proximoEnvio->getTimestamp() - time());
}

function renovarVerificacionCorreo(PDO $db, int $usuarioId): array {
    $verificacion = crearDatosVerificacionCorreo();
    $stmt = $db->prepare('UPDATE usuarios SET email_verificacion_token_hash = :token_hash, email_verificacion_expires_at = :token_expires, ultimo_envio_verificacion_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':token_hash' => $verificacion['token_hash'],
        ':token_expires' => $verificacion['expires_at'],
        ':id' => $usuarioId,
    ]);

    return $verificacion;
}

function enviarVerificacionAUsuario(array $usuario, string $token): bool {
    $url = construirUrlVerificacionCorreo($token);
    $ok = enviarCorreoVerificacion($usuario['email'], $usuario['nombre_completo'] ?? '', $url);
    if (!$ok) {
        registrarEventoCorreo('No se pudo entregar el correo de verificacion a ' . $usuario['email'] . '. El usuario debe solicitar reenvio desde el login.');
    }

    return $ok;
}

function valorBooleano(mixed $valor): bool {
    return in_array($valor, [true, 1, '1', 't', 'true', 'TRUE'], true);
}


