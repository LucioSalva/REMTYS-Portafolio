<?php
/**
 * Protección CSRF
 */

function generarTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF(string $token): bool {
    if (empty($_SESSION['csrf_token'])) return false;
    $valido = hash_equals($_SESSION['csrf_token'], $token);
    if ($valido) {
        // Rotar token tras uso exitoso
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $valido;
}

function campoCSRF(): string {
    $token = generarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verificarCSRF(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validarTokenCSRF($token)) {
            logCSRF('CSRF_TOKEN_INVALIDO', ['modulo' => $_GET['modulo'] ?? '', 'accion' => $_GET['accion'] ?? '']);
            http_response_code(403);
            die('Error de seguridad: token CSRF inválido. Recargue la página e intente nuevamente.');
        }
    }
}
