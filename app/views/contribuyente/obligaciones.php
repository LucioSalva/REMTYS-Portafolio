<?php /* Vista: Mis Obligaciones Fiscales y Saldo */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-cash-stack me-2 text-primario"></i>Mis Obligaciones Fiscales</h5>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=pagos" class="btn btn-primary">
        <i class="bi bi-credit-card me-2"></i>Realizar Pago
    </a>
</div>

<!-- Tarjetas de saldo -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon <?= ($saldo['saldo_pendiente'] ?? 0) > 0 ? 'rojo' : 'verde' ?>">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">$<?= number_format((float)($saldo['saldo_pendiente'] ?? 0), 2) ?></div>
                <div class="stat-label">Saldo Pendiente</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon azul"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value">$<?= number_format((float)($saldo['total_pagado'] ?? 0), 2) ?></div>
                <div class="stat-label">Total Pagado</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon naranja"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= (int)($saldo['obligaciones_pendientes'] ?? 0) ?></div>
                <div class="stat-label">Obligaciones Pendientes</div>
            </div>
        </div>
    </div>
</div>

<?php if (($saldo['saldo_pendiente'] ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        Tiene un saldo pendiente de <strong>$<?= number_format((float)$saldo['saldo_pendiente'], 2) ?></strong>.
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=pagos" class="alert-link ms-1">Realizar pago</a>
    </div>
</div>
<?php endif; ?>

<!-- Tabla de obligaciones -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-list-ul me-2"></i>Detalle de Obligaciones</span>
        <?php if (!empty($obligaciones)): ?>
        <span class="badge bg-primary"><?= count($obligaciones) ?> obligación(es)</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($obligaciones)): ?>
        <div class="empty-state p-4">
            <i class="bi bi-check-circle"></i>
            <h5>Sin obligaciones registradas</h5>
            <p class="fs-sm">No tiene obligaciones fiscales pendientes en este momento.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Período</th>
                        <th>Predio / Cuenta</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Recargos</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($obligaciones as $ob): ?>
                <?php
                    $saldoOb = (float)$ob['monto'] + (float)$ob['monto_recargos'] - (float)$ob['monto_pagado'];
                ?>
                <tr>
                    <td>
                        <div class="fw-500"><?= s($ob['concepto'] ?? $ob['tipo_obligacion'] ?? '—') ?></div>
                        <?php if (!empty($ob['descripcion'])): ?>
                        <div class="fs-xs text-muted"><?= s($ob['descripcion']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="fs-sm"><?= s($ob['anio_periodo'] ?? $ob['anio'] ?? '—') ?></td>
                    <td class="fs-xs text-muted"><?= s($ob['clave_catastral'] ?? $ob['cuenta_predial'] ?? '—') ?></td>
                    <td class="text-end">$<?= number_format((float)$ob['monto'], 2) ?></td>
                    <td class="text-end <?= (float)$ob['monto_recargos'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= (float)$ob['monto_recargos'] > 0 ? '$' . number_format((float)$ob['monto_recargos'], 2) : '—' ?>
                    </td>
                    <td class="text-end text-success">
                        <?= (float)$ob['monto_pagado'] > 0 ? '$' . number_format((float)$ob['monto_pagado'], 2) : '—' ?>
                    </td>
                    <td class="text-end fw-500 <?= $saldoOb > 0 ? 'text-danger' : 'text-success' ?>">
                        $<?= number_format($saldoOb, 2) ?>
                    </td>
                    <td>
                        <?php if ($ob['pagado']): ?>
                        <span class="badge bg-success fs-xs"><i class="bi bi-check me-1"></i>Pagado</span>
                        <?php elseif ($saldoOb > 0): ?>
                        <span class="badge bg-danger fs-xs">Pendiente</span>
                        <?php else: ?>
                        <span class="badge bg-secondary fs-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end fw-500">Saldo total pendiente:</td>
                        <td class="text-end fw-500 <?= ($saldo['saldo_pendiente'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            $<?= number_format((float)($saldo['saldo_pendiente'] ?? 0), 2) ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
