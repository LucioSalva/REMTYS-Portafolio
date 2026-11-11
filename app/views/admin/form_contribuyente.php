<?php /* Admin: Formulario Contribuyente (nuevo/editar) */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=contribuyentes" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0"><?= s($titulo) ?></h5>
</div>

<div class="card" style="max-width: 760px;">
    <div class="card-body">
        <?php
        $esEdicion = !empty($contribuyente['id']);
        $accionForm = $esEdicion
            ? BASE_URL . '/?modulo=admin&accion=editar_contribuyente&id=' . (int)$contribuyente['id']
            : BASE_URL . '/?modulo=admin&accion=nuevo_contribuyente';
        $tipoPersona = $contribuyente['tipo_persona'] ?? 'fisica';
        ?>
        <form method="POST" action="<?= $accionForm ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label class="form-label">Tipo de Persona <span class="required">*</span></label>
                <?php if ($esEdicion): ?>
                    <input type="hidden" name="tipo_persona" value="<?= s($tipoPersona) ?>">
                    <input type="text" class="form-control" value="<?= $tipoPersona === 'moral' ? 'Persona Moral' : 'Persona Fisica' ?>" readonly>
                <?php else: ?>
                    <select class="form-select" id="tipo_persona" name="tipo_persona">
                        <option value="fisica" <?= $tipoPersona === 'fisica' ? 'selected' : '' ?>>Persona Fisica</option>
                        <option value="moral" <?= $tipoPersona === 'moral' ? 'selected' : '' ?>>Persona Moral</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Nombre completo / Razon social <span class="required">*</span></label>
                <input type="text" class="form-control" name="nombre_completo"
                       value="<?= s($contribuyente['nombre_completo'] ?? '') ?>"
                       required minlength="3" maxlength="400">
                <div class="invalid-feedback">Requerido.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">RFC <?= !$esEdicion ? '<span class="required">*</span>' : '' ?></label>
                    <input type="text" class="form-control text-uppercase" name="rfc"
                           value="<?= s($contribuyente['rfc'] ?? '') ?>"
                           <?= !$esEdicion ? 'required' : 'readonly' ?>
                           maxlength="13" pattern="[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}">
                </div>
                <div class="col-md-6 campo-fisica">
                    <label class="form-label">CURP</label>
                    <input type="text" class="form-control text-uppercase" name="curp"
                           value="<?= s($contribuyente['curp'] ?? '') ?>"
                           maxlength="18" placeholder="XAXX010101HDFYYY00">
                </div>
                <div class="col-md-6 campo-moral">
                    <label class="form-label">Representante legal <span class="required">*</span></label>
                    <input type="text" class="form-control" name="representante_legal"
                           value="<?= s($contribuyente['representante_legal'] ?? '') ?>"
                           data-requiredMoral="true" maxlength="400">
                </div>
                <div class="col-md-6 campo-moral">
                    <label class="form-label">RFC del representante <span class="required">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="rfc_representante"
                           value="<?= s($contribuyente['rfc_representante'] ?? '') ?>"
                           data-requiredMoral="true" maxlength="13"
                           pattern="[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefono</label>
                    <input type="tel" class="form-control" name="telefono"
                           value="<?= s($contribuyente['telefono'] ?? '') ?>" maxlength="20">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Email de Contacto <?= !$esEdicion ? '<span class="required">*</span>' : '' ?></label>
                    <input type="email" class="form-control" name="<?= !$esEdicion ? 'email' : 'email_contacto' ?>"
                           value="<?= s($contribuyente['email_contacto'] ?? '') ?>"
                           <?= !$esEdicion ? 'required' : '' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Municipio</label>
                    <input type="text" class="form-control" name="municipio"
                           value="<?= s($contribuyente['municipio'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <input type="text" class="form-control" name="estado"
                           value="<?= s($contribuyente['estado'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Calle</label>
                    <input type="text" class="form-control" name="calle"
                           value="<?= s($contribuyente['calle'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label">C.P.</label>
                    <input type="text" class="form-control" name="codigo_postal"
                           value="<?= s($contribuyente['codigo_postal'] ?? '') ?>" maxlength="5" pattern="[0-9]{5}">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i><?= $esEdicion ? 'Guardar Cambios' : 'Crear Contribuyente' ?>
                </button>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=contribuyentes" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
