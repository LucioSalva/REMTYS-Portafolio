<?php /* Admin: Gestión de Requisitos Documentales */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=catalogos" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-list-check me-2 text-primario"></i>Requisitos Documentales</h5>
</div>

<!-- Selector de tipo de trámite -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="requisitos">
            <label class="form-label mb-0 me-2 fw-500 fs-sm text-nowrap">Tipo de Trámite:</label>
            <select class="form-select" name="tipo_tramite_id" data-submit-on-change>
                <option value="">-- Seleccione tipo de trámite --</option>
                <?php foreach ($tiposTramite as $tt): ?>
                <option value="<?= (int)$tt['id'] ?>" <?= (int)($tipoTramiteSeleccionado ?? 0) === (int)$tt['id'] ? 'selected' : '' ?>>
                    <?= s($tt['nombre']) ?> (<?= s($tt['clave']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($tipoTramiteSeleccionado): ?>

<!-- Formulario para agregar nuevo requisito -->
<div class="card mb-3">
    <div class="card-header fs-sm"><i class="bi bi-plus-circle me-2"></i>Agregar Requisito</div>
    <div class="card-body">
        <?php
        $db = getDBConnection();
        $tiposDocAll = $db->query('SELECT id, nombre, clave FROM tipos_documento WHERE activo = TRUE ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_requisito" class="row g-2 align-items-end">
            <?= campoCSRF() ?>
            <input type="hidden" name="tipo_tramite_id" value="<?= (int)$tipoTramiteSeleccionado ?>">
            <div class="col-md-4">
                <label class="form-label fs-xs">Tipo de Documento <span class="required">*</span></label>
                <select class="form-select form-select-sm" name="tipo_documento_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($tiposDocAll as $td): ?>
                    <option value="<?= (int)$td['id'] ?>"><?= s($td['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-xs">Instrucción</label>
                <input type="text" class="form-control form-control-sm" name="descripcion_instruccion" maxlength="300" placeholder="Texto de ayuda...">
            </div>
            <div class="col-md-1">
                <label class="form-label fs-xs">Orden</label>
                <input type="number" class="form-control form-control-sm" name="orden_visualizacion" value="0" min="0" max="999">
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-3 pb-1">
                    <div class="form-check form-check-sm">
                        <input type="checkbox" class="form-check-input" id="chkOblig" name="obligatorio" checked>
                        <label class="form-check-label fs-xs" for="chkOblig">Obligatorio</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input type="checkbox" class="form-check-input" id="chkEspec" name="especifico_tramite">
                        <label class="form-check-label fs-xs" for="chkEspec">Específico</label>
                    </div>
                    <div class="form-check form-check-sm">
                        <input type="checkbox" class="form-check-input" id="chkExcl" name="exclusivo_expediente">
                        <label class="form-check-label fs-xs" for="chkExcl">Exclusivo</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100 mt-1">
                    <i class="bi bi-plus me-1"></i>Agregar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de requisitos actuales -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Requisitos para: <strong><?= count($requisitos) > 0 ? s($requisitos[0]['tipo_tramite_nombre'] ?? '') : 'Trámite seleccionado' ?></strong></span>
        <span class="badge bg-primary"><?= count($requisitos) ?> requisito(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requisitos)): ?>
        <div class="empty-state p-4">
            <i class="bi bi-list-check"></i>
            <h5>Sin requisitos configurados</h5>
            <p class="fs-sm">Agregue requisitos usando el formulario de arriba.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Orden</th><th>Documento</th><th>Obligatorio</th><th>Específico</th><th>Exclusivo</th><th>Instrucción</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($requisitos as $r): ?>
                <tr>
                    <td class="text-center fw-500"><?= (int)$r['orden_visualizacion'] ?></td>
                    <td>
                        <div class="fw-500"><?= s($r['tipo_doc_nombre']) ?></div>
                        <div class="fs-xs text-muted"><?= s($r['tipo_doc_nombre']) ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $r['obligatorio'] ? 'bg-danger' : 'bg-secondary' ?> fs-xs">
                            <?= $r['obligatorio'] ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $r['especifico_tramite'] ? 'bg-primary' : 'bg-light text-dark border' ?> fs-xs">
                            <?= $r['especifico_tramite'] ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $r['exclusivo_expediente'] ? 'bg-dark' : 'bg-light text-dark border' ?> fs-xs">
                            <?= $r['exclusivo_expediente'] ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <td class="fs-xs text-muted"><?= s($r['descripcion_instruccion'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=eliminar_requisito" class="d-inline">
                            <?= campoCSRF() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="tipo_tramite_id" value="<?= (int)$tipoTramiteSeleccionado ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    data-confirm="¿Eliminar este requisito?">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-arrow-up me-2"></i>Seleccione un tipo de trámite para gestionar sus requisitos documentales.
</div>
<?php endif; ?>
