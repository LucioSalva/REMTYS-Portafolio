<?php /* Admin: Editar Inspección */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=inspecciones" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Editar Inspección #<?= (int)$inspeccion['id'] ?></h5>
</div>

<div class="card" style="max-width:580px;">
    <div class="card-body">
        <div class="mb-3 p-2 bg-light rounded fs-sm">
            <strong>Expediente:</strong> <?= s($inspeccion['numero_expediente']) ?>
            &nbsp;|&nbsp; <strong>Tipo:</strong> <?= s($inspeccion['tipo']) ?>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=editar_inspeccion&id=<?= (int)$inspeccion['id'] ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label class="form-label">Estado <span class="required">*</span></label>
                <select class="form-select" name="estado" required>
                    <?php foreach (['pendiente','programada','realizada','cancelada'] as $est): ?>
                    <option value="<?= $est ?>" <?= $inspeccion['estado'] === $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha Realizada</label>
                <input type="datetime-local" class="form-control" name="realizada_en"
                       value="<?= $inspeccion['realizada_en'] ? s(str_replace(' ','T',substr($inspeccion['realizada_en'],0,16))) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Resultado / Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="4"><?= s($inspeccion['observaciones'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar</button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=inspecciones" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
