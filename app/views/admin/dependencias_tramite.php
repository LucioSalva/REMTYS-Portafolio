<?php /* Admin: Dependencias entre Trámites */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_tramite&id=<?= (int)$tramite['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Dependencias del Trámite <?= s($tramite['numero_tramite']) ?></h5>
</div>

<div class="alert alert-info fs-sm mb-4">
    <i class="bi bi-info-circle-fill me-2"></i>
    Las dependencias indican que este trámite <strong>requiere</strong> que otro trámite esté <strong>aprobado</strong> antes de procesarse.
    El sistema previene ciclos automáticamente.
</div>

<div class="row g-3">
    <div class="col-md-5">
        <!-- Agregar nueva dependencia -->
        <div class="card">
            <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>Agregar Dependencia</div>
            <div class="card-body">
                <?php if (empty($tramitesCandidatos)): ?>
                <p class="text-muted fs-sm">No hay trámites aprobados disponibles como dependencia.</p>
                <?php else: ?>
                <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=nueva_dependencia">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="tramite_id" value="<?= (int)$tramite['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Este trámite depende de: <span class="required">*</span></label>
                        <select class="form-select" name="depende_de_tramite_id" required>
                            <option value="">-- Seleccione trámite previo --</option>
                            <?php foreach ($tramitesCandidatos as $tc): ?>
                            <option value="<?= (int)$tc['id'] ?>">
                                <?= s($tc['numero_tramite']) ?> — <?= s($tc['tipo_nombre']) ?>
                                (Exp: <?= s($tc['numero_expediente']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-link-45deg me-2"></i>Agregar Dependencia
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info del trámite actual -->
        <div class="card mt-3">
            <div class="card-header fs-sm"><i class="bi bi-file-earmark-fill me-2"></i>Trámite Actual</div>
            <div class="card-body fs-sm">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Número:</dt><dd class="col-7 fw-500"><?= s($tramite['numero_tramite']) ?></dd>
                    <dt class="col-5 text-muted">Tipo:</dt><dd class="col-7"><?= s($tramite['tipo_nombre']) ?></dd>
                    <dt class="col-5 text-muted">Expediente:</dt><dd class="col-7"><?= s($tramite['numero_expediente']) ?></dd>
                    <dt class="col-5 text-muted">Estado:</dt>
                    <dd class="col-7">
                        <?php
                        $tc = match($tramite['estado']) {
                            'aprobado'=>'badge-estado-aprobado','rechazado'=>'badge-estado-rechazado',
                            'cancelado'=>'badge-estado-cancelado',default=>'badge-estado-iniciado'
                        };
                        ?>
                        <span class="badge <?= $tc ?>"><?= s(str_replace('_',' ',$tramite['estado'])) ?></span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-diagram-3-fill me-2"></i>Dependencias Actuales (<?= count($dependencias) ?>)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($dependencias)): ?>
                <div class="empty-state p-4">
                    <i class="bi bi-diagram-3"></i>
                    <h5>Sin dependencias</h5>
                    <p class="fs-sm">Este trámite no depende de ningún otro trámite.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Trámite Previo</th><th>Tipo</th><th>Expediente</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($dependencias as $dep): ?>
                        <tr>
                            <td class="fw-500 fs-sm"><?= s($dep['numero_tramite']) ?></td>
                            <td class="fs-xs"><?= s($dep['tipo_dep_nombre']) ?></td>
                            <td class="fs-xs"><?= s($dep['numero_expediente'] ?? '—') ?></td>
                            <td>
                                <?php
                                $dc = match($dep['estado'] ?? '') {
                                    'aprobado'=>'badge-estado-aprobado','rechazado'=>'badge-estado-rechazado',
                                    'cancelado'=>'badge-estado-cancelado',default=>'badge-estado-iniciado'
                                };
                                ?>
                                <span class="badge <?= $dc ?> fs-xs"><?= s(str_replace('_',' ',$dep['estado'] ?? '—')) ?></span>
                            </td>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>/?modulo=admin&accion=eliminar_dependencia" class="d-inline">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="id" value="<?= (int)$dep['id'] ?>">
                                    <input type="hidden" name="tramite_id" value="<?= (int)$tramite['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-confirm="¿Eliminar esta dependencia?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
