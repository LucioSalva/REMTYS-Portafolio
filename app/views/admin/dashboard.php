<?php /* Dashboard Admin */ ?>

<!-- Stats row — tarjetas clicables con navegación por rol -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['contribuyentes'] ?>" class="stat-card" aria-label="Ver listado de contribuyentes">
            <div class="stat-icon azul"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['contribuyentes'] ?? 0)) ?></div>
                <div class="stat-label">Contribuyentes</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['predios'] ?>" class="stat-card" aria-label="Ver predios activos">
            <div class="stat-icon verde"><i class="bi bi-map-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['predios'] ?? 0)) ?></div>
                <div class="stat-label">Predios Activos</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['expedientes'] ?>" class="stat-card" aria-label="Ver expedientes">
            <div class="stat-icon naranja"><i class="bi bi-folder-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['expedientes'] ?? 0)) ?></div>
                <div class="stat-label">Expedientes</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['tramites'] ?>" class="stat-card" aria-label="Ver todos los tramites">
            <div class="stat-icon rojo"><i class="bi bi-file-earmark-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['tramites'] ?? 0)) ?></div>
                <div class="stat-label">Tramites Total</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['docs_pendientes'] ?>" class="stat-card" aria-label="Validar documentos pendientes">
            <div class="stat-icon morado"><i class="bi bi-patch-check-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['docs_pendientes'] ?? 0)) ?></div>
                <div class="stat-label">Docs. Pendientes</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="<?= $cardLinks['tramites_activos'] ?>" class="stat-card" aria-label="Ver tramites activos">
            <div class="stat-icon naranja"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= number_format((int)($stats['tramites_activos'] ?? 0)) ?></div>
                <div class="stat-label">Tramites Activos</div>
            </div>
        </a>
    </div>
</div>

<!-- Alertas de acción -->
<?php if ((int)($stats['docs_pendientes'] ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong><?= (int)$stats['docs_pendientes'] ?></strong> documento(s) pendientes de validación.
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=validacion_docs" class="alert-link ms-2">Revisar ahora</a>
    </div>
</div>
<?php endif; ?>

<!-- Accesos rápidos -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primario text-white"><i class="bi bi-lightning-charge-fill me-2"></i>Acciones Rápidas</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_expediente" class="btn btn-primary">
                        <i class="bi bi-folder-plus me-1"></i>Nuevo Expediente
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=validacion_docs" class="btn btn-warning text-dark">
                        <i class="bi bi-patch-check me-1"></i>Validar Documentos
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_contribuyente" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-1"></i>Nuevo Contribuyente
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nueva_inspeccion" class="btn btn-outline-secondary">
                        <i class="bi bi-clipboard2-plus me-1"></i>Programar Inspección
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=reportes" class="btn btn-outline-dark">
                        <i class="bi bi-bar-chart-fill me-1"></i>Ver Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expedientes recientes -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-folder-fill me-2 text-primario"></i>Expedientes Recientes</span>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-sm btn-outline-primary">Ver todos</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($expRecientes)): ?>
        <div class="empty-state p-4">
            <i class="bi bi-folder-x"></i>
            <h5>Sin expedientes registrados</h5>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Contribuyente</th>
                        <th>Predio</th>
                        <th>Estado</th>
                        <th>Apertura</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($expRecientes as $exp): ?>
                    <tr>
                        <td><strong><?= s($exp['numero_expediente']) ?></strong></td>
                        <td>
                            <div class="fs-sm"><?= s($exp['contribuyente']) ?></div>
                        </td>
                        <td class="fs-xs text-muted"><?= s($exp['clave_catastral'] ?? '—') ?></td>
                        <td>
                            <?php
                            $cl = match($exp['estado']) {
                                'abierto'    => 'badge-estado-abierto',
                                'en_proceso' => 'badge-estado-en_proceso',
                                'resuelto'   => 'badge-estado-resuelto',
                                'cerrado'    => 'badge-estado-cerrado',
                                'cancelado'  => 'badge-estado-cancelado',
                                default      => 'bg-secondary text-white'
                            };
                            ?>
                            <span class="badge <?= $cl ?>"><?= s(str_replace('_', ' ', $exp['estado'])) ?></span>
                        </td>
                        <td class="fs-xs text-muted"><?= s($exp['fecha_apertura']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$exp['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
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
