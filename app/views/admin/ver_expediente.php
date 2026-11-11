<?php /* Admin: Ver Expediente */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Expediente <?= s($expediente['numero_expediente']) ?></h5>
    <div class="ms-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tramite&expediente_id=<?= (int)$expediente['id'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus me-1"></i>Nuevo Trámite
        </a>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_expediente&id=<?= (int)$expediente['id'] ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=historial&expediente_id=<?= (int)$expediente['id'] ?>" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-clock-history me-1"></i>Historial
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-primario text-white"><i class="bi bi-folder-fill me-2"></i>Datos del Expediente</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Número:</dt><dd class="col-7 fw-500"><?= s($expediente['numero_expediente']) ?></dd>
                    <dt class="col-5 text-muted">Estado:</dt>
                    <dd class="col-7">
                        <?php
                        $cl = match($expediente['estado']) {
                            'abierto'=>'badge-estado-abierto','en_proceso'=>'badge-estado-en_proceso',
                            'resuelto'=>'badge-estado-resuelto','cerrado'=>'badge-estado-cerrado',
                            'cancelado'=>'badge-estado-cancelado',default=>'bg-secondary text-white'
                        };
                        ?>
                        <span class="badge <?= $cl ?>"><?= s(str_replace('_',' ',$expediente['estado'])) ?></span>
                    </dd>
                    <dt class="col-5 text-muted">Contribuyente:</dt><dd class="col-7"><?= s($expediente['contribuyente_nombre']) ?></dd>
                    <dt class="col-5 text-muted">RFC:</dt><dd class="col-7"><code class="fs-xs"><?= s($expediente['rfc']) ?></code></dd>
                    <?php if ($expediente['clave_catastral']): ?>
                    <dt class="col-5 text-muted">Predio:</dt><dd class="col-7"><?= s($expediente['clave_catastral']) ?></dd>
                    <dt class="col-5 text-muted">Dirección:</dt><dd class="col-7 fs-xs"><?= s($expediente['calle'] . ' ' . $expediente['numero_exterior']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-5 text-muted">Apertura:</dt><dd class="col-7"><?= s($expediente['fecha_apertura']) ?></dd>
                    <?php if ($expediente['fecha_cierre']): ?>
                    <dt class="col-5 text-muted">Cierre:</dt><dd class="col-7"><?= s($expediente['fecha_cierre']) ?></dd>
                    <?php endif; ?>
                </dl>
                <?php if ($expediente['observaciones']): ?>
                <hr class="my-2">
                <div class="text-muted fs-xs">Observaciones: <?= s($expediente['observaciones']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones rápidas de estado -->
        <div class="card">
            <div class="card-header fs-sm"><i class="bi bi-gear me-2"></i>Cambiar Estado</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=editar_expediente&id=<?= (int)$expediente['id'] ?>">
                    <?= campoCSRF() ?>
                    <div class="mb-2">
                        <select class="form-select form-select-sm" name="estado">
                            <?php foreach (['abierto','en_proceso','resuelto','cerrado','cancelado'] as $est): ?>
                            <option value="<?= $est ?>" <?= $expediente['estado'] === $est ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2"
                                  placeholder="Observación..."><?= s($expediente['observaciones'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-save me-1"></i>Actualizar Estado
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Trámites -->
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-file-earmark-fill me-2"></i>Trámites (<?= count($tramites) ?>)</span>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tramite&expediente_id=<?= (int)$expediente['id'] ?>"
                   class="btn btn-sm btn-outline-primary"><i class="bi bi-plus me-1"></i>Agregar</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tramites)): ?>
                <div class="empty-state p-3"><i class="bi bi-file-x"></i><p class="fs-sm">Sin trámites.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Trámite</th><th>Tipo</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($tramites as $t): ?>
                        <tr>
                            <td class="fw-500 fs-sm"><?= s($t['numero_tramite']) ?></td>
                            <td class="fs-xs"><?= s($t['tipo_nombre']) ?></td>
                            <td>
                                <?php
                                $tc = match($t['estado']) {
                                    'aprobado'=>'badge-estado-aprobado','rechazado'=>'badge-estado-rechazado',
                                    'cancelado'=>'badge-estado-cancelado','en_revision'=>'badge-estado-en_revision',
                                    'documentacion_pendiente'=>'badge-estado-documentacion_pendiente',
                                    default=>'badge-estado-iniciado'
                                };
                                ?>
                                <span class="badge <?= $tc ?> fs-xs"><?= s(str_replace('_',' ',$t['estado'])) ?></span>
                            </td>
                            <td class="fs-xs text-muted"><?= s($t['fecha_inicio']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_tramite&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-eye"></i></a>
                                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_tramite&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary py-0"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial reciente -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2"></i>Historial Reciente</span>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=historial&expediente_id=<?= (int)$expediente['id'] ?>" class="btn btn-sm btn-outline-dark">Ver completo</a>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                <p class="text-muted fs-sm mb-0">Sin eventos.</p>
                <?php else: ?>
                <ul class="timeline">
                <?php foreach (array_slice($historial, 0, 6) as $h): ?>
                <li class="timeline-item">
                    <div class="timeline-icon"><i class="bi bi-check"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?= s(substr($h['fecha'] ?? '', 0, 16)) ?><?php if (!empty($h['nombre_completo'])): ?> — <?= s($h['nombre_completo']) ?><?php endif; ?></div>
                        <div class="timeline-event"><?= s($h['evento']) ?></div>
                        <?php if ($h['descripcion']): ?><div class="timeline-desc"><?= s($h['descripcion']) ?></div><?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
