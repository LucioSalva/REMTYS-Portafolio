<?php /* Vista: Mis Pagos */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-credit-card me-2 text-primario"></i>Mis Pagos</h5>
</div>

<!-- Saldo resumen -->
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
            <div class="stat-icon azul"><i class="bi bi-receipt"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= count($pagos) ?></div>
                <div class="stat-label">Pagos Realizados</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon verde"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value">$<?= number_format((float)($saldo['total_pagado'] ?? 0), 2) ?></div>
                <div class="stat-label">Total Histórico Pagado</div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario para nuevo pago -->
<?php $obligacionesPendientes = array_filter($obligaciones, fn($o) => !$o['pagado']); ?>
<?php if (!empty($obligacionesPendientes)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Registrar Nuevo Pago</div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?modulo=contribuyente&accion=pagos" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label class="form-label fw-500">Seleccione obligaciones a pagar</label>
                <div class="border rounded p-3" style="max-height:240px;overflow-y:auto;">
                    <?php foreach ($obligacionesPendientes as $ob):
                        $saldoOb = (float)$ob['monto'] + (float)$ob['monto_recargos'] - (float)$ob['monto_pagado'];
                    ?>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input pago-check"
                               id="op<?= (int)$ob['id'] ?>"
                               name="obligacion_periodo_ids[]"
                               value="<?= (int)$ob['id'] ?>"
                               data-monto="<?= $saldoOb ?>">
                        <label class="form-check-label fs-sm" for="op<?= (int)$ob['id'] ?>">
                            <span class="fw-500"><?= s($ob['concepto'] ?? $ob['tipo_obligacion'] ?? 'Obligación') ?></span>
                            — <?= s($ob['anio_periodo'] ?? $ob['anio'] ?? '') ?>
                            <?php if (!empty($ob['clave_catastral'])): ?>
                            <span class="text-muted">(<?= s($ob['clave_catastral']) ?>)</span>
                            <?php endif; ?>
                            <span class="badge bg-danger ms-1">$<?= number_format($saldoOb, 2) ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Seleccione una o más obligaciones para incluir en este pago.</div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Método de Pago</label>
                    <select class="form-select" name="metodo_pago" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Total a Pagar</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control fw-500 text-success" id="totalPago" readonly value="0.00">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100"
                            data-confirm="¿Confirmar el pago seleccionado?">
                        <i class="bi bi-check-circle me-2"></i>Confirmar Pago
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-success mb-4">
    <i class="bi bi-check-circle me-2"></i>No tiene obligaciones pendientes de pago en este momento.
</div>
<?php endif; ?>

<!-- Historial de pagos -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>Historial de Pagos</span>
        <?php if (!empty($pagos)): ?>
        <span class="badge bg-primary"><?= count($pagos) ?> pago(s)</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pagos)): ?>
        <div class="empty-state p-4">
            <i class="bi bi-receipt"></i>
            <h5>Sin historial de pagos</h5>
            <p class="fs-sm">Aquí aparecerán sus pagos realizados.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Folio / Referencia</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th class="text-end">Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td>
                        <div class="fw-500 fs-sm"><?= s($p['numero_referencia'] ?? $p['folio'] ?? 'N/A') ?></div>
                        <?php if (!empty($p['observaciones'])): ?>
                        <div class="fs-xs text-muted"><?= s($p['observaciones']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="fs-sm"><?= s(substr($p['fecha_pago'] ?? $p['created_at'] ?? '', 0, 16)) ?></td>
                    <td>
                        <?php
                        $metodoBadge = match($p['metodo_pago'] ?? '') {
                            'efectivo'      => 'bg-success',
                            'transferencia' => 'bg-info text-dark',
                            'tarjeta'       => 'bg-primary',
                            'cheque'        => 'bg-warning text-dark',
                            default         => 'bg-secondary',
                        };
                        ?>
                        <span class="badge <?= $metodoBadge ?> fs-xs">
                            <?= s(ucfirst($p['metodo_pago'] ?? '—')) ?>
                        </span>
                    </td>
                    <td class="text-end fw-500 text-success">$<?= number_format((float)$p['monto_total'], 2) ?></td>
                    <td>
                        <?php
                        $estadoPago = $p['estado'] ?? 'completado';
                        $estadoBadge = match($estadoPago) {
                            'completado' => 'bg-success',
                            'pendiente'  => 'bg-warning text-dark',
                            'cancelado'  => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                        ?>
                        <span class="badge <?= $estadoBadge ?> fs-xs"><?= s(ucfirst($estadoPago)) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
    const checks = document.querySelectorAll('.pago-check');
    const totalEl = document.getElementById('totalPago');

    function recalcular() {
        let total = 0;
        checks.forEach(function (c) {
            if (c.checked) total += parseFloat(c.dataset.monto || 0);
        });
        if (totalEl) totalEl.value = total.toFixed(2);
    }

    checks.forEach(function (c) { c.addEventListener('change', recalcular); });
    recalcular();
});
</script>
