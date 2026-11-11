<?php
/**
 * Mensajes Flash de sesión
 */

function setFlash(string $tipo, string $mensaje): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function mostrarFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $tipo = match($flash['tipo']) {
        'exito'      => 'success',
        'error'      => 'danger',
        'advertencia'=> 'warning',
        'info'       => 'info',
        default      => 'info',
    };
    $mensaje = htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8');
    echo <<<HTML
    <div class="alert alert-{$tipo} alert-dismissible fade show" role="alert">
        {$mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    HTML;
}
