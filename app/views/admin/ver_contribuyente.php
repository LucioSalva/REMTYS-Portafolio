<?php /* Admin: Ver Contribuyente */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=contribuyentes" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">Contribuyente: <?= s($contribuyente['nombre_completo']) ?></h5>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_contribuyente&id=<?= (int)$contribuyente['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_expediente?contribuyente_id=<?= (int)$contribuyente['id'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-folder-plus me-1"></i>Nuevo Expediente
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-primario text-white"><i class="bi bi-person-fill me-2"></i>Datos Generales</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Tipo:</dt>
                    <dd class="col-7">
                        <span class="badge <?= $contribuyente['tipo_persona'] === 'moral' ? 'bg-info' : 'bg-success' ?>">
                            <?= $contribuyente['tipo_persona'] === 'moral' ? 'Persona Moral' : 'Persona Fisica' ?>
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">RFC:</dt>
                    <dd class="col-7"><code><?= s($contribuyente['rfc']) ?></code></dd>
                    <?php if ($contribuyente['tipo_persona'] === 'fisica' && !empty($contribuyente['curp'])): ?>
                    <dt class="col-5 text-muted">CURP:</dt>
                    <dd class="col-7"><code class="fs-xs"><?= s($contribuyente['curp']) ?></code></dd>
                    <?php endif; ?>
                    <?php if ($contribuyente['tipo_persona'] === 'moral'): ?>
                    <dt class="col-5 text-muted">Rep. legal:</dt>
                    <dd class="col-7"><?= s($contribuyente['representante_legal'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">RFC Rep.:</dt>
                    <dd class="col-7"><code class="fs-xs"><?= s($contribuyente['rfc_representante'] ?? '—') ?></code></dd>
                    <?php endif; ?>
                    <dt class="col-5 text-muted">Email:</dt>
                    <dd class="col-7"><?= s($contribuyente['email']) ?></dd>
                    <?php if ($contribuyente['email_contacto'] && $contribuyente['email_contacto'] !== $contribuyente['email']): ?>
                    <dt class="col-5 text-muted">Email C.:</dt>
                    <dd class="col-7"><?= s($contribuyente['email_contacto']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-5 text-muted">Telefono:</dt>
                    <dd class="col-7"><?= s($contribuyente['telefono'] ?? '—') ?></dd>
                    <dt class="col-5 text-muted">Rol:</dt>
                    <dd class="col-7"><span class="badge bg-secondary"><?= s($contribuyente['rol']) ?></span></dd>
                    <dt class="col-5 text-muted">Estado:</dt>
                    <dd class="col-7">
                        <span class="badge <?= $contribuyente['usuario_activo'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $contribuyente['usuario_activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Ult. acceso:</dt>
                    <dd class="col-7 fs-xs"><?= s($contribuyente['ultimo_acceso'] ? substr($contribuyente['ultimo_acceso'], 0, 16) : '—') ?></dd>
                    <dt class="col-5 text-muted">Registro:</dt>
                    <dd class="col-7 fs-xs"><?= s(substr($contribuyente['created_at'] ?? '', 0, 10)) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fs-sm"><i class="bi bi-house me-2"></i>Domicilio Fiscal</div>
            <div class="card-body fs-sm">
                <?php
                $dom = trim(implode(' ', array_filter([
                    $contribuyente['calle'] ?? '',
                    $contribuyente['numero_exterior'] ? '#' . $contribuyente['numero_exterior'] : '',
                    $contribuyente['colonia'] ?? '',
                ])));
                $loc = trim(implode(', ', array_filter([
                    $contribuyente['municipio'] ?? '',
                    $contribuyente['estado'] ?? '',
                    $contribuyente['codigo_postal'] ? 'C.P. ' . $contribuyente['codigo_postal'] : '',
                ])));
                ?>
                <?php if ($dom || $loc): ?>
                <address class="mb-0">
                    <?= s($dom) ?><br>
                    <?= s($loc) ?>
                </address>
                <?php else: ?>
                <span class="text-muted">Sin domicilio registrado</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header fs-sm"><i class="bi bi-bank2 me-2"></i>Resumen Fiscal</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1 fs-sm">
                    <span class="text-muted">Total cargado:</span>
                    <strong>$<?= number_format((float)($saldo['total_cargo'] ?? 0), 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1 fs-sm">
                    <span class="text-muted">Pagado:</span>
                    <strong class="text-success">$<?= number_format((float)($saldo['total_pagado'] ?? 0), 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2 fs-sm">
                    <span class="text-muted">Saldo pendiente:</span>
                    <strong class="<?= ($saldo['saldo_pendiente'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                        $<?= number_format((float)($saldo['saldo_pendiente'] ?? 0), 2) ?>
                    </strong>
                </div>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=obligaciones&contribuyente_id=<?= (int)$contribuyente['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-receipt me-1"></i>Ver Obligaciones
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-geo-alt-fill me-2"></i>Predios (<?= count($predios) ?>)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($predios)): ?>
                <div class="empty-state p-3"><i class="bi bi-geo-alt"></i><p class="fs-sm">Sin predios.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Clave Catastral</th><th>Ubicacion</th><th>Relacion</th><th>Desde</th></tr></thead>
                        <tbody>
                        <?php foreach ($predios as $p): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_predio&id=<?= (int)$p['id'] ?>" class="text-decoration-none fw-500"><?= s($p['clave_catastral']) ?></a></td>
                            <td class="fs-xs"><?= s($p['calle'] . ' ' . $p['numero_exterior'] . ', ' . $p['colonia']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary fs-xs"><?= s($p['tipo_relacion']) ?></span></td>
                            <td class="fs-xs text-muted"><?= s($p['fecha_inicio']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-folder-fill me-2"></i>Expedientes (<?= count($expedientes) ?>)</span>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_expediente" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-folder-plus me-1"></i>Nuevo
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($expedientes)): ?>
                <div class="empty-state p-3"><i class="bi bi-folder-x"></i><p class="fs-sm">Sin expedientes.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Numero</th><th>Predio</th><th>Estado</th><th>Tramites</th><th>Apertura</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($expedientes as $exp): ?>
                        <tr>
                            <td class="fw-500 fs-sm"><?= s($exp['numero_expediente']) ?></td>
                            <td class="fs-xs text-muted"><?= s($exp['clave_catastral'] ?? '—') ?></td>
                            <td>
                                <?php
                                $ecl = match($exp['estado']) {
                                    'abierto'=>'badge-estado-abierto','en_proceso'=>'badge-estado-en_proceso',
                                    'resuelto'=>'badge-estado-resuelto','cerrado'=>'badge-estado-cerrado',
                                    'cancelado'=>'badge-estado-cancelado',default=>'bg-secondary text-white'
                                };
                                ?>
                                <span class="badge <?= $ecl ?> fs-xs"><?= s(str_replace('_',' ',$exp['estado'])) ?></span>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$exp['total_tramites'] ?></span></td>
                            <td class="fs-xs text-muted"><?= s($exp['fecha_apertura']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$exp['id'] ?>" class="btn btn-xs btn-sm btn-outline-primary py-0">
                                    <i class="bi bi-eye"></i>
                                </a>
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
