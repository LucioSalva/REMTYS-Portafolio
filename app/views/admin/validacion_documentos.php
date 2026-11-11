<?php /* Admin: Cola de Validación Documental */ ?>
<?php
/**
 * Helpers locales solo para esta vista — clasificacion visual del tipo
 * de archivo y formato humano de tamano. No se reutilizan fuera.
 */
if (!function_exists('__validacion_tipo_archivo')) {
    /**
     * Detecta el tipo visual (pdf|image|otro) a partir del mime + extension.
     */
    function __validacion_tipo_archivo(?string $mime, ?string $nombre): string {
        $mime = strtolower((string)$mime);
        if ($mime === 'application/pdf') return 'pdf';
        if ($mime === 'image/jpeg' || $mime === 'image/png') return 'image';
        $ext = strtolower(pathinfo((string)$nombre, PATHINFO_EXTENSION));
        if ($ext === 'pdf') return 'pdf';
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) return 'image';
        return 'otro';
    }
    function __validacion_formatear_tamano(?int $bytes): string {
        if (!$bytes || $bytes <= 0) return '';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 1) . ' KB';
        return number_format($bytes / 1048576, 2) . ' MB';
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-patch-check-fill me-2 text-primario"></i>Validación de Documentos</h5>
    <span class="badge bg-warning text-dark fs-sm"><?= count($documentos) ?> pendiente(s)</span>
</div>

<?php if (empty($documentos)): ?>
<div class="empty-state card p-5">
    <i class="bi bi-patch-check"></i>
    <h5>Sin documentos pendientes</h5>
    <p class="fs-sm">No hay documentos en cola de validación.</p>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($documentos as $doc):
    $docId         = (int)$doc['id'];
    $nombreOrig    = $doc['nombre_original'] ?? $doc['nombre_archivo'];
    $tipoVis       = __validacion_tipo_archivo($doc['mime_type'] ?? null, $nombreOrig);
    $tamanoHumano  = __validacion_formatear_tamano(isset($doc['tamano_bytes']) ? (int)$doc['tamano_bytes'] : null);
    // Verificacion de existencia fisica (defensivo) — la cola normalmente
    // trae pocos documentos pendientes, asi que el coste es aceptable.
    $rutaRel       = (string)($doc['ruta_archivo'] ?? '');
    $archivoExiste = ($rutaRel !== '') && is_file(STORAGE_PATH . '/' . ltrim($rutaRel, '/\\'));
    // URLs del endpoint seguro (preview inline y descarga forzada)
    $urlVer        = BASE_URL . '/?modulo=admin&accion=ver_documento&id=' . $docId;
    $urlDescargar  = $urlVer . '&descargar=1';
    $modalId       = 'verDocModal-' . $docId;
?>
<div class="col-md-6 col-lg-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-500 fs-sm text-truncate" style="max-width:200px"><?= s($doc['tipo_nombre']) ?></span>
            <span class="badge bg-warning text-dark fs-xs">Pendiente</span>
        </div>
        <div class="card-body">
            <div class="mb-2">
                <div class="fw-500 fs-sm"><?= s($doc['contribuyente_nombre']) ?></div>
                <div class="fs-xs text-muted"><code><?= s($doc['rfc']) ?></code></div>
            </div>

            <!-- Nombre + badge del tipo de archivo -->
            <div class="d-flex align-items-center gap-2 mb-1">
                <?php if ($tipoVis === 'pdf'): ?>
                    <span class="badge rounded-pill fs-xs d-inline-flex align-items-center gap-1"
                          style="background-color: var(--color-vino, #691C32); color: #fff;">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </span>
                <?php elseif ($tipoVis === 'image'): ?>
                    <span class="badge rounded-pill fs-xs d-inline-flex align-items-center gap-1"
                          style="background-color: var(--color-dorado, #BC955C); color: #fff;">
                        <i class="bi bi-file-earmark-image-fill"></i> <?= strtoupper(pathinfo((string)$nombreOrig, PATHINFO_EXTENSION)) ?: 'IMG' ?>
                    </span>
                <?php else: ?>
                    <span class="badge rounded-pill bg-secondary fs-xs d-inline-flex align-items-center gap-1">
                        <i class="bi bi-file-earmark"></i> ARCHIVO
                    </span>
                <?php endif; ?>
                <span class="text-truncate fs-xs text-muted" style="max-width:170px" title="<?= s($nombreOrig) ?>">
                    <?= s($nombreOrig) ?>
                </span>
            </div>

            <?php if ($doc['numero_folio']): ?>
            <div class="fs-xs text-muted mb-1"><i class="bi bi-hash me-1"></i>Folio: <?= s($doc['numero_folio']) ?></div>
            <?php endif; ?>
            <?php if ($doc['fecha_emision']): ?>
            <div class="fs-xs text-muted mb-1"><i class="bi bi-calendar me-1"></i>Emitido: <?= s($doc['fecha_emision']) ?></div>
            <?php endif; ?>
            <?php if ($doc['fecha_vigencia']): ?>
            <div class="fs-xs mb-1">
                <i class="bi bi-clock me-1"></i>Vence:
                <span class="<?= $doc['fecha_vigencia'] < date('Y-m-d') ? 'text-danger fw-500' : 'text-success' ?>">
                    <?= s($doc['fecha_vigencia']) ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="fs-xs text-muted mb-2">
                <i class="bi bi-calendar-plus me-1"></i>Subido: <?= s(substr($doc['created_at'] ?? '', 0, 10)) ?>
                <?php if ($tamanoHumano): ?>
                    <span class="ms-2"><i class="bi bi-hdd me-1"></i><?= s($tamanoHumano) ?></span>
                <?php endif; ?>
            </div>

            <!-- Acceso al archivo real para validar visualmente -->
            <?php if ($archivoExiste): ?>
                <button type="button"
                        class="btn-custom btn-custom-vino w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#<?= s($modalId) ?>">
                    <i class="bi bi-eye-fill me-1"></i> Ver documento
                </button>
            <?php else: ?>
                <div class="alert alert-warning py-2 mb-0 fs-xs" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Archivo no disponible en el servidor.
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top p-2">
            <!-- Botones de acción rápida -->
            <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=validar_documento" class="d-flex gap-1 mb-2">
                <?= campoCSRF() ?>
                <input type="hidden" name="documento_id" value="<?= $docId ?>">
                <input type="hidden" name="resultado" value="aprobado">
                <input type="hidden" name="observaciones" value="">
                <button type="submit" class="btn btn-sm btn-success flex-fill"
                        data-confirm="¿Aprobar este documento?">
                    <i class="bi bi-check-circle me-1"></i>Aprobar
                </button>
            </form>

            <!-- Formulario rechazo con observaciones -->
            <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=validar_documento">
                <?= campoCSRF() ?>
                <input type="hidden" name="documento_id" value="<?= $docId ?>">
                <input type="hidden" name="resultado" value="rechazado">
                <textarea class="form-control form-control-sm mb-1" name="observaciones" rows="2"
                          placeholder="Motivo del rechazo (requerido)..." required></textarea>
                <button type="submit" class="btn btn-sm btn-danger w-100"
                        data-confirm="¿Rechazar este documento?">
                    <i class="bi bi-x-circle me-1"></i>Rechazar
                </button>
            </form>
        </div>
    </div>
</div>

<?php if ($archivoExiste): ?>
<!-- Modal de previsualizacion del documento -->
<div class="modal fade" id="<?= s($modalId) ?>" tabindex="-1" aria-labelledby="<?= s($modalId) ?>-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="<?= s($modalId) ?>-label">
                    <?php if ($tipoVis === 'pdf'): ?>
                        <i class="bi bi-file-earmark-pdf-fill" style="color: var(--color-vino, #691C32);"></i>
                    <?php elseif ($tipoVis === 'image'): ?>
                        <i class="bi bi-file-earmark-image-fill" style="color: var(--color-dorado, #BC955C);"></i>
                    <?php else: ?>
                        <i class="bi bi-file-earmark"></i>
                    <?php endif; ?>
                    <span class="text-truncate" style="max-width: 500px;"><?= s($nombreOrig) ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0" style="background:#f5f5f5;">
                <?php if ($tipoVis === 'pdf'): ?>
                    <iframe src="<?= s($urlVer) ?>"
                            title="Previsualizacion de <?= s($nombreOrig) ?>"
                            style="width:100%; min-height:70vh; border:0; display:block;"></iframe>
                <?php elseif ($tipoVis === 'image'): ?>
                    <div class="d-flex align-items-center justify-content-center p-3" style="min-height:70vh;">
                        <img src="<?= s($urlVer) ?>"
                             alt="<?= s($nombreOrig) ?>"
                             style="max-width:100%; max-height:75vh; height:auto; object-fit:contain;">
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-file-earmark fs-1"></i>
                        <p class="mb-0 mt-2">Vista previa no disponible para este tipo de archivo.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="<?= s($urlDescargar) ?>" target="_blank" rel="noopener"
                   class="btn-custom btn-custom-dorado">
                    <i class="bi bi-download me-1"></i> Descargar
                </a>
                <button type="button" class="btn-custom btn-custom-outline-vino" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>
<?php endif; ?>
