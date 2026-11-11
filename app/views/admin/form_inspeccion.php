<?php /* Admin: Nueva Inspección */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=inspecciones" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Nueva Inspección</h5>
</div>

<div class="card" style="max-width:620px;">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nueva_inspeccion" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label class="form-label">Expediente <span class="required">*</span></label>
                <select class="form-select" name="expediente_id" required>
                    <option value="">-- Seleccione expediente --</option>
                    <?php foreach ($expedientes as $exp): ?>
                    <option value="<?= (int)$exp['id'] ?>"><?= s($exp['numero_expediente']) ?> — <?= s($exp['contrib']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Requerido.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo de Inspección <span class="required">*</span></label>
                <input type="text" class="form-control" name="tipo_inspeccion" required maxlength="100"
                       placeholder="Ej: Verificación ocular, Inspección física, Medición de predio...">
                <div class="invalid-feedback">Requerido.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Fecha Programada</label>
                    <input type="date" class="form-control" name="programada_para"
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Inspector Asignado</label>
                    <select class="form-select" name="inspector_id">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($inspectores as $ins): ?>
                        <option value="<?= (int)$ins['id'] ?>"><?= s($ins['nombre_completo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-clipboard2-plus me-2"></i>Programar Inspección</button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=inspecciones" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
