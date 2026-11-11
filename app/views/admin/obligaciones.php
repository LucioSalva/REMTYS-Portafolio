<?php /* Admin: Obligaciones Fiscales */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-receipt me-2 text-primario"></i>Obligaciones Fiscales</h5>
</div>

<!-- Filtro por contribuyente -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="obligaciones">
            <label class="form-label mb-0 me-2 fw-500 fs-sm text-nowrap">Contribuyente:</label>
            <select class="form-select form-select-sm" name="contribuyente_id" style="max-width:320px;">
                <option value="">-- Todos --</option>
                <?php foreach ($contribs as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)($contribSeleccionado ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= s($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<?php if ($contribSeleccionado): ?>
<!-- Formulario nueva obligación -->
<div class="card mb-3">
    <div class="card-header fs-sm"><i class="bi bi-plus-circle me-2"></i>Nueva Obligación para <?= s($contribNombre ?? '') ?></div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nueva_obligacion" class="row g-2 align-items-end">
            <?= campoCSRF() ?>
            <input type="hidden" name="contribuyente_id" value="<?= (int)$contribSeleccionado ?>">
            <div class="col-md-3">
                <label class="form-label fs-xs">Tipo Obligación <span class="required">*</span></label>
                <input type="text" class="form-control form-control-sm" name="tipo" placeholder="Predial, Servicios..." required maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label fs-xs">Descripción</label>
                <input type="text" class="form-control form-control-sm" name="descripcion" maxlength="300">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-xs">Monto Base ($)</label>
                <input type="number" class="form-control form-control-sm" name="monto_base" step="0.01" min="0" value="0.00">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus me-1"></i>Agregar Obligación</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><?= count($obligaciones) ?> obligación(es)</div>
    <div class="card-body p-0">
        <?php if (empty($obligaciones)): ?>
        <div class="empty-state p-4"><i class="bi bi-receipt"></i><h5>Sin obligaciones <?= $contribSeleccionado ? 'para este contribuyente' : '- seleccione un contribuyente' ?></h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Tipo</th><th>Descripción</th><th>Predio</th><th>Año</th><th>Monto</th><th>Recargos</th><th>Total</th><th>Estado</th><th>Vence</th></tr></thead>
                <tbody>
                <?php foreach ($obligaciones as $ob): ?>
                <tr>
                    <td class="fw-500 fs-sm"><?= s($ob['tipo']) ?></td>
                    <td class="fs-xs text-muted"><?= s($ob['descripcion'] ?? '—') ?></td>
                    <td class="fs-xs"><?= s($ob['clave_catastral'] ?? '—') ?></td>
                    <td class="fs-sm"><?= (int)$ob['anio'] ?></td>
                    <td class="fs-sm">$<?= number_format((float)$ob['monto'], 2) ?></td>
                    <td class="fs-sm text-warning">$<?= number_format((float)$ob['monto_recargos'], 2) ?></td>
                    <td class="fw-500">$<?= number_format((float)$ob['monto'] + (float)$ob['monto_recargos'], 2) ?></td>
                    <td>
                        <span class="badge <?= $ob['pagado'] ? 'bg-success' : 'bg-danger' ?> fs-xs">
                            <?= $ob['pagado'] ? 'Pagado' : 'Pendiente' ?>
                        </span>
                    </td>
                    <td class="fs-xs text-muted"><?= s($ob['fecha_limite'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
