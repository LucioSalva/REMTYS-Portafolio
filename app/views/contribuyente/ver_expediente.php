<?php /* Vista: Ver Expediente */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=expedientes" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">Expediente <?= s($expediente['numero_expediente']) ?></h5>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <!-- Trámites del expediente -->
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-file-earmark-text me-2"></i>Trámites</span>
                <span class="badge bg-primary"><?= count($expediente['tramites']) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($expediente['tramites'])): ?>
                <div class="empty-state p-3">
                    <i class="bi bi-file-x"></i>
                    <p class="fs-sm">Sin trámites en este expediente.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Trámite</th><th>Tipo</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($expediente['tramites'] as $t): ?>
                        <tr>
                            <td><strong><?= s($t['numero_tramite']) ?></strong></td>
                            <td class="fs-sm"><?= s($t['tipo_tramite_nombre']) ?></td>
                            <td>
                                <?php
                                $tc = match($t['estado']) {
                                    'aprobado' => 'badge-estado-aprobado',
                                    'rechazado' => 'badge-estado-rechazado',
                                    'cancelado' => 'badge-estado-cancelado',
                                    'en_revision' => 'badge-estado-en_revision',
                                    'documentacion_pendiente' => 'badge-estado-documentacion_pendiente',
                                    default => 'badge-estado-iniciado'
                                };
                                ?>
                                <span class="badge <?= $tc ?>"><?= s(str_replace('_',' ',$t['estado'])) ?></span>
                            </td>
                            <td class="fs-xs text-muted"><?= s($t['fecha_inicio']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=requisitos_tramite&id=<?= (int)$t['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-list-check me-1"></i>Requisitos
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

        <!-- Historial -->
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Historial del Expediente</div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                <p class="text-muted fs-sm">Sin eventos registrados.</p>
                <?php else: ?>
                <ul class="timeline">
                <?php foreach ($historial as $h): ?>
                    <li class="timeline-item">
                        <div class="timeline-icon"><i class="bi bi-check"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-date"><?= s($h['fecha']) ?> <?php if ($h['usuario_nombre']): ?> — <?= s($h['usuario_nombre']) ?><?php endif; ?></div>
                            <div class="timeline-event"><?= s($h['evento']) ?></div>
                            <?php if ($h['descripcion']): ?>
                            <div class="timeline-desc"><?= s($h['descripcion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primario text-white"><i class="bi bi-info-circle me-2"></i>Datos del Expediente</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted">Número:</dt>
                    <dd class="col-sm-7 fw-500"><?= s($expediente['numero_expediente']) ?></dd>
                    <dt class="col-sm-5 text-muted">Estado:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $ecl = match($expediente['estado']) {
                            'abierto'=>'badge-estado-abierto','en_proceso'=>'badge-estado-en_proceso',
                            'resuelto'=>'badge-estado-resuelto','cerrado'=>'badge-estado-cerrado',
                            'cancelado'=>'badge-estado-cancelado', default=>'bg-secondary text-white'
                        };
                        ?>
                        <span class="badge <?= $ecl ?>"><?= s(str_replace('_',' ',$expediente['estado'])) ?></span>
                    </dd>
                    <dt class="col-sm-5 text-muted">Apertura:</dt>
                    <dd class="col-sm-7"><?= s($expediente['fecha_apertura']) ?></dd>
                    <?php if ($expediente['clave_catastral']): ?>
                    <dt class="col-sm-5 text-muted">Predio:</dt>
                    <dd class="col-sm-7"><?= s($expediente['clave_catastral']) ?></dd>
                    <dt class="col-sm-5 text-muted">Dirección:</dt>
                    <dd class="col-sm-7 fs-xs"><?= s($expediente['calle'] . ' ' . $expediente['numero_exterior'] . ', ' . $expediente['colonia']) ?></dd>
                    <?php endif; ?>
                    <?php if ($expediente['descripcion']): ?>
                    <dt class="col-sm-5 text-muted">Descripción:</dt>
                    <dd class="col-sm-7 fs-xs"><?= s($expediente['descripcion']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
