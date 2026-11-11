<?php /* Admin: Lista de Inspecciones */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-clipboard2-check-fill me-2 text-primario"></i>Inspecciones</h5>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nueva_inspeccion" class="btn btn-primary">
        <i class="bi bi-clipboard2-plus me-2"></i>Nueva Inspección
    </a>
</div>

<div class="card">
    <div class="card-header"><?= count($inspecciones) ?> inspección(es)</div>
    <div class="card-body p-0">
        <?php if (empty($inspecciones)): ?>
        <div class="empty-state p-4"><i class="bi bi-clipboard2-x"></i><h5>Sin inspecciones</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Tipo</th>
                        <th>Contribuyente</th>
                        <th>Inspector</th>
                        <th>Programada</th>
                        <th>Realizada</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($inspecciones as $ins): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$ins['expediente_id'] ?>"
                           class="text-decoration-none fs-sm"><?= s($ins['numero_expediente']) ?></a>
                    </td>
                    <td class="fs-sm"><?= s($ins['tipo']) ?></td>
                    <td class="fs-sm"><?= s($ins['contrib_nombre']) ?></td>
                    <td class="fs-sm"><?= s($ins['inspector_nombre'] ?? '—') ?></td>
                    <td class="fs-xs text-muted"><?= $ins['programada_para'] ? s(substr($ins['programada_para'], 0, 10)) : '—' ?></td>
                    <td class="fs-xs text-muted"><?= $ins['realizada_en'] ? s(substr($ins['realizada_en'], 0, 16)) : '—' ?></td>
                    <td>
                        <?php
                        $ic = match($ins['estado']) {
                            'realizada'   => 'bg-success',
                            'cancelada'   => 'bg-danger',
                            'programada'  => 'bg-primary',
                            default       => 'bg-warning text-dark'
                        };
                        ?>
                        <span class="badge <?= $ic ?> fs-xs"><?= s($ins['estado']) ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_inspeccion&id=<?= (int)$ins['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
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
