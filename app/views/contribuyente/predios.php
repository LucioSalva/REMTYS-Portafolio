<?php /* Vista: Mis Predios */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-geo-alt-fill me-2 text-primario"></i>Mis Predios</h5>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_predio" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Registrar Predio
    </a>
</div>

<?php if (empty($predios)): ?>
<div class="empty-state card p-4">
    <i class="bi bi-geo-alt-fill"></i>
    <h5>No tiene predios registrados</h5>
    <p class="fs-sm">Registre sus predios para poder iniciar trámites.</p>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_predio" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Registrar Predio
    </a>
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($predios as $predio): ?>
<div class="col-md-6 col-lg-4">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span class="fw-600 fs-sm"><i class="bi bi-geo-alt me-1"></i><?= s($predio['clave_catastral']) ?></span>
            <span class="badge bg-success-subtle text-success fs-xs"><?= s($predio['tipo_relacion'] ?? 'propietario') ?></span>
        </div>
        <div class="card-body">
            <h6 class="mb-2"><?= s($predio['nombre_descripcion'] ?? 'Sin nombre') ?></h6>
            <div class="fs-sm text-muted mb-1">
                <i class="bi bi-house me-1"></i><?= s($predio['calle'] . ' ' . $predio['numero_exterior'] . ', ' . $predio['colonia']) ?>
            </div>
            <div class="fs-sm text-muted mb-1">
                <i class="bi bi-geo me-1"></i><?= s($predio['municipio'] . ', ' . $predio['estado']) ?>
            </div>
            <hr class="my-2">
            <div class="row g-1 fs-xs text-muted">
                <div class="col-6"><i class="bi bi-arrows-angle-expand me-1"></i>Sup: <?= $predio['superficie_total'] ? number_format($predio['superficie_total'],0) . ' m²' : 'N/A' ?></div>
                <div class="col-6"><i class="bi bi-tag me-1"></i>Uso: <?= s($predio['uso_suelo'] ?? 'N/A') ?></div>
                <?php if ($predio['valor_catastral']): ?>
                <div class="col-12"><i class="bi bi-currency-dollar me-1"></i>Valor Catastral: $<?= number_format($predio['valor_catastral'],0) ?></div>
                <?php endif; ?>
                <div class="col-12"><i class="bi bi-receipt me-1"></i>Cuenta: <?= s($predio['cuenta_predial'] ?? 'N/A') ?></div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top d-flex gap-2">
            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=editar_predio&id=<?= (int)$predio['id'] ?>"
               class="btn btn-sm btn-outline-primary flex-fill">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite?predio_id=<?= (int)$predio['id'] ?>"
               class="btn btn-sm btn-primary flex-fill">
                <i class="bi bi-file-plus me-1"></i>Trámite
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
