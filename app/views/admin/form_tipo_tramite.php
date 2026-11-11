<?php /* Admin: Formulario Tipo de Trámite */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_tramite" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= s($titulo) ?></h5>
</div>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php
        $esEdicion = !empty($tipoTramite['id']);
        $accionForm = $esEdicion
            ? BASE_URL . '/?modulo=admin&accion=editar_tipo_tramite&id=' . (int)$tipoTramite['id']
            : BASE_URL . '/?modulo=admin&accion=nuevo_tipo_tramite';
        ?>
        <form method="POST" action="<?= $accionForm ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Clave <span class="required">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="clave"
                           value="<?= s($tipoTramite['clave'] ?? '') ?>"
                           required maxlength="50" placeholder="LIC_CONST"
                           <?= $esEdicion ? 'readonly' : '' ?>>
                    <div class="form-text fs-xs">Identificador único, sin espacios.</div>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nombre"
                           value="<?= s($tipoTramite['nombre'] ?? '') ?>"
                           required maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3" maxlength="1000"><?= s($tipoTramite['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Dependencia Responsable</label>
                    <input type="text" class="form-control" name="dependencia"
                           value="<?= s($tipoTramite['dependencia'] ?? '') ?>"
                           placeholder="Dirección de Desarrollo Urbano" maxlength="255">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Días de Resolución</label>
                    <input type="number" class="form-control" name="dias_resolucion"
                           value="<?= (int)($tipoTramite['dias_resolucion'] ?? 15) ?>"
                           min="1" max="365">
                </div>
                <?php if ($esEdicion): ?>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="activoTT" name="activo"
                               <?= $tipoTramite['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activoTT">Activo</label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i><?= $esEdicion ? 'Guardar Cambios' : 'Crear Tipo' ?>
                </button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_tramite" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
