<?php
/**
 * Helper de Autenticación y Sesiones
 */

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_only_cookies', 1);
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        ini_set('session.cookie_secure', $isSecure ? 1 : 0);
        ini_set('session.gc_maxlifetime', 3600);
        ini_set('session.cookie_lifetime', 0);
        session_start();

        // Idle timeout: 30 minutos
        $idleLimit = 1800;
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $idleLimit)) {
            logAuth('SESION_EXPIRADA_INACTIVIDAD');
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['_expired'] = true;
            return;
        }
        $_SESSION['_last_activity'] = time();

        // Timeout absoluto: 8 horas
        if (isset($_SESSION['_created_at']) && (time() - $_SESSION['_created_at'] > 28800)) {
            logAuth('SESION_EXPIRADA_ABSOLUTA');
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['_expired'] = true;
            return;
        }
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        }
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function getUsuarioActual(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
        'email'  => $_SESSION['usuario_email'] ?? '',
        'rol'    => $_SESSION['usuario_rol'] ?? '',
    ];
}

function getRolActual(): string {
    return $_SESSION['usuario_rol'] ?? '';
}

function isSuperGod(): bool {
    return getRolActual() === 'supergod';
}

function isAdmin(): bool {
    // supergod tiene acceso total, pasa cualquier guard de admin
    return getRolActual() === 'admin' || isSuperGod();
}

function isContribuyente(): bool {
    return getRolActual() === 'contribuyente';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        logAccesoDenegado('REQUIERE_LOGIN', ['ruta' => $_SERVER['REQUEST_URI'] ?? '']);
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=login');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {  // isAdmin() ya incluye supergod
        logAccesoDenegado('REQUIERE_ADMIN', ['rol_actual' => getRolActual()]);
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
        exit;
    }
}

function requireContribuyente(): void {
    requireLogin();
    if (!isContribuyente() && !isSuperGod()) {
        logAccesoDenegado('REQUIERE_CONTRIBUYENTE', ['rol_actual' => getRolActual()]);
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
        exit;
    }
    // Verify contribuyente_id exists in session
    if (getContribuyenteId() === null && !isSuperGod()) {
        logAccesoDenegado('CONTRIBUYENTE_SIN_ID', ['usuario_id' => $_SESSION['usuario_id'] ?? 0]);
        setFlash('error', 'Su cuenta no tiene un perfil de contribuyente asociado. Contacte al administrador.');
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
        exit;
    }
}

function isRevisor(): bool {
    return getRolActual() === 'revisor';
}

function isAdminORevisor(): bool {
    return in_array(getRolActual(), ['admin', 'revisor', 'supergod'], true);
}

function requireAdminORevisor(): void {
    requireLogin();
    if (!isAdminORevisor()) {
        logAccesoDenegado('REQUIERE_ADMIN_O_REVISOR', ['rol_actual' => getRolActual()]);
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
        exit;
    }
}

function loginUsuario(array $usuario): void {
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
    $_SESSION['usuario_email']  = $usuario['email'];
    $_SESSION['usuario_rol']    = $usuario['rol'];
    if ($usuario['rol'] === 'contribuyente') {
        $_SESSION['contribuyente_id'] = $usuario['contribuyente_id'] ?? null;
    }
}

function logoutUsuario(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function getContribuyenteId(): ?int {
    return isset($_SESSION['contribuyente_id']) ? (int)$_SESSION['contribuyente_id'] : null;
}
