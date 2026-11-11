<?php
$saldoPendiente = (float)($saldo['saldo_pendiente'] ?? 0);
$obligacionesPendientes = (int)($saldo['obligaciones_pendientes'] ?? 0);
$estaAlCorriente = $saldoPendiente <= 0;
$setupPasos = 4;
$setupCompletados = 0;
$setupCompletados += $totalPredios > 0 ? 1 : 0;
$setupCompletados += $totalDocumentos > 0 ? 1 : 0;
$setupCompletados += $totalExpedientes > 0 ? 1 : 0;
$setupCompletados += $estaAlCorriente ? 1 : 0;
$setupPorcentaje = (int) round(($setupCompletados / $setupPasos) * 100);
$estadoPanelClase = $estaAlCorriente ? 'is-success' : 'is-warning';
$estadoPanelTexto = $estaAlCorriente ? 'Al corriente' : 'Atencion requerida';

$acciones = [];
$acciones[] = $totalPredios === 0
    ? [
        'icon' => 'bi bi-geo-alt-fill',
        'title' => 'Registrar su primer predio',
        'text' => 'Capture la informacion catastral base para habilitar nuevos tramites y expedientes.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=nuevo_predio',
        'button' => 'Registrar predio',
    ]
    : [
        'icon' => 'bi bi-map-fill',
        'title' => 'Revisar mis predios',
        'text' => 'Verifique datos del inmueble, ubicacion y claves catastrales antes de iniciar una gestion.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=predios',
        'button' => 'Ver predios',
    ];
$acciones[] = $tramitesPendientes > 0
    ? [
        'icon' => 'bi bi-folder2-open',
        'title' => 'Dar seguimiento a mis tramites',
        'text' => 'Revise expedientes recientes, estados y requisitos documentales para continuar sin retrasos.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=expedientes',
        'button' => 'Ver expedientes',
    ]
    : [
        'icon' => 'bi bi-file-earmark-plus',
        'title' => 'Iniciar un nuevo tramite',
        'text' => 'Comience una solicitud predial o fiscal con un flujo claro y ordenado desde el portal.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=nuevo_tramite',
        'button' => 'Nuevo tramite',
    ];
$acciones[] = $totalDocumentos > 0
    ? [
        'icon' => 'bi bi-cloud-arrow-up-fill',
        'title' => 'Actualizar documentacion',
        'text' => 'Mantenga archivos vigentes y reutilizables para reducir tiempos de revision en sus gestiones.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=documentos',
        'button' => 'Ver documentos',
    ]
    : [
        'icon' => 'bi bi-cloud-upload-fill',
        'title' => 'Subir su primer documento',
        'text' => 'Cargue identificaciones y comprobantes para aprovechar la reutilizacion en futuros tramites.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=subir_documento',
        'button' => 'Subir documento',
    ];
$acciones[] = $saldoPendiente > 0
    ? [
        'icon' => 'bi bi-receipt-cutoff',
        'title' => 'Regularizar obligaciones',
        'text' => 'Tiene ' . $obligacionesPendientes . ' obligacion(es) pendiente(s) por $' . number_format($saldoPendiente, 2) . '.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=pagos',
        'button' => 'Ir a pagos',
    ]
    : [
        'icon' => 'bi bi-check2-circle',
        'title' => 'Consultar situacion fiscal',
        'text' => 'Su cuenta se encuentra al corriente. Revise historial de pagos y movimientos cuando lo requiera.',
        'href' => BASE_URL . '/?modulo=contribuyente&accion=obligaciones',
        'button' => 'Ver obligaciones',
    ];

$pasos = [
    [
        'state' => $totalPredios > 0 ? 'is-complete' : 'is-current',
        'icon' => $totalPredios > 0 ? '<i class="bi bi-check-lg"></i>' : '1',
        'title' => 'Actualice sus datos y predios',
        'text' => $totalPredios > 0
            ? 'Ya cuenta con predios registrados para operar dentro del portal.'
            : 'Registre al menos un predio para iniciar solicitudes y asociar expedientes.',
    ],
    [
        'state' => $totalExpedientes > 0 ? ($tramitesPendientes > 0 ? 'is-current' : 'is-complete') : ($totalPredios > 0 ? 'is-current' : 'is-pending'),
        'icon' => $totalExpedientes > 0 && $tramitesPendientes === 0 ? '<i class="bi bi-check-lg"></i>' : '2',
        'title' => 'Inicie o de seguimiento a sus tramites',
        'text' => $totalExpedientes > 0
            ? ($tramitesPendientes > 0
                ? 'Tiene tramites activos que requieren seguimiento documental o revision.'
                : 'Sus expedientes recientes ya cuentan con historial dentro del portal.')
            : 'Cuando sus datos esten listos, podra abrir un nuevo expediente con un tramite digital.',
    ],
    [
        'state' => ($totalDocumentos > 0 && $estaAlCorriente) ? 'is-complete' : (($totalExpedientes > 0 || $saldoPendiente > 0) ? 'is-current' : 'is-pending'),
        'icon' => ($totalDocumentos > 0 && $estaAlCorriente) ? '<i class="bi bi-check-lg"></i>' : '3',
        'title' => 'Mantenga documentacion y obligaciones en regla',
        'text' => ($totalDocumentos > 0 && $estaAlCorriente)
            ? 'Su expediente digital cuenta con soporte documental y situacion fiscal controlada.'
            : 'Suba documentos vigentes y atienda pagos pendientes para evitar retrasos.',
    ],
];
?>

<section class="dashboard-hero">
    <div class="row g-4 align-items-stretch">
        <div class="col-xl-8">
            <div class="hero-panel">
                <span class="hero-eyebrow">Atencion digital del contribuyente</span>
                <h1 class="hero-title"><?= $tramitesPendientes > 0 ? 'Continue sus tramites con un seguimiento claro y ordenado.' : 'Administre sus tramites prediales y fiscales desde un solo panel.' ?></h1>
                <p class="hero-description"><?= $tramitesPendientes > 0 ? 'Desde aqui puede revisar expedientes, atender documentos y mantener control de cada avance sin perder contexto.' : 'REMTYS le permite iniciar solicitudes, cargar documentacion y consultar obligaciones dentro de una experiencia institucional pensada para orientar cada paso.' ?></p>

                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-light">
                        <i class="bi bi-plus-circle me-2"></i>Iniciar tramite
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento" class="btn btn-outline-light">
                        <i class="bi bi-cloud-upload me-2"></i>Subir documento
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=expedientes" class="btn btn-outline-light">
                        <i class="bi bi-folder2-open me-2"></i>Ver expedientes
                    </a>
                </div>

                <div class="hero-meta-grid">
                    <a href="<?= $cardLinks['predios'] ?>" class="hero-meta-item hero-meta-link" aria-label="Ver mis predios">
                        <span class="hero-meta-label">Predios registrados</span>
                        <span class="hero-meta-value"><?= (int)$totalPredios ?></span>
                    </a>
                    <a href="<?= $cardLinks['documentos'] ?>" class="hero-meta-item hero-meta-link" aria-label="Ver mis documentos">
                        <span class="hero-meta-label">Documentos activos</span>
                        <span class="hero-meta-value"><?= (int)$totalDocumentos ?></span>
                    </a>
                    <a href="<?= $cardLinks['expedientes'] ?>" class="hero-meta-item hero-meta-link" aria-label="Ver mis expedientes">
                        <span class="hero-meta-label">Expedientes abiertos</span>
                        <span class="hero-meta-value"><?= (int)$totalExpedientes ?></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="hero-sidecard">
                <div class="hero-sidecard-header">
                    <div>
                        <div class="section-eyebrow">Estado actual</div>
                        <h2 class="hero-sidecard-title">Panorama de su portal</h2>
                    </div>
                    <span class="hero-sidecard-status <?= $estadoPanelClase ?>"><i class="bi bi-activity"></i><?= $estadoPanelTexto ?></span>
                </div>

                <ul class="hero-status-list">
                    <li class="hero-status-item">
                        <i class="bi bi-receipt"></i>
                        <div>
                            <div class="hero-status-label">Situacion fiscal</div>
                            <div class="hero-status-value"><?= $estaAlCorriente ? 'Sin adeudos pendientes registrados.' : 'Tiene ' . $obligacionesPendientes . ' obligacion(es) por atender.' ?></div>
                        </div>
                    </li>
                    <li class="hero-status-item">
                        <i class="bi bi-file-earmark-text"></i>
                        <div>
                            <div class="hero-status-label">Tramites activos</div>
                            <div class="hero-status-value"><?= $tramitesPendientes > 0 ? $tramitesPendientes . ' tramite(s) en seguimiento.' : 'No hay tramites activos en este momento.' ?></div>
                        </div>
                    </li>
                    <li class="hero-status-item">
                        <i class="bi bi-archive"></i>
                        <div>
                            <div class="hero-status-label">Documentacion</div>
                            <div class="hero-status-value"><?= $totalDocumentos > 0 ? 'Cuenta con ' . $totalDocumentos . ' documento(s) disponible(s).' : 'Aun no ha cargado documentos reutilizables.' ?></div>
                        </div>
                    </li>
                </ul>

                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-600 fs-sm">Preparacion del portal</span>
                        <strong><?= $setupPorcentaje ?>%</strong>
                    </div>
                    <div class="doc-progress-bar">
                        <div class="doc-progress-fill" style="width: <?= $setupPorcentaje ?>%;"></div>
                    </div>
                    <p class="hero-progress-note">Ha completado <?= $setupCompletados ?> de <?= $setupPasos ?> hitos clave para operar su expediente digital con mayor fluidez.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <a href="<?= $cardLinks['predios'] ?>" class="stat-card h-100" aria-label="Ver mis predios registrados">
                <div class="stat-icon azul"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= (int)$totalPredios ?></div>
                    <div class="stat-label">Predios registrados</div>
                    <div class="stat-meta">Base catastral vinculada a su cuenta.</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= $cardLinks['expedientes'] ?>" class="stat-card h-100" aria-label="Ver mis expedientes">
                <div class="stat-icon verde"><i class="bi bi-folder2-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= (int)$totalExpedientes ?></div>
                    <div class="stat-label">Expedientes</div>
                    <div class="stat-meta">Historico disponible para consulta.</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= $cardLinks['tramites'] ?>" class="stat-card h-100" aria-label="Ver mis tramites activos">
                <div class="stat-icon naranja"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= (int)$tramitesPendientes ?></div>
                    <div class="stat-label">Tramites activos</div>
                    <div class="stat-meta">Gestiones que requieren seguimiento.</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= $cardLinks['documentos'] ?>" class="stat-card h-100" aria-label="Ver mis documentos activos">
                <div class="stat-icon morado"><i class="bi bi-cloud-check-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?= (int)$totalDocumentos ?></div>
                    <div class="stat-label">Documentos activos</div>
                    <div class="stat-meta">Archivos listos para reutilizarse.</div>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-heading-inline">
        <div>
            <span class="section-eyebrow">Siguiente paso</span>
            <h2 class="section-title">Acciones recomendadas</h2>
            <p class="section-description">Elija la accion que mas valor aporta a su avance actual dentro del portal.</p>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($acciones as $accion): ?>
        <div class="col-md-6 col-xl-3">
            <a href="<?= $accion['href'] ?>" class="action-card">
                <div class="action-card-icon"><i class="<?= $accion['icon'] ?>"></i></div>
                <h3 class="action-card-title"><?= s($accion['title']) ?></h3>
                <p class="action-card-text"><?= s($accion['text']) ?></p>
                <span class="action-card-link"><?= s($accion['button']) ?> <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="dashboard-section">
    <div class="row g-4 align-items-stretch">
        <div class="col-xl-7">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <div>
                        <span class="section-eyebrow">Ruta del contribuyente</span>
                        <h3 class="section-card-title">Que sigue en su proceso</h3>
                        <p class="section-card-subtitle">Una guia simple para priorizar el siguiente movimiento sin perder contexto.</p>
                    </div>
                </div>
                <div class="section-card-body">
                    <div class="process-list">
                        <?php foreach ($pasos as $paso): ?>
                        <div class="process-step <?= $paso['state'] ?>">
                            <div class="process-step-indicator"><?= $paso['icon'] ?></div>
                            <div class="process-step-content">
                                <div class="process-step-title"><?= s($paso['title']) ?></div>
                                <p class="process-step-text"><?= s($paso['text']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <div>
                        <span class="section-eyebrow">Control financiero</span>
                        <h3 class="section-card-title">Resumen de obligaciones y respaldo</h3>
                        <p class="section-card-subtitle">Una lectura rapida del estado fiscal y documental de su cuenta.</p>
                    </div>
                </div>
                <div class="section-card-body summary-card">
                    <div class="summary-highlight <?= $estaAlCorriente ? 'is-success' : 'is-warning' ?>">
                        <span class="summary-highlight-label">Situacion fiscal actual</span>
                        <span class="summary-highlight-value"><?= $estaAlCorriente ? 'Sin adeudos' : '$' . number_format($saldoPendiente, 2) ?></span>
                        <p class="summary-highlight-text"><?= $estaAlCorriente ? 'Su cuenta no presenta obligaciones pendientes de pago al momento.' : 'Tiene ' . $obligacionesPendientes . ' obligacion(es) pendiente(s). Atenderlas ayuda a evitar fricciones en el seguimiento.' ?></p>
                    </div>

                    <ul class="summary-list">
                        <li class="summary-list-item">
                            <i class="bi bi-check2-square"></i>
                            <div>
                                <strong><?= (int)$totalDocumentos ?></strong> documento(s) activo(s) disponibles para futuras gestiones.
                            </div>
                        </li>
                        <li class="summary-list-item">
                            <i class="bi bi-diagram-3"></i>
                            <div>
                                <strong><?= (int)$totalExpedientes ?></strong> expediente(s) registrado(s) dentro de su historial del portal.
                            </div>
                        </li>
                        <li class="summary-list-item">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong><?= (int)$tramitesPendientes ?></strong> tramite(s) activo(s) que requieren monitoreo o carga documental.
                            </div>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=obligaciones" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-receipt me-1"></i>Ver obligaciones
                        </a>
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=documentos" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-archive me-1"></i>Administrar documentos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="section-card">
        <div class="section-card-header">
            <div>
                <span class="section-eyebrow">Seguimiento reciente</span>
                <h3 class="section-card-title">Mis expedientes recientes</h3>
                <p class="section-card-subtitle">Consulte el estado de sus expedientes y localice el siguiente paso con rapidez.</p>
            </div>
            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=expedientes" class="btn btn-outline-primary btn-sm">Ver todos</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($expedientesRecientes)): ?>
            <div class="empty-state">
                <i class="bi bi-folder-x"></i>
                <h5>No tiene expedientes aun</h5>
                <p class="fs-sm">Inicie su primer tramite para abrir un expediente digital y comenzar el seguimiento.</p>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Iniciar tramite
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Predio</th>
                            <th>Estado</th>
                            <th>Tramites</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($expedientesRecientes as $exp): ?>
                        <?php
                        $badgeClass = match($exp['estado']) {
                            'abierto' => 'badge-estado-abierto',
                            'en_proceso' => 'badge-estado-en_proceso',
                            'resuelto' => 'badge-estado-resuelto',
                            'cerrado' => 'badge-estado-cerrado',
                            'cancelado' => 'badge-estado-cancelado',
                            default => 'bg-secondary text-white',
                        };
                        ?>
                        <tr>
                            <td><strong><?= s($exp['numero_expediente']) ?></strong></td>
                            <td><?= s($exp['clave_catastral'] ?? 'Sin predio asociado') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= s(str_replace('_', ' ', $exp['estado'])) ?></span></td>
                            <td><?= (int)$exp['tramites'] ?></td>
                            <td class="fs-xs text-muted"><?= s($exp['fecha_apertura']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_expediente&id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Ver detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
