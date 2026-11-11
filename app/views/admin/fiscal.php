<?php /* Admin: Gestión Fiscal */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-bank2 me-2 text-primario"></i>Gestión Fiscal</h5>
</div>

<div class="row g-3 mb-4">
    <!-- Periodos fiscales -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-calendar-range me-2"></i>Periodos Fiscales</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($periodos)): ?>
                <div class="empty-state p-3"><i class="bi bi-calendar-x"></i><p class="fs-sm">Sin periodos.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Año</th><th>Descripción</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($periodos as $p): ?>
                        <tr>
                            <td class="fw-500"><?= (int)$p['anio'] ?></td>
                            <td class="fs-xs"><?= s($p['descripcion']) ?></td>
                            <td class="fs-xs"><?= s($p['fecha_inicio']) ?></td>
                            <td class="fs-xs"><?= s($p['fecha_fin']) ?></td>
                            <td><span class="badge <?= $p['activo'] ? 'bg-success' : 'bg-secondary' ?> fs-xs"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <!-- Formulario nuevo periodo -->
                <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_periodo" class="row g-2">
                    <?= campoCSRF() ?>
                    <div class="col-3"><input type="number" class="form-control form-control-sm" name="anio" placeholder="Año" min="2000" max="2099" required></div>
                    <div class="col-5"><input type="text" class="form-control form-control-sm" name="descripcion" placeholder="Descripción" required></div>
                    <div class="col-2"><input type="date" class="form-control form-control-sm" name="fecha_inicio" required></div>
                    <div class="col-2"><input type="date" class="form-control form-control-sm" name="fecha_fin" required></div>
                    <div class="col-12"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus me-1"></i>Agregar Periodo</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Saldos por contribuyente -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-people me-2"></i>Saldos Pendientes</span>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=obligaciones" class="btn btn-sm btn-outline-primary">Ver obligaciones</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($saldos)): ?>
                <div class="empty-state p-3"><i class="bi bi-cash-stack"></i><p class="fs-sm">Sin saldos.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Contribuyente</th><th>RFC</th><th>Total Cargo</th><th>Pagado</th><th>Saldo</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($saldos as $s): ?>
                        <tr>
                            <td class="fs-sm"><?= s($s['nombre_completo']) ?></td>
                            <td><code class="fs-xs"><?= s($s['rfc']) ?></code></td>
                            <td class="fs-sm">$<?= number_format((float)$s['total_cargo'], 2) ?></td>
                            <td class="fs-sm text-success">$<?= number_format((float)$s['total_pagado'], 2) ?></td>
                            <td class="fw-500 <?= $s['saldo_pendiente'] > 0 ? 'text-danger' : 'text-success' ?>">
                                $<?= number_format((float)$s['saldo_pendiente'], 2) ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/?modulo=admin&accion=obligaciones&contribuyente_id=<?= (int)$s['contribuyente_id'] ?>"
                                   class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-eye"></i></a>
                            </td>
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
