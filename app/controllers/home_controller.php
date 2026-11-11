<?php
/**
 * Controlador Home — Página principal
 */

function accion_index(): void {
    if (isLoggedIn()) {
        if (isAdmin() || getRolActual() === 'revisor') {
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=dashboard');
        } else {
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=dashboard');
        }
        exit;
    }
    // Mostrar página de bienvenida
    require APP_PATH . '/views/shared/bienvenida.php';
}
