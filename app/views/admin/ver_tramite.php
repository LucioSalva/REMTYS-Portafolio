<?php /* Admin: Ver Trámite con Requisitos */
function badgeRequisito(string $cl): string {
    return match($cl) {
        'CUMPLE'       => '<span class="req-badge badge-cumple"><i class="bi bi-check-circle-fill"></i>CUMPLE</span>',
        'REUTILIZABLE' => '<span class="req-badge badge-reutilizable"><i class="bi bi-arrow-repeat"></i>REUTILIZABLE</span>',
        'PENDIENTE'    => '<span class="req-badge badge-pendiente"><i class="bi bi-hourglass-split"></i>EN REVISIÓN</span>',
        'VENCIDO'      => '<span class="req-badge badge-vencido"><i class="bi bi-exclamation-triangle-fill"></i>VENCIDO</span>',
        'INVALIDO'     => '<span class="req-badge badge-invalido"><i class="bi bi-x-circle-fill"></i>INVÁLIDO</span>',
        'FALTANTE'     => '<span class="req-badge badge-faltante"><i class="bi bi-dash-circle"></i>FALTANTE</span>',
        'ESPECIFICO'   => '<span class="req-badge badge-especifico"><i class="bi bi-pin-fill"></i>ESPECÍFICO</span>',
        'EXCLUSIVO'    => '<span class="req-badge badge-exclusivo"><i class="bi bi-lock-fill"></i>EXCLUSIVO</span>',
        default        => '<span class="req-badge badge-faltante">' . s($cl) . '</span>',
    };
}
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=tramites" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Trámite <?= s($tramite['numero_tramite']) ?></h5>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_tramite&id=<?= (int)$tramite['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=dependencias_tramite&tramite_id=<?= (int)$tramite['id'] ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-diagram-3 me-1"></i>Dependencias</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-primario text-white"><i class="bi bi-file-earmark-fill me-2"></i>Datos del Trámite</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Número:</dt><dd class="col-7 fw-500"><?= s($tramite['numero_tramite']) ?></dd>
                    <dt class="col-5 text-muted">Tipo:</dt><dd class="col-7"><?= s($tramite['tipo_nombre']) ?></dd>
                    <dt class="col-5 text-muted">Expediente:</dt>
                    <dd class="col-7">
                        <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$tramite['expediente_id'] ?>" class="text-decoration-none"><?= s($tramite['numero_expediente']) ?></a>
                    </dd>
                    <dt class="col-5 text-muted">Contribuyente:</dt><dd class="col-7"><?= s($tramite['contrib_nombre']) ?></dd>
                    <dt class="col-5 text-muted">Estado:</dt>
                    <dd class="col-7">
                        <?php
                        $tc = match($tramite['estado']) {
                            'aprobado'=>'badge-estado-aprobado','rechazado'=>'badge-estado-rechazado',
                            'cancelado'=>'badge-estado-cancelado','en_revision'=>'badge-estado-en_revision',
                            'documentacion_pendiente'=>'badge-estado-documentacion_pendiente',
                            default=>'badge-estado-iniciado'
                        };
                        ?>
                        <span class="badge <?= $tc ?>"><?= s(str_replace('_',' ',$tramite['estado'])) ?></span>
                    </dd>
                    <dt class="col-5 text-muted">Fecha:</dt><dd class="col-7"><?= s($tramite['fecha_inicio']) ?></dd>
                    <?php if ($tramite['fecha_resolucion']): ?>
                    <dt class="col-5 text-muted">Resolución:</dt><dd class="col-7"><?= s($tramite['fecha_resolucion']) ?></dd>
                    <?php endif; ?>
                </dl>
                <?php if ($tramite['observaciones']): ?>
                <hr class="my-2"><div class="fs-xs text-muted"><?= s($tramite['observaciones']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progreso documental -->
        <?php
        $aAprob   = (int)$completitud['aprobados'];
        $aRev     = (int)$completitud['en_revision'];
        $aFalt    = (int)$completitud['faltantes'];
        $aVenc    = (int)$completitud['vencidos'];
        $aInval   = (int)$completitud['invalidos'];
        $aTotObl  = (int)$completitud['total_obligatorios'];
        $aPctAprob= (int)$completitud['porcentaje_aprobado'];
        $aPctCarg = (int)$completitud['porcentaje_cargado'];
        $aListo   = (bool)$completitud['listo_para_envio'];
        $wA = $aTotObl > 0 ? ($aAprob / $aTotObl) * 100 : 0;
        $wR = $aTotObl > 0 ? ($aRev   / $aTotObl) * 100 : 0;
        ?>
        <div class="card">
            <div class="card-header fs-sm"><i class="bi bi-bar-chart me-2"></i>Completitud Documental</div>
            <div class="card-body">
                <div class="d-flex justify-content-between fs-sm mb-1">
                    <span><strong><?= $aAprob ?></strong> / <?= $aTotObl ?> aprobados</span>
                    <strong><?= $aPctAprob ?>%</strong>
                </div>
                <div class="doc-progress-bar doc-progress-bar-dual" role="progressbar"
                     aria-label="Completitud documental"
                     aria-valuenow="<?= $aPctCarg ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="doc-progress-fill doc-progress-fill-aprobado" style="width:<?= $wA ?>%;"></div>
                    <div class="doc-progress-fill doc-progress-fill-pendiente" style="width:<?= $wR ?>%;"></div>
                </div>
                <div class="mt-2 fs-xs d-flex flex-wrap gap-2">
                    <?php if ($aRev > 0): ?>
                    <span class="text-dorado"><i class="bi bi-hourglass-split me-1"></i><?= $aRev ?> en revisión</span>
                    <?php endif; ?>
                    <?php if ($aFalt > 0): ?>
                    <span class="text-muted"><i class="bi bi-dash-circle me-1"></i><?= $aFalt ?> faltantes</span>
                    <?php endif; ?>
                    <?php if ($aVenc > 0): ?>
                    <span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= $aVenc ?> vencidos</span>
                    <?php endif; ?>
                    <?php if ($aInval > 0): ?>
                    <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i><?= $aInval ?> rechazados</span>
                    <?php endif; ?>
                </div>
                <?php if ($aListo): ?>
                <div class="alert alert-success mt-2 py-1 mb-0 fs-xs"><i class="bi bi-check-circle-fill me-1"></i>Documentación completa y aprobada</div>
                <?php elseif ($aFalt == 0 && $aVenc == 0 && $aInval == 0 && $aRev > 0): ?>
                <div class="alert alert-info mt-2 py-1 mb-0 fs-xs"><i class="bi bi-hourglass-split me-1"></i>Cargada, pendiente de validación</div>
                <?php else: ?>
                <div class="alert alert-warning mt-2 py-1 mb-0 fs-xs"><i class="bi bi-exclamation-triangle me-1"></i>Documentación incompleta</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primario text-white"><i class="bi bi-list-check me-2"></i>Requisitos Documentales</div>
            <div class="card-body p-0">
                <?php if (empty($requisitos)): ?>
                <div class="empty-state p-3"><i class="bi bi-inbox"></i><p class="fs-sm">Sin requisitos configurados.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="tabla-requisitos">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Documento</th>
                                <th>Estado</th>
                                <th>Archivo</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requisitos as $i => $req): ?>
                        <tr>
                            <td class="text-muted fs-xs"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-500 fs-sm"><?= s($req['tipo_documento_nombre']) ?></div>
                                <?php if ($req['obligatorio']): ?><span class="badge bg-danger-subtle text-danger fs-xs">Obligatorio</span><?php endif; ?>
                            </td>
                            <td><?= badgeRequisito($req['clasificacion']) ?></td>
                            <td>
                                <?php if ($req['documento']): ?>
                                <div class="fs-xs text-truncate d-flex align-items-center gap-1" style="max-width:180px">
                                    <i class="bi bi-file-earmark text-muted"></i>
                                    <span class="text-truncate" title="<?= s($req['documento']['nombre_original'] ?? $req['documento']['nombre_archivo']) ?>">
                                        <?= s($req['documento']['nombre_original'] ?? $req['documento']['nombre_archivo']) ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_documento&id=<?= (int)$req['documento']['id'] ?>"
                                       target="_blank" rel="noopener"
                                       class="text-decoration-none ms-1"
                                       title="Ver archivo">
                                        <i class="bi bi-eye-fill" style="color: var(--color-vino, #691C32);"></i>
                                    </a>
                                </div>
                                <?php if ($req['documento']['estado_validacion']): ?>
                                <span class="badge fs-xs <?= $req['documento']['estado_validacion'] === 'aprobado' ? 'bg-success' : ($req['documento']['estado_validacion'] === 'rechazado' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= s($req['documento']['estado_validacion']) ?>
                                </span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-muted fs-xs">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($req['documento'] && $req['documento']['estado_validacion'] === 'pendiente'): ?>
                                <a href="<?= BASE_URL ?>/?modulo=admin&accion=validacion_docs" class="btn btn-xs btn-sm btn-outline-warning py-0">
                                    <i class="bi bi-patch-check"></i> Validar
                                </a>
                                <?php elseif (!$req['documento']): ?>
                                <span class="text-muted fs-xs">Pendiente subida</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
