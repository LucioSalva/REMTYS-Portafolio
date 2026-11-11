<?php /* Admin: Lista de Trámites */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-file-earmark-fill me-2 text-primario"></i>Trámites</h5>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="tramites">
            <input type="text" class="form-control" style="max-width:300px;" name="q"
                   value="<?= s($busqueda ?? '') ?>" placeholder="Número, tipo, contribuyente...">
            <select class="form-select" style="max-width:200px;" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (['iniciado','en_revision','documentacion_pendiente','aprobado','rechazado','cancelado'] as $est): ?>
                <option value="<?= $est ?>" <?= ($estadoFiltro ?? '') === $est ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i></button>
            <?php if ($busqueda || $estadoFiltro): ?>
            <a href="<?= BASE_URL ?>/?modulo=admin&accion=tramites" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= count($tramites) ?> trámite(s)</div>
    <div class="card-body p-0">
        <?php if (empty($tramites)): ?>
        <div class="empty-state p-4"><i class="bi bi-file-x"></i><h5>Sin trámites</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Trámite</th>
                        <th>Tipo</th>
                        <th>Contribuyente</th>
                        <th>Expediente</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tramites as $t): ?>
                <tr>
                    <td><strong><?= s($t['numero_tramite']) ?></strong></td>
                    <td class="fs-sm"><?= s($t['tipo_nombre']) ?></td>
                    <td class="fs-sm"><?= s($t['contrib_nombre']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$t['expediente_id'] ?>"
                           class="text-decoration-none fs-sm"><?= s($t['numero_expediente']) ?></a>
                    </td>
                    <td>
                        <?php
                        $tc = match($t['estado']) {
                            'aprobado'=>'badge-estado-aprobado','rechazado'=>'badge-estado-rechazado',
                            'cancelado'=>'badge-estado-cancelado','en_revision'=>'badge-estado-en_revision',
                            'documentacion_pendiente'=>'badge-estado-documentacion_pendiente',
                            default=>'badge-estado-iniciado'
                        };
                        ?>
                        <span class="badge <?= $tc ?>"><?= s(str_replace('_',' ',$t['estado'])) ?></span>
                    </td>
                    <td class="fs-xs text-muted"><?= s($t['fecha_inicio']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_tramite&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_tramite&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=dependencias_tramite&tramite_id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-dark" title="Dependencias" data-bs-toggle="tooltip"><i class="bi bi-diagram-3"></i></a>
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
