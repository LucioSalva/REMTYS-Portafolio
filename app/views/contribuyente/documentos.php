<?php /* Vista: Mis Documentos */ ?>
<div class="page-intro">
    <div class="page-intro-content">
        <span class="page-intro-eyebrow">Control documental</span>
        <h1 class="page-intro-title">Mis Documentos</h1>
        <p class="page-intro-text">Mantenga su documentacion organizada, vigente y lista para reutilizarse en tramites futuros.</p>
    </div>
    <div class="page-intro-actions">
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento" class="btn btn-primary">
            <i class="bi bi-cloud-upload me-2"></i>Subir documento
        </a>
    </div>
</div>

<?php if (empty($documentos)): ?>
<div class="empty-state card p-4">
    <i class="bi bi-archive"></i>
    <h5>No tiene documentos cargados</h5>
    <p class="fs-sm">Suba sus documentos para reutilizarlos automaticamente en sus tramites y expedientes.</p>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento" class="btn btn-primary btn-sm">
        <i class="bi bi-cloud-upload me-1"></i>Subir documento
    </a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <span><i class="bi bi-files me-2"></i><?= count($documentos) ?> documento(s)</span>
        <input type="text" class="form-control form-control-sm" style="max-width: 240px;"
               placeholder="Buscar documento..." data-table-search="tablaDocumentos">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaDocumentos">
                <thead>
                    <tr>
                        <th>Tipo de Documento</th>
                        <th>Archivo</th>
                        <th>Folio</th>
                        <th>F. Emision</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th>Version</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($documentos as $doc): ?>
                    <?php
                    $hoy = date('Y-m-d');
                    $esVencido = $doc['fecha_vigencia'] && $doc['fecha_vigencia'] < $hoy;
                    $estadoBadge = match($doc['estado_validacion']) {
                        'aprobado' => $esVencido ? 'badge-vencido' : 'badge-cumple',
                        'rechazado' => 'badge-invalido',
                        'en_revision' => 'badge-pendiente',
                        default => 'badge-pendiente',
                    };
                    $estadoTexto = match($doc['estado_validacion']) {
                        'aprobado' => $esVencido ? 'VENCIDO' : 'APROBADO',
                        'rechazado' => 'RECHAZADO',
                        'en_revision' => 'EN REVISION',
                        default => 'PENDIENTE',
                    };
                    ?>
                    <tr>
                        <td>
                            <div class="fw-500"><?= s($doc['tipo_nombre']) ?></div>
                            <div class="fs-xs text-muted"><?= s($doc['tipo_clave']) ?></div>
                        </td>
                        <td>
                            <div class="fs-sm text-truncate" style="max-width: 220px;" title="<?= s($doc['nombre_original'] ?? $doc['nombre_archivo']) ?>">
                                <i class="bi bi-file-earmark-fill me-1 text-muted"></i>
                                <?= s($doc['nombre_original'] ?? $doc['nombre_archivo']) ?>
                            </div>
                        </td>
                        <td class="fs-xs"><?= s($doc['numero_folio'] ?? '-') ?></td>
                        <td class="fs-xs"><?= s($doc['fecha_emision'] ?? '-') ?></td>
                        <td class="fs-xs">
                            <?php if ($doc['fecha_vigencia']): ?>
                            <span class="<?= $esVencido ? 'text-danger fw-500' : 'text-success' ?>">
                                <?= s($doc['fecha_vigencia']) ?>
                                <?= $esVencido ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-check-circle-fill"></i>' ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">Sin vigencia</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="req-badge <?= $estadoBadge ?>">
                                <?= $estadoTexto ?>
                            </span>
                        </td>
                        <td class="fs-xs text-muted">v<?= (int)$doc['version'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=subir_documento&padre_doc_id=<?= (int)$doc['id'] ?>&tipo_doc_id=<?= (int)$doc['tipo_documento_id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="Subir nueva version"
                               data-bs-toggle="tooltip">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
