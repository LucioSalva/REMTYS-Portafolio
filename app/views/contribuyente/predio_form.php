<?php /* Formulario de Predio */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=predios" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0"><?= s($titulo) ?></h5>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= s($accion_form) ?>" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="form-section-title">Datos Catastrales</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Clave Catastral <span class="required">*</span></label>
                    <input type="text" class="form-control text-uppercase" name="clave_catastral"
                           value="<?= s($predio['clave_catastral'] ?? '') ?>" required maxlength="50"
                           placeholder="MUN-001-001-001" <?= $predio ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cuenta Predial <span class="required">*</span></label>
                    <input type="text" class="form-control" name="cuenta_predial"
                           value="<?= s($predio['cuenta_predial'] ?? '') ?>" required maxlength="50"
                           placeholder="CP-2024-000000" <?= $predio ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Nombre / Descripción del Predio</label>
                    <input type="text" class="form-control" name="nombre_descripcion"
                           value="<?= s($predio['nombre_descripcion'] ?? '') ?>" maxlength="500">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Uso de Suelo</label>
                    <select class="form-select" name="uso_suelo">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach (['Habitacional','Habitacional Unifamiliar','Habitacional Multifamiliar','Comercial','Industrial','Mixto','Agrícola','Pecuario','Forestal','Turístico','Equipamiento','Vialidad','Sin Uso'] as $uso): ?>
                        <option value="<?= $uso ?>" <?= ($predio['uso_suelo'] ?? '') === $uso ? 'selected' : '' ?>><?= $uso ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section-title">Medidas y Valor</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Superficie Total (m²)</label>
                    <input type="number" class="form-control" name="superficie_total" step="0.01" min="0"
                           value="<?= s($predio['superficie_total'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sup. Construida (m²)</label>
                    <input type="number" class="form-control" name="superficie_construida" step="0.01" min="0"
                           value="<?= s($predio['superficie_construida'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valor Catastral ($)</label>
                    <input type="number" class="form-control" name="valor_catastral" step="0.01" min="0"
                           value="<?= s($predio['valor_catastral'] ?? '') ?>">
                </div>
            </div>

            <div class="form-section-title">Ubicación</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Calle</label>
                    <input type="text" class="form-control" name="calle" value="<?= s($predio['calle'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Núm. Exterior</label>
                    <input type="text" class="form-control" name="numero_exterior" value="<?= s($predio['numero_exterior'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Núm. Interior</label>
                    <input type="text" class="form-control" name="numero_interior" value="<?= s($predio['numero_interior'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Código Postal <span class="required">*</span></label>
                    <select class="form-select" name="codigo_postal" id="selectCP" required>
                        <option value="">-- Seleccionar C.P. --</option>
                        <?php foreach ($codigos_postales ?? [] as $cp): ?>
                        <option value="<?= s($cp) ?>" <?= ($predio['codigo_postal'] ?? '') === $cp ? 'selected' : '' ?>><?= s($cp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Colonia <span class="required">*</span></label>
                    <select class="form-select" name="colonia" id="selectColonia" required>
                        <option value="">-- Primero seleccione C.P. --</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Municipio</label>
                    <input type="text" class="form-control" value="<?= s(APP_MUNICIPIO) ?>" readonly disabled>
                    <input type="hidden" name="municipio" value="<?= s(APP_MUNICIPIO) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <input type="text" class="form-control" value="<?= s(APP_ESTADO) ?>" readonly disabled>
                    <input type="hidden" name="estado" value="<?= s(APP_ESTADO) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i><?= $predio ? 'Actualizar Predio' : 'Registrar Predio' ?>
                </button>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=predios" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const selectCP = document.getElementById('selectCP');
    const selectColonia = document.getElementById('selectColonia');
    const coloniaActual = <?= json_encode($predio['colonia'] ?? '') ?>;
    const baseUrl = <?= json_encode(BASE_URL) ?>;

    function cargarColonias(cp, coloniaSeleccionada) {
        selectColonia.innerHTML = '<option value="">Cargando...</option>';
        selectColonia.disabled = true;

        if (!cp) {
            selectColonia.innerHTML = '<option value="">-- Primero seleccione C.P. --</option>';
            selectColonia.disabled = false;
            return;
        }

        fetch(baseUrl + '/?modulo=contribuyente&accion=colonias_por_cp&cp=' + encodeURIComponent(cp))
            .then(r => r.json())
            .then(colonias => {
                if (colonias.length === 0) {
                    selectColonia.innerHTML = '<option value="">No se encontraron colonias</option>';
                } else if (colonias.length === 1) {
                    selectColonia.innerHTML = '<option value="' + colonias[0] + '" selected>' + colonias[0] + '</option>';
                } else {
                    let html = '<option value="">-- Seleccionar colonia --</option>';
                    colonias.forEach(function(col) {
                        const sel = col === coloniaSeleccionada ? ' selected' : '';
                        html += '<option value="' + col + '"' + sel + '>' + col + '</option>';
                    });
                    selectColonia.innerHTML = html;
                }
                selectColonia.disabled = false;
            })
            .catch(() => {
                selectColonia.innerHTML = '<option value="">Error al cargar colonias</option>';
                selectColonia.disabled = false;
            });
    }

    selectCP.addEventListener('change', function() {
        cargarColonias(this.value, '');
    });

    // If editing, load colonies for current CP
    if (selectCP.value) {
        cargarColonias(selectCP.value, coloniaActual);
    }
});
</script>
