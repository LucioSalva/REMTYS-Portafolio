<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REMTYS - Sistema de Gestion Predial y Fiscal Municipal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="public-body">
<header class="public-header">
    <div class="container public-header-inner">
        <a href="<?= BASE_URL ?>" class="public-brand">
            <span class="public-brand-icon"><i class="bi bi-building-fill"></i></span>
            <span>
                <span class="public-brand-kicker">Gobierno Municipal</span>
                <span class="public-brand-name">REMTYS</span>
            </span>
        </a>

        <nav class="public-nav">
            <a href="#servicios" class="public-nav-link">Servicios</a>
            <a href="#proceso" class="public-nav-link">Como funciona</a>
            <a href="#acceso" class="public-nav-link">Acceso</a>
        </nav>

        <div class="public-actions">
            <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="btn btn-outline-primary btn-sm">Iniciar sesion</a>
            <a href="<?= BASE_URL ?>/?modulo=auth&accion=registro" class="btn btn-primary btn-sm">Crear cuenta</a>
        </div>
    </div>
</header>

<main class="landing-main">
    <section class="landing-hero">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="landing-hero-copy">
                        <span class="section-eyebrow">Portal institucional del contribuyente</span>
                        <h1 class="landing-title">Tramites prediales y fiscales con una experiencia clara, formal y confiable.</h1>
                        <p class="landing-text">REMTYS concentra registro de predios, expedientes, documentos y pagos en un flujo digital pensado para orientar al ciudadano en cada etapa del tramite.</p>
                        <div class="landing-actions">
                            <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al portal
                            </a>
                            <a href="<?= BASE_URL ?>/?modulo=auth&accion=registro" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-person-plus me-2"></i>Registrarse
                            </a>
                        </div>
                        <div class="landing-trust">
                            <span class="trust-chip"><i class="bi bi-shield-check"></i> Acceso seguro</span>
                            <span class="trust-chip"><i class="bi bi-clock-history"></i> Disponibilidad 24/7</span>
                            <span class="trust-chip"><i class="bi bi-file-earmark-check"></i> Tramites digitales</span>
                            <span class="trust-chip"><i class="bi bi-geo-alt"></i> Gestion predial centralizada</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="spotlight-card">
                        <div class="spotlight-header">
                            <div>
                                <span class="section-eyebrow">Panel del contribuyente</span>
                                <h2 class="section-card-title">Todo el proceso en un solo lugar</h2>
                            </div>
                            <span class="spotlight-status">Disponible</span>
                        </div>
                        <div class="spotlight-body">
                            <div class="spotlight-metric-grid">
                                <div class="spotlight-metric">
                                    <span class="spotlight-metric-value">01</span>
                                    <span class="spotlight-metric-label">Cuenta institucional</span>
                                </div>
                                <div class="spotlight-metric">
                                    <span class="spotlight-metric-value">02</span>
                                    <span class="spotlight-metric-label">Seguimiento de tramites</span>
                                </div>
                                <div class="spotlight-metric">
                                    <span class="spotlight-metric-value">03</span>
                                    <span class="spotlight-metric-label">Pagos y documentos</span>
                                </div>
                            </div>

                            <div class="spotlight-list">
                                <div class="spotlight-item">
                                    <span class="spotlight-item-index">01</span>
                                    <div>
                                        <strong>Registre su predio</strong>
                                        <p>Asocie la informacion catastral base para comenzar su gestion.</p>
                                    </div>
                                </div>
                                <div class="spotlight-item">
                                    <span class="spotlight-item-index">02</span>
                                    <div>
                                        <strong>Inicie y consulte tramites</strong>
                                        <p>Revise expediente, estado y requisitos desde un panel claro.</p>
                                    </div>
                                </div>
                                <div class="spotlight-item">
                                    <span class="spotlight-item-index">03</span>
                                    <div>
                                        <strong>Suba documentos y regularice pagos</strong>
                                        <p>Mantenga su expediente listo para avanzar con menos friccion.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="servicios" class="landing-section">
        <div class="container">
            <div class="section-heading">
                <span class="section-eyebrow">Servicios clave</span>
                <h2 class="section-title">Un portal disenado para orientar el tramite completo</h2>
                <p class="section-description">La experiencia se organiza en modulos claros para que el contribuyente sepa que tiene, que le falta y que sigue en cada momento.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-speedometer2"></i></div>
                        <h3 class="feature-title">Resumen inmediato</h3>
                        <p class="feature-text">Visualice expedientes, tramites activos y situacion fiscal desde la pantalla principal.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-folder2-open"></i></div>
                        <h3 class="feature-title">Seguimiento ordenado</h3>
                        <p class="feature-text">Revise expedientes y conozca el estado exacto de cada gestion sin perder contexto.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-archive"></i></div>
                        <h3 class="feature-title">Documentacion centralizada</h3>
                        <p class="feature-text">Mantenga documentos vigentes, reutilizables y listos para futuras solicitudes.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-cash-stack"></i></div>
                        <h3 class="feature-title">Pagos y obligaciones</h3>
                        <p class="feature-text">Consulte adeudos, historial de pagos y referencias desde una vista institucional consistente.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="proceso" class="landing-section landing-section--soft">
        <div class="container">
            <div class="section-heading">
                <span class="section-eyebrow">Ruta de atencion</span>
                <h2 class="section-title">Tres pasos para operar con claridad</h2>
                <p class="section-description">El portal esta pensado para que el usuario avance sin perderse entre pantallas, documentos y pagos.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-card">
                        <span class="step-number">1</span>
                        <h3 class="step-title">Registre su cuenta y predio</h3>
                        <p class="step-text">Active su acceso institucional y capture la informacion base del inmueble.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <span class="step-number">2</span>
                        <h3 class="step-title">Inicie el tramite y siga su avance</h3>
                        <p class="step-text">Revise expedientes, estados y requisitos documentales desde el panel del contribuyente.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card">
                        <span class="step-number">3</span>
                        <h3 class="step-title">Mantenga documentos y obligaciones al dia</h3>
                        <p class="step-text">Evite retrasos con una lectura clara de documentos vigentes y obligaciones pendientes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="acceso" class="landing-section">
        <div class="container">
            <div class="cta-banner">
                <div>
                    <span class="section-eyebrow">Acceso y registro</span>
                    <h2 class="section-title">Entre al portal y gestione su tramite con seguridad</h2>
                    <p class="section-description">Use su cuenta para dar seguimiento a expedientes, subir documentacion y consultar su situacion fiscal sin desplazamientos innecesarios.</p>
                </div>
                <div class="landing-actions">
                    <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="btn btn-light btn-lg">Iniciar sesion</a>
                    <a href="<?= BASE_URL ?>/?modulo=auth&accion=registro" class="btn btn-outline-light btn-lg">Crear cuenta</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="public-footer">
    <div class="container public-footer-inner">
        <div>
            <strong>REMTYS</strong>
            <span>Sistema de Gestion Predial y Fiscal Municipal</span>
        </div>
        <span>Portal institucional para atencion digital del contribuyente.</span>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
