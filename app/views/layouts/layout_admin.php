<?php
/**
 * Layout principal - Backoffice Admin / Validador
 */
if (!defined('ROOT_PATH')) { header('Location: /'); exit; }
$usuario = getUsuarioActual();
$nombreUsuario = s($usuario['nombre'] ?? 'Administrador');
$rolUsuario = s($usuario['rol'] ?? 'admin');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= s($titulo ?? 'Administracion') ?> - REMTYS Admin</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="app-body app-body--admin">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-shell">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=dashboard" class="sidebar-brand">
            <div class="brand-logo">
                <div class="brand-icon"><i class="bi bi-shield-fill-check"></i></div>
                <div class="brand-text">
                    <span class="brand-kicker">Operacion institucional</span>
                    <div class="app-name">REMTYS</div>
                    <div class="app-subtitle">Panel Administrativo</div>
                </div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Inicio</div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=dashboard" class="nav-link <?= ($accion_activa ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Dashboard</span>
                        <span class="nav-link-meta">Resumen operativo</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Gestion</div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=contribuyentes" class="nav-link <?= ($accion_activa ?? '') === 'contribuyentes' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-people-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Contribuyentes</span>
                        <span class="nav-link-meta">Padron y consulta</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=predios" class="nav-link <?= ($accion_activa ?? '') === 'predios' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-map-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Predios</span>
                        <span class="nav-link-meta">Inventario territorial</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="nav-link <?= ($accion_activa ?? '') === 'expedientes' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-folder-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Expedientes</span>
                        <span class="nav-link-meta">Seguimiento documental</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tramites" class="nav-link <?= ($accion_activa ?? '') === 'tramites' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-file-earmark-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Tramites</span>
                        <span class="nav-link-meta">Control del flujo</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Validacion</div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=validacion_docs" class="nav-link <?= ($accion_activa ?? '') === 'validacion_docs' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-patch-check-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Validar Documentos</span>
                        <span class="nav-link-meta">Revision y dictamen</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=inspecciones" class="nav-link <?= ($accion_activa ?? '') === 'inspecciones' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-clipboard2-check-fill"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Inspecciones</span>
                        <span class="nav-link-meta">Verificacion en campo</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Fiscal</div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=fiscal" class="nav-link <?= ($accion_activa ?? '') === 'fiscal' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-bank2"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Gestion Fiscal</span>
                        <span class="nav-link-meta">Obligaciones y control</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=pagos" class="nav-link <?= ($accion_activa ?? '') === 'pagos' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-cash-stack"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Pagos</span>
                        <span class="nav-link-meta">Cobranza y movimientos</span>
                    </span>
                </a>
            </div>

            <div class="sidebar-nav-group">
                <div class="sidebar-section-label">Catalogos</div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=catalogos" class="nav-link <?= ($accion_activa ?? '') === 'catalogos' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-list-ul"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Catalogos</span>
                        <span class="nav-link-meta">Configuracion general</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_tramite" class="nav-link <?= ($accion_activa ?? '') === 'tipos_tramite' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-journal-text"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Tipos de Tramite</span>
                        <span class="nav-link-meta">Matriz de procesos</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_documento" class="nav-link <?= ($accion_activa ?? '') === 'tipos_documento' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Tipos de Documento</span>
                        <span class="nav-link-meta">Clasificacion documental</span>
                    </span>
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=requisitos" class="nav-link <?= ($accion_activa ?? '') === 'requisitos' ? 'active' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-list-check"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Requisitos</span>
                        <span class="nav-link-meta">Reglas por tramite</span>
                    </span>
                </a>
            </div>

            <?php if (isSuperGod()): ?>
            <div class="sidebar-nav-group">
                <div class="sidebar-section-label" style="color:#f0883e;">Supergod</div>
                <a href="<?= BASE_URL ?>/?modulo=supergod&accion=consola" class="nav-link <?= ($_GET['modulo'] ?? '') === 'supergod' && ($_GET['accion'] ?? '') === 'consola' ? 'active' : '' ?>" style="<?= ($_GET['modulo'] ?? '') === 'supergod' ? 'border-left-color:#f0883e;' : '' ?>">
                    <span class="nav-link-icon"><i class="bi bi-terminal-fill" style="color:#f0883e;"></i></span>
                    <span class="nav-link-copy">
                        <span class="nav-link-title">Consola del Sistema</span>
                        <span class="nav-link-meta">Monitoreo y auditoria</span>
                    </span>
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar"><i class="bi bi-person-badge-fill"></i></div>
                <div>
                    <div class="sidebar-user-name"><?= $nombreUsuario ?></div>
                    <div class="sidebar-user-role"><?= ucfirst($rolUsuario) ?></div>
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
                <span class="topbar-eyebrow">Panel administrativo</span>
                <span class="topbar-title"><?= s($titulo ?? 'Panel Administrativo') ?></span>
                <span class="topbar-subtitle">Supervise contribuyentes, expedientes, validaciones y control fiscal con una vista operativa consistente.</span>
            </div>
        </div>

        <div class="topbar-actions">
            <span class="topbar-chip"><i class="bi bi-shield-lock me-1"></i><?= ucfirst($rolUsuario) ?></span>
            <div class="topbar-user">
                <span class="topbar-user-label">Usuario interno</span>
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

