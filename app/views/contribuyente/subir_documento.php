<?php /* Vista: Subir Documento */ ?>
<?php
/*
 * Construcción del action del form:
 * El contexto del requisito (tramite_id, expediente_id, tipo_doc_id, padre_doc_id)
 * SE INCLUYE EN LA URL DEL ACTION (no solo como hidden del POST).
 *
 * Razón: si PHP descarta $_POST (por post_max_size, CSRF inválido, back/forward
 * del navegador, etc.) el contexto SOBREVIVE en $_GET al caer de nuevo al GET
 * handler del controlador. Este es el único esquema a prueba de fallos previos
 * al parseo del POST.
 */
$qsAction = ['modulo' => 'contribuyente', 'accion' => 'subir_documento'];
if ($tramiteId)                     $qsAction['tramite_id']    = (int)$tramiteId;
if ($expedienteId)                  $qsAction['expediente_id'] = (int)$expedienteId;
if (!empty($tipoForzado['id']))     $qsAction['tipo_doc_id']   = (int)$tipoForzado['id'];
if ($padreId)                       $qsAction['padre_doc_id']  = (int)$padreId;
$formAction = BASE_URL . '/?' . http_build_query($qsAction);
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=documentos" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">Subir Documento</h5>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primario text-white">
                <i class="bi bi-cloud-upload me-2"></i>Carga de Documento
            </div>
            <div class="card-body">
                <form method="POST"
                      action="<?= s($formAction) ?>"
                      enctype="multipart/form-data"
                      class="needs-validation" novalidate>
                    <?= campoCSRF() ?>

                    <?php if ($tramiteId): ?>
                    <input type="hidden" name="tramite_id" value="<?= (int)$tramiteId ?>">
                    <?php endif; ?>
                    <?php if ($expedienteId): ?>
                    <input type="hidden" name="expediente_id" value="<?= (int)$expedienteId ?>">
                    <?php endif; ?>
                    <?php if ($padreId): ?>
                    <input type="hidden" name="padre_documento_id" value="<?= (int)$padreId ?>">
                    <div class="alert alert-info mb-3 fs-sm">
                        <i class="bi bi-arrow-clockwise me-2"></i>Está subiendo una <strong>nueva versión</strong> del documento.
                        La versión anterior quedará inactiva automáticamente.
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Documento <span class="required">*</span></label>
                        <?php if ($tipoForzado): ?>
                        <!-- Modo contextual: tipo fijo desde requisito (irreversible) -->
                        <input type="hidden" name="tipo_documento_id" value="<?= (int)$tipoForzado['id'] ?>">
                        <input type="hidden" name="tipo_doc_forzado" value="<?= (int)$tipoForzado['id'] ?>">
                        <input type="text" class="form-control" value="<?= s($tipoForzado['nombre']) ?>" readonly disabled>
                        <div class="form-text fs-xs"><i class="bi bi-lock-fill me-1"></i>Tipo de documento determinado por el requisito. No se puede cambiar.</div>
                        <div id="docInfo" class="mt-1 fs-xs text-muted">
                            Formatos permitidos: <strong id="docExts"><?= s($tipoForzado['extensiones_permitidas']) ?></strong> | Tamaño máximo: <strong id="docMax"><?= (int)$tipoForzado['tamano_maximo_mb'] ?></strong> MB
                        </div>
                        <?php else: ?>
                        <!-- Modo genérico: selector libre -->
                        <select class="form-select" name="tipo_documento_id" required id="tipoDocSelect">
                            <option value="">-- Seleccione el tipo de documento --</option>
                            <?php foreach ($tiposDocumento as $td): ?>
                            <option value="<?= (int)$td['id'] ?>"
                                    data-exts="<?= s($td['extensiones_permitidas']) ?>"
                                    data-vigencia="<?= $td['requiere_vigencia'] ? '1' : '0' ?>"
                                    data-meses="<?= (int)$td['meses_vigencia'] ?>"
                                    data-max="<?= (int)$td['tamano_maximo_mb'] ?>">
                                <?= s($td['nombre']) ?>
                                <?= $td['requiere_vigencia'] ? ' (requiere vigencia)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="docInfo" class="mt-1 fs-xs text-muted d-none">
                            Formatos permitidos: <strong id="docExts"></strong> | Tamaño máximo: <strong id="docMax"></strong> MB
                        </div>
                        <div class="invalid-feedback">Seleccione el tipo de documento.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Zona de carga -->
                    <div class="mb-3">
                        <label class="form-label">Archivo <span class="required">*</span></label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="archivo" id="archivoInput" class="d-none" required
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <!-- Estado vacío (sin archivo) -->
                            <div class="upload-zone-empty">
                                <i class="bi bi-cloud-upload-fill"></i>
                                <div class="upload-zone-empty-title">Arrastra tu archivo aquí o haz clic para seleccionar</div>
                                <div class="upload-zone-empty-hint">Formatos: PDF, JPG, PNG | Máx. 20 MB</div>
                            </div>
                            <!-- Estado error (archivo rechazado) -->
                            <div class="upload-zone-error d-none">
                                <i class="bi bi-x-circle-fill upload-zone-error-icon"></i>
                                <div class="upload-zone-error-title">Archivo no válido</div>
                                <div class="upload-zone-error-detail"></div>
                                <div class="upload-zone-error-action">
                                    <i class="bi bi-arrow-repeat"></i> Haga clic para seleccionar otro archivo
                                </div>
                            </div>
                            <!-- Estado con archivo seleccionado -->
                            <div class="upload-zone-file d-none">
                                <div class="upload-zone-file-check">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="upload-zone-file-details">
                                    <div class="upload-zone-file-icon-name">
                                        <i class="bi bi-file-earmark-check-fill upload-zone-file-icon"></i>
                                        <span class="upload-zone-file-name">archivo.pdf</span>
                                    </div>
                                    <div class="upload-zone-file-size">0 KB</div>
                                    <div class="upload-zone-file-status">
                                        <i class="bi bi-check-lg"></i> Archivo seleccionado correctamente
                                    </div>
                                </div>
                                <a href="#" class="upload-zone-change" id="uploadZoneChange">
                                    <i class="bi bi-arrow-repeat"></i> Cambiar archivo
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Número de Folio / Referencia</label>
                            <input type="text" class="form-control" name="numero_folio"
                                   placeholder="Ej: 2024-00123" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Emisión</label>
                            <input type="date" class="form-control" name="fecha_emision"
                                   max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6" id="campoVigencia">
                            <label class="form-label">Fecha de Vigencia</label>
                            <input type="date" class="form-control" name="fecha_vigencia"
                                   min="<?= date('Y-m-d') ?>">
                            <div class="form-text fs-xs">Requerida si el documento tiene fecha de vencimiento.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload-fill me-2"></i>Subir Documento
                        </button>
                        <?php if ($tramiteId): ?>
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=requisitos_tramite&id=<?= (int)$tramiteId ?>"
                           class="btn btn-outline-secondary">Cancelar</a>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=documentos"
                           class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header fs-sm"><i class="bi bi-shield-check me-2"></i>Buenas Prácticas</div>
            <div class="card-body fs-sm">
                <ul class="ps-3">
                    <li class="mb-2">Suba documentos en formato <strong>PDF</strong> cuando sea posible.</li>
                    <li class="mb-2">Asegúrese de que el documento sea <strong>legible y completo</strong>.</li>
                    <li class="mb-2">No suba imágenes borrosas o cortadas.</li>
                    <li class="mb-2">Si el documento tiene <strong>fecha de vigencia</strong>, ingrésela para rastreo automático.</li>
                    <li>El documento quedará en estado <strong>Pendiente de Validación</strong> hasta que un revisor lo apruebe.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
(function() {
    var tipoDocSelect = document.getElementById('tipoDocSelect');
    var vigEl = document.getElementById('campoVigencia');

    <?php if ($tipoForzado): ?>
    // Modo contextual: configurar vigencia según tipo forzado
    <?php if ($tipoForzado['requiere_vigencia']): ?>
    vigEl.querySelector('input').required = true;
    vigEl.querySelector('.form-text').textContent = 'Obligatoria para este tipo de documento.';
    <?php endif; ?>
    <?php else: ?>
    // Modo genérico: select dinámico
    if (tipoDocSelect) {
        tipoDocSelect.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            var info = document.getElementById('docInfo');
            if (this.value) {
                document.getElementById('docExts').textContent = opt.dataset.exts || 'pdf,jpg,png';
                document.getElementById('docMax').textContent = opt.dataset.max || '10';
                info.classList.remove('d-none');
                var reqVig = opt.dataset.vigencia === '1';
                vigEl.querySelector('input').required = reqVig;
                vigEl.querySelector('.form-text').textContent = reqVig
                    ? 'Obligatoria para este tipo de documento.'
                    : 'Requerida si el documento tiene fecha de vencimiento.';
            } else {
                info.classList.add('d-none');
            }
        });
    }
    <?php endif; ?>
})();
</script>
