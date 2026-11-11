<?php /* Admin: Formulario Nuevo Expediente */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Nuevo Expediente</h5>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_expediente" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label class="form-label">Contribuyente <span class="required">*</span></label>
                <select class="form-select" name="contribuyente_id" required>
                    <option value="">-- Seleccione contribuyente --</option>
                    <?php foreach ($contribs as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= s($c['nombre']) ?> — <?= s($c['rfc']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Seleccione un contribuyente.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo de Trámite <span class="required">*</span></label>
                <select class="form-select" name="tipo_tramite_id" required>
                    <option value="">-- Seleccione tipo de trámite --</option>
                    <?php foreach ($tiposTramite as $tt): ?>
                    <option value="<?= (int)$tt['id'] ?>"><?= s($tt['nombre']) ?> (<?= s($tt['clave']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Seleccione el tipo de trámite.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Predio Relacionado</label>
                <select class="form-select" name="predio_id">
                    <option value="">-- Sin predio específico --</option>
                    <?php foreach ($predios as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"><?= s($p['clave_catastral']) ?> — <?= s($p['calle'] . ' ' . $p['numero_exterior']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción / Observaciones</label>
                <textarea class="form-control" name="descripcion" rows="3" maxlength="500"
                          placeholder="Motivo del expediente..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-folder-plus me-2"></i>Crear Expediente
                </button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
