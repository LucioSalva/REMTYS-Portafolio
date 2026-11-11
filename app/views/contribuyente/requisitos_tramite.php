<?php
function badgeClasificacion(string $clasificacion): string {
    return match($clasificacion) {
        'CUMPLE'       => '<span class="req-badge badge-cumple"><i class="bi bi-check-circle-fill"></i>CUMPLE</span>',
        'REUTILIZABLE' => '<span class="req-badge badge-reutilizable"><i class="bi bi-arrow-repeat"></i>REUTILIZABLE</span>',
        'PENDIENTE'    => '<span class="req-badge badge-pendiente"><i class="bi bi-hourglass-split"></i>EN REVISION</span>',
        'VENCIDO'      => '<span class="req-badge badge-vencido"><i class="bi bi-exclamation-triangle-fill"></i>VENCIDO</span>',
        'INVALIDO'     => '<span class="req-badge badge-invalido"><i class="bi bi-x-circle-fill"></i>INVALIDO</span>',
        'FALTANTE'     => '<span class="req-badge badge-faltante"><i class="bi bi-dash-circle"></i>FALTANTE</span>',
        'ESPECIFICO'   => '<span class="req-badge badge-especifico"><i class="bi bi-pin-fill"></i>ESPECIFICO</span>',
        'EXCLUSIVO'    => '<span class="req-badge badge-exclusivo"><i class="bi bi-lock-fill"></i>EXCLUSIVO</span>',
        default        => '<span class="req-badge badge-faltante">' . s($clasificacion) . '</span>',
    };
}
?>

<div class="tramite-hero-card mb-4">
    <div class="tramite-hero-body">
        <div class="row g-4 align-items-center">
            <div class="col-md-8">
                <div class="tramite-hero-meta">
                    <span><i class="bi bi-folder2-open me-1"></i>Expediente: <strong><?= s($tramite['numero_expediente']) ?></strong></span>
                    <span><i class="bi bi-hash me-1"></i>Tramite: <strong><?= s($tramite['numero_tramite']) ?></strong></span>
                </div>
                <h1 class="tramite-hero-title"><?= s($tramite['tipo_tramite_nombre']) ?></h1>
                <p class="tramite-hero-text">
                    <?php if ($tramite['clave_catastral']): ?>
                    <i class="bi bi-geo-alt me-1"></i><?= s($tramite['clave_catastral']) ?> - <?= s($tramite['calle'] . ' ' . $tramite['numero_exterior']) ?>
                    <?php else: ?>
                    Consulte los requisitos documentales asociados a este tramite y cargue la evidencia necesaria para continuar.
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="tramite-status-label">Estado del tramite</div>
                <span class="tramite-status-badge"><?= s(str_replace('_', ' ', ucfirst($tramite['estado']))) ?></span>
                <div class="tramite-status-date">Fecha de inicio: <?= s($tramite['fecha_inicio']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
// Alias locales legibles del array de completitud
$totOblig   = (int)$completitud['total_obligatorios'];
$aprobados  = (int)$completitud['aprobados'];
$enRevision = (int)$completitud['en_revision'];
$cargados   = (int)$completitud['cargados'];
$vencidos   = (int)$completitud['vencidos'];
$invalidos  = (int)$completitud['invalidos'];
$faltantes  = (int)$completitud['faltantes'];
$pctCargado = (int)$completitud['porcentaje_cargado'];
$pctAprob   = (int)$completitud['porcentaje_aprobado'];
$listo      = (bool)$completitud['listo_para_envio'];

// Ancho de cada segmento de la barra dual (aprobado verde + en revisión ámbar)
$wAprob   = $totOblig > 0 ? ($aprobados / $totOblig) * 100 : 0;
$wRevision= $totOblig > 0 ? ($enRevision / $totOblig) * 100 : 0;
?>
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <span class="fw-600 fs-sm">Avance documental</span>
            <span class="fw-700 <?= $listo ? 'text-success' : ($cargados > 0 ? 'text-dorado' : 'text-warning') ?>">
                <?= $cargados ?> / <?= $totOblig ?> cargados (<?= $pctCargado ?>%)
            </span>
        </div>

        <div class="doc-progress-bar doc-progress-bar-dual" role="progressbar"
             aria-label="Avance documental del tramite"
             aria-valuenow="<?= $pctCargado ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="doc-progress-fill doc-progress-fill-aprobado" style="width: <?= $wAprob ?>%;"></div>
            <div class="doc-progress-fill doc-progress-fill-pendiente" style="width: <?= $wRevision ?>%;"></div>
        </div>

        <?php
        // Sub-línea: desglose de conteos (oculta los que son 0)
        $desglose = [];
        $desglose[] = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>' . $aprobados . ' aprobados</span>';
        if ($enRevision > 0) {
            $desglose[] = '<span class="text-dorado"><i class="bi bi-hourglass-split me-1"></i>' . $enRevision . ' en revision</span>';
        }
        if ($faltantes > 0) {
            $desglose[] = '<span class="text-muted"><i class="bi bi-dash-circle me-1"></i>' . $faltantes . ' faltantes</span>';
        }
        if ($vencidos > 0) {
            $desglose[] = '<span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>' . $vencidos . ' vencidos</span>';
        }
        if ($invalidos > 0) {
            $desglose[] = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>' . $invalidos . ' rechazados</span>';
        }
        ?>
        <div class="mt-2 fs-xs d-flex flex-wrap gap-3">
            <?= implode('', $desglose) ?>
        </div>

        <?php if ($listo): ?>
            <?php $estadoTramite = $tramite['estado'] ?? ''; ?>
            <?php if ($estadoTramite === 'iniciado'): ?>
                <div class="alert alert-success mt-3 mb-0 py-2">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Documentacion completa y aprobada.</strong>
                            Puede enviar su tramite a revision oficial.
                        </div>
                        <form method="POST" action="<?= BASE_URL ?>/?modulo=contribuyente&accion=enviar_tramite"
                              data-confirm-submit="Una vez enviado, el tramite pasa a revision oficial y no podra modificarse hasta ser dictaminado. ¿Desea continuar?">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="tramite_id" value="<?= (int)$tramite['id'] ?>">
                            <button type="submit" class="btn-custom btn-custom-vino">
                                <i class="bi bi-send-fill me-1"></i>Enviar tramite a revision
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-3 mb-0 py-2">
                    <i class="bi bi-info-circle me-2"></i>
                    Este tramite se encuentra en estado <strong><?= s($estadoTramite) ?></strong> y no requiere envio adicional.
                </div>
            <?php endif; ?>
        <?php elseif ($faltantes == 0 && $vencidos == 0 && $invalidos == 0 && $enRevision > 0): ?>
            <div class="alert alert-info mt-3 mb-0 py-2">
                <i class="bi bi-hourglass-split me-2"></i>
                Todos los documentos obligatorios estan cargados. Quedan <strong><?= $enRevision ?></strong> en revision por parte del personal validador.
            </div>
        <?php elseif ($totOblig == 0): ?>
            <div class="alert alert-secondary mt-3 mb-0 py-2">
                <i class="bi bi-info-circle me-2"></i>
                Este tramite no tiene requisitos documentales obligatorios configurados.
            </div>
        <?php else: ?>
            <?php
            $partes = [];
            if ($faltantes > 0) { $partes[] = $faltantes . ' por subir'; }
            if ($vencidos  > 0) { $partes[] = $vencidos  . ' vencidos'; }
            if ($invalidos > 0) { $partes[] = $invalidos . ' rechazados'; }
            $msg = implode(' / ', $partes);
            ?>
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Documentacion incompleta: <strong><?= s($msg) ?></strong>. Cargue la evidencia indicada para continuar.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primario text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span><i class="bi bi-list-check me-2"></i>Requisitos documentales (<?= count($requisitos) ?>)</span>
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= (int)$tramite['id'] ?>&expediente_id=<?= (int)$tramite['expediente_id'] ?>" class="btn btn-sm btn-light">
            <i class="bi bi-cloud-upload me-1"></i>Subir documento
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requisitos)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>No hay requisitos configurados para este tipo de tramite</h5>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="tabla-requisitos">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Documento Requerido</th>
                        <th style="width: 150px;">Estado</th>
                        <th>Detalle / Documento</th>
                        <th style="width: 200px;">Accion</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requisitos as $i => $req): ?>
                    <tr>
                        <td class="text-muted fs-xs"><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-500"><?= s($req['tipo_documento_nombre']) ?></div>
                            <?php if ($req['descripcion_instruccion']): ?>
                            <div class="fs-xs text-muted mt-1"><?= s($req['descripcion_instruccion']) ?></div>
                            <?php endif; ?>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <?php if ($req['obligatorio']): ?>
                                <span class="badge bg-danger-subtle text-danger fs-xs">Obligatorio</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary fs-xs">Opcional</span>
                                <?php endif; ?>
                                <?php if ($req['requiere_vigencia'] && $req['meses_vigencia']): ?>
                                <span class="badge bg-info-subtle text-info fs-xs">Vigencia: <?= (int)$req['meses_vigencia'] ?> meses</span>
                                <?php endif; ?>
                                <?php if ($req['exclusivo_expediente']): ?>
                                <span class="badge bg-dark-subtle text-dark fs-xs">Exclusivo</span>
                                <?php elseif ($req['especifico_tramite']): ?>
                                <span class="badge bg-primary-subtle text-primary fs-xs">Especifico</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= badgeClasificacion($req['clasificacion']) ?></td>
                        <td>
                            <?php if ($req['documento']): ?>
                            <?php $doc = $req['documento']; ?>
                            <div class="fs-xs">
                                <div class="fw-500 text-truncate" style="max-width: 220px;" title="<?= s($doc['nombre_original'] ?? $doc['nombre_archivo']) ?>">
                                    <i class="bi bi-file-earmark-fill me-1 text-muted"></i><?= s($doc['nombre_original'] ?? $doc['nombre_archivo']) ?>
                                </div>
                                <?php if ($doc['fecha_emision']): ?>
                                <div class="text-muted">Emitido: <?= s($doc['fecha_emision']) ?></div>
                                <?php endif; ?>
                                <?php if ($doc['fecha_vigencia']): ?>
                                <?php $esVencido = $doc['fecha_vigencia'] < date('Y-m-d'); ?>
                                <div class="<?= $esVencido ? 'text-danger fw-500' : 'text-success' ?>">
                                    Vence: <?= s($doc['fecha_vigencia']) ?>
                                    <?= $esVencido ? ' <i class="bi bi-exclamation-triangle-fill"></i>' : '' ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($doc['numero_folio']): ?>
                                <div class="text-muted">Folio: <?= s($doc['numero_folio']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted fs-xs"><i class="bi bi-dash"></i> Sin documento</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $tid_p = (int)$tramite['id'];
                            $eid_p = (int)$tramite['expediente_id'];
                            $tipo_doc_id = (int)$req['tipo_documento_id'];
                            $padreId = $req['documento']['id'] ?? null;
                            ?>
                            <?php if ($req['clasificacion'] === 'CUMPLE'): ?>
                                <?php if ($req['documento']): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_documento&id=<?= (int)$req['documento']['id'] ?>" target="_blank" rel="noopener" class="btn-accion-doc btn btn-sm btn-outline-success">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <?php endif; ?>
                            <?php elseif ($req['clasificacion'] === 'PENDIENTE'): ?>
                                <?php if ($req['documento']): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_documento&id=<?= (int)$req['documento']['id'] ?>" target="_blank" rel="noopener" class="btn-accion-doc btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= $tid_p ?>&expediente_id=<?= $eid_p ?>&tipo_doc_id=<?= $tipo_doc_id ?>&padre_doc_id=<?= $padreId ?>" class="btn-accion-doc btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat"></i> Reemplazar
                                </a>
                                <?php endif; ?>
                            <?php elseif ($req['clasificacion'] === 'REUTILIZABLE'): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= $tid_p ?>&expediente_id=<?= $eid_p ?>&tipo_doc_id=<?= $tipo_doc_id ?>" class="btn-accion-doc btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-arrow-repeat"></i> Confirmar uso
                                </a>
                                <?php if ($req['documento']): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_documento&id=<?= (int)$req['documento']['id'] ?>" target="_blank" rel="noopener" class="btn-accion-doc btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <?php endif; ?>
                            <?php elseif ($req['clasificacion'] === 'VENCIDO'): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= $tid_p ?>&expediente_id=<?= $eid_p ?>&tipo_doc_id=<?= $tipo_doc_id ?>&padre_doc_id=<?= $padreId ?>" class="btn-accion-doc btn btn-sm btn-warning text-dark">
                                    <i class="bi bi-arrow-clockwise"></i> Nueva version
                                </a>
                            <?php elseif ($req['clasificacion'] === 'INVALIDO'): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= $tid_p ?>&expediente_id=<?= $eid_p ?>&tipo_doc_id=<?= $tipo_doc_id ?>&padre_doc_id=<?= $padreId ?>" class="btn-accion-doc btn btn-sm btn-danger">
                                    <i class="bi bi-cloud-upload"></i> Subir documento
                                </a>
                            <?php elseif (in_array($req['clasificacion'], ['FALTANTE', 'ESPECIFICO', 'EXCLUSIVO'], true)): ?>
                                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&tramite_id=<?= $tid_p ?>&expediente_id=<?= $eid_p ?>&tipo_doc_id=<?= $tipo_doc_id ?>" class="btn-accion-doc btn btn-sm btn-primary">
                                    <i class="bi bi-cloud-upload-fill"></i> Subir documento
                                </a>
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

<div class="card mt-3">
    <div class="card-body">
        <h6 class="fw-600 fs-sm mb-3">Leyenda de estados documentales</h6>
        <div class="d-flex flex-wrap gap-2">
            <span class="req-badge badge-cumple"><i class="bi bi-check-circle-fill"></i>CUMPLE - Existe, aprobado y vigente</span>
            <span class="req-badge badge-reutilizable"><i class="bi bi-arrow-repeat"></i>REUTILIZABLE - Puede usarse desde otro expediente</span>
            <span class="req-badge badge-pendiente"><i class="bi bi-hourglass-split"></i>EN REVISION - Documento cargado, esperando validacion</span>
            <span class="req-badge badge-vencido"><i class="bi bi-exclamation-triangle-fill"></i>VENCIDO - Requiere nueva version</span>
            <span class="req-badge badge-invalido"><i class="bi bi-x-circle-fill"></i>INVALIDO - Fue rechazado en validacion</span>
            <span class="req-badge badge-faltante"><i class="bi bi-dash-circle"></i>FALTANTE - Aun no se ha cargado</span>
            <span class="req-badge badge-especifico"><i class="bi bi-pin-fill"></i>ESPECIFICO - Obligatorio para este tramite, sin documento aun</span>
            <span class="req-badge badge-exclusivo"><i class="bi bi-lock-fill"></i>EXCLUSIVO - Evidencia unica y exclusiva del expediente</span>
        </div>
    </div>
</div>
