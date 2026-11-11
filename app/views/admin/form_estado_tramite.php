<?php /* Admin: Editar estado del trámite */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_tramite&id=<?= (int)$tramite['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Editar Trámite <?= s($tramite['numero_tramite']) ?></h5>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=editar_tramite&id=<?= (int)$tramite['id'] ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>
            <div class="mb-3">
                <label class="form-label">Estado <span class="required">*</span></label>
                <select class="form-select" name="estado" required>
                    <?php foreach (['iniciado','en_revision','documentacion_pendiente','aprobado','rechazado','cancelado'] as $est): ?>
                    <option value="<?= $est ?>" <?= $tramite['estado'] === $est ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" name="observaciones" rows="4"><?= s($tramite['observaciones'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Guardar</button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_tramite&id=<?= (int)$tramite['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
