<?php
/**
 * Layout principal - Portal del Contribuyente
 * Variables esperadas: $titulo (string), $modulo_activo (string), $accion_activa (string)
 */
if (!defined('ROOT_PATH')) { header('Location: /'); exit; }
$usuario = getUsuarioActual();
$nombreUsuario = s($usuario['nombre'] ?? 'Usuario');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= s($titulo ?? 'Portal Contribuyente') ?> - REMTYS</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="app-body app-body--contribuyente">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-shell">
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=dashboard" class="sidebar-brand">
            <div class="brand-logo">
                <div class="brand-icon"><i class="bi bi-building-fill"></i></div>
                <div class="brand-text">
                    <span class="brand-kicker">Gobierno Municipal</span>
                    <div class="app-name">REMTYS</div>
                    <div class="app-subtitle">Portal del Contribuyente</div>
                </div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Inicio</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=dashboard" class="nav-link <?= ($accion_activa ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-speedometer2"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mi Panel</span>
                        <span class="nav-link-meta">Resumen general y seguimiento</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Mi Cuenta</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=perfil" class="nav-link <?= ($accion_activa ?? '') === 'perfil' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-person-circle"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Datos</span>
                        <span class="nav-link-meta">Informacion personal y contacto</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Predios</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=predios" class="nav-link <?= ($accion_activa ?? '') === 'predios' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Predios</span>
                        <span class="nav-link-meta">Consulta y administracion</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_predio" class="nav-link <?= ($accion_activa ?? '') === 'nuevo_predio' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-plus-circle"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Registrar Predio</span>
                        <span class="nav-link-meta">Alta de nuevo inmueble</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Tramites</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=expedientes" class="nav-link <?= ($accion_activa ?? '') === 'expedientes' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-folder2-open"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Expedientes</span>
                        <span class="nav-link-meta">Estado y seguimiento</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=tramites" class="nav-link <?= ($accion_activa ?? '') === 'tramites' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Tramites</span>
                        <span class="nav-link-meta">Solicitudes en proceso</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Documentacion</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=documentos" class="nav-link <?= ($accion_activa ?? '') === 'documentos' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-archive"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Documentos</span>
                        <span class="nav-link-meta">Archivos, vigencias y estatus</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Pagos</div>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=obligaciones" class="nav-link <?= ($accion_activa ?? '') === 'obligaciones' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-receipt"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Obligaciones</span>
                        <span class="nav-link-meta">Adeudos y pendientes</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=pagos" class="nav-link <?= ($accion_activa ?? '') === 'pagos' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-cash-coin"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Mis Pagos</span>
                        <span class="nav-link-meta">Historial y referencias</span>
                    </span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
                <div>
                    <div class="sidebar-user-name"><?= $nombreUsuario ?></div>
                    <div class="sidebar-user-role">Contribuyente registrado</div>
                </div>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=logout" class="w-100"><?= campoCSRF() ?><button type="submit" class="btn btn-outline-light w-100" data-confirm="¿Cerrar sesion?"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion</button></form>
        </div>
    </div>
</aside>

<div class="main-content">
    <header class="topbar">
        <div class="topbar-main">
            <button class="topbar-toggle d-lg-none" id="sidebarToggle" aria-label="Abrir navegacion">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-copy">
                <span class="topbar-eyebrow">Portal del contribuyente</span>
                <span class="topbar-title"><?= s($titulo ?? 'Panel del Contribuyente') ?></span>
                <span class="topbar-subtitle">Consulte predios, expedientes, documentos y obligaciones desde un entorno institucional unificado.</span>
            </div>
        </div>

        <div class="topbar-actions">
            <span class="topbar-chip"><i class="bi bi-shield-check me-1"></i>Servicio en linea</span>
            <div class="topbar-user">
                <span class="topbar-user-label">Sesion activa</span>
                <span class="topbar-user-name"><?= $nombreUsuario ?></span>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=logout" style="display:inline"><?= campoCSRF() ?><button type="submit" class="btn btn-outline-primary btn-sm topbar-logout" data-confirm="¿Cerrar sesion?"><i class="bi bi-box-arrow-right me-1"></i>Salir</button></form>
        </div>
    </header>

    <main class="page-content">
        <div class="page-container">
            <?php mostrarFlash(); ?>
            <?= $contenido ?? '' ?>
        </div>
    </main>
</div>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>

