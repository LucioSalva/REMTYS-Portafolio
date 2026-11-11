<?php /* Admin: Ver Predio */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=predios" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Predio: <?= s($predio['clave_catastral']) ?></h5>
    <div class="ms-auto">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_predio&id=<?= (int)$predio['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header bg-primario text-white"><i class="bi bi-geo-alt-fill me-2"></i>Datos Catastrales</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Clave Catastral:</dt><dd class="col-7"><code><?= s($predio['clave_catastral']) ?></code></dd>
                    <dt class="col-5 text-muted">Cuenta Predial:</dt><dd class="col-7"><code class="fs-xs"><?= s($predio['cuenta_predial']) ?></code></dd>
                    <dt class="col-5 text-muted">Nombre:</dt><dd class="col-7"><?= s($predio['nombre_descripcion'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Uso de Suelo:</dt><dd class="col-7"><?= s($predio['uso_suelo'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Sup. Total:</dt><dd class="col-7"><?= $predio['superficie_total'] ? number_format($predio['superficie_total'], 2) . ' m²' : '—' ?></dd>
                    <dt class="col-5 text-muted">Sup. Construida:</dt><dd class="col-7"><?= $predio['superficie_construida'] ? number_format($predio['superficie_construida'], 2) . ' m²' : '—' ?></dd>
                    <dt class="col-5 text-muted">Valor Catastral:</dt><dd class="col-7"><?= $predio['valor_catastral'] ? '$' . number_format($predio['valor_catastral'], 2) : '—' ?></dd>
                    <dt class="col-5 text-muted">Estado:</dt>
                    <dd class="col-7"><span class="badge <?= $predio['activo'] ? 'bg-success' : 'bg-danger' ?>"><?= $predio['activo'] ? 'Activo' : 'Inactivo' ?></span></dd>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header fs-sm"><i class="bi bi-pin-map me-2"></i>Ubicación</div>
            <div class="card-body fs-sm">
                <address class="mb-0">
                    <?= s($predio['calle'] . ' #' . $predio['numero_exterior']) ?><br>
                    <?= s($predio['colonia'] . ', C.P. ' . $predio['codigo_postal']) ?><br>
                    <?= s($predio['municipio'] . ', ' . $predio['estado']) ?>
                </address>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Historial de Propietarios/Poseedores</div>
            <div class="card-body p-0">
                <?php if (empty($historial)): ?>
                <div class="empty-state p-3"><i class="bi bi-person-x"></i><p class="fs-sm">Sin relaciones registradas.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Contribuyente</th><th>RFC</th><th>Relación</th><th>Desde</th><th>Hasta</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($historial as $h): ?>
                        <tr>
                            <td class="fs-sm"><?= s($h['contrib_nombre']) ?></td>
                            <td><code class="fs-xs"><?= s($h['rfc']) ?></code></td>
                            <td class="fs-xs"><?= s($h['tipo_relacion']) ?></td>
                            <td class="fs-xs"><?= s($h['fecha_inicio']) ?></td>
                            <td class="fs-xs"><?= $h['fecha_fin'] ? s($h['fecha_fin']) : '<span class="text-success">Actual</span>' ?></td>
                            <td><span class="badge <?= $h['activo'] ? 'bg-success' : 'bg-secondary' ?> fs-xs"><?= $h['activo'] ? 'Activo' : 'Anterior' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
