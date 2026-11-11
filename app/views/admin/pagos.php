<?php /* Admin: Pagos */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-cash-stack me-2 text-primario"></i>Pagos Registrados</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoPago">
        <i class="bi bi-plus-circle me-2"></i>Registrar Pago
    </button>
</div>

<div class="card">
    <div class="card-header"><?= count($pagos) ?> pago(s)</div>
    <div class="card-body p-0">
        <?php if (empty($pagos)): ?>
        <div class="empty-state p-4"><i class="bi bi-cash"></i><h5>Sin pagos registrados</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Referencia</th><th>Contribuyente</th><th>RFC</th><th>Monto</th><th>Método</th><th>Fecha</th><th>Items</th></tr></thead>
                <tbody>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><code class="fs-xs"><?= s($p['referencia'] ?? '—') ?></code></td>
                    <td class="fs-sm"><?= s($p['contrib_nombre']) ?></td>
                    <td><code class="fs-xs"><?= s($p['rfc']) ?></code></td>
                    <td class="fw-500 text-success">$<?= number_format((float)$p['monto'], 2) ?></td>
                    <td><span class="badge bg-secondary fs-xs"><?= s($p['metodo_pago']) ?></span></td>
                    <td class="fs-xs text-muted"><?= s(substr($p['fecha'] ?? '', 0, 10)) ?></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$p['items_aplicados'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Nuevo Pago Manual -->
<div class="modal fade" id="modalNuevoPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Registrar Pago Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_pago">
                <?= campoCSRF() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contribuyente <span class="required">*</span></label>
                        <select class="form-select" name="contribuyente_id" required>
                            <option value="">-- Seleccione --</option>
                            <?php
                            $db = getDBConnection();
                            $contribs = $db->query('SELECT id, nombre_mostrado AS nombre FROM v_contribuyentes_detalle ORDER BY nombre_mostrado ASC')->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($contribs as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= s($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto ($) <span class="required">*</span></label>
                        <input type="number" class="form-control" name="monto" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select class="form-select" name="metodo_pago">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Referencia</label>
                        <input type="text" class="form-control" name="referencia" maxlength="100" placeholder="Número de folio/cheque/transferencia">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
