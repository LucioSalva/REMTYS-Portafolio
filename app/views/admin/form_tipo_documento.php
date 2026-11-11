<?php /* Admin: Formulario Tipo de Documento */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_documento" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= s($titulo) ?></h5>
</div>

<div class="card" style="max-width:620px;">
    <div class="card-body">
        <?php
        $esEdicion = !empty($tipoDoc['id']);
        $accionForm = $esEdicion
            ? BASE_URL . '/?modulo=admin&accion=editar_tipo_documento&id=' . (int)$tipoDoc['id']
            : BASE_URL . '/?modulo=admin&accion=nuevo_tipo_documento';
        ?>
        <form method="POST" action="<?= $accionForm ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Clave <span class="required">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="clave"
                           value="<?= s($tipoDoc['clave'] ?? '') ?>"
                           required maxlength="50" placeholder="INE_IFE"
                           <?= $esEdicion ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?= s($tipoDoc['nombre'] ?? '') ?>"
                           required maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="2" maxlength="500"><?= s($tipoDoc['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Formatos Permitidos</label>
                    <input type="text" class="form-control" name="extensiones_permitidas"
                           value="<?= s($tipoDoc['extensiones_permitidas'] ?? 'pdf,jpg,jpeg,png') ?>"
                           placeholder="pdf,jpg,jpeg,png">
                    <div class="form-text fs-xs">Separados por coma, sin puntos.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tamaño Máx (MB)</label>
                    <input type="number" class="form-control" name="tamano_maximo_mb"
                           value="<?= (int)($tipoDoc['tamano_maximo_mb'] ?? 10) ?>"
                           min="1" max="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Meses Vigencia</label>
                    <input type="number" class="form-control" name="meses_vigencia"
                           value="<?= s($tipoDoc['meses_vigencia'] ?? '') ?>"
                           min="1" max="120" placeholder="Ej: 6">
                </div>
                <div class="col-12">
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="reutilizable" name="reutilizable"
                                   <?= ($tipoDoc['reutilizable'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="reutilizable">Reutilizable entre expedientes</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="req_vig" name="requiere_vigencia"
                                   <?= ($tipoDoc['requiere_vigencia'] ?? false) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="req_vig">Requiere fecha de vigencia</label>
                        </div>
                        <?php if ($esEdicion): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activoTD" name="activo"
                                   <?= ($tipoDoc['activo'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activoTD">Activo</label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i><?= $esEdicion ? 'Guardar Cambios' : 'Crear Tipo' ?>
                </button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_documento" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
