<?php /* Admin: Formulario Nuevo Trámite */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$expedienteId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Nuevo Trámite</h5>
</div>

<div class="card" style="max-width:580px;">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tramite&expediente_id=<?= (int)$expedienteId ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>
            <div class="mb-3">
                <label class="form-label">Tipo de Trámite <span class="required">*</span></label>
                <select class="form-select" name="tipo_tramite_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($tiposTramite as $tt): ?>
                    <option value="<?= (int)$tt['id'] ?>"><?= s($tt['nombre']) ?> (<?= s($tt['clave']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Requerido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="3" maxlength="500"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-file-plus me-2"></i>Crear Trámite</button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$expedienteId ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
