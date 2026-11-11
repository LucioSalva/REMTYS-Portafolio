<?php /* Admin: Tipos de Trámite */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=catalogos" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0"><i class="bi bi-journal-text me-2 text-primario"></i>Tipos de Trámite</h5>
    </div>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tipo_tramite" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Tipo
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($tiposTramite)): ?>
        <div class="empty-state p-4"><i class="bi bi-journal"></i><h5>Sin tipos de trámite</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Clave</th><th>Nombre</th><th>Dependencia</th><th>Días</th><th>Requisitos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tiposTramite as $tt): ?>
                <tr>
                    <td><code><?= s($tt['clave']) ?></code></td>
                    <td class="fw-500"><?= s($tt['nombre']) ?></td>
                    <td class="fs-xs text-muted"><?= s($tt['dependencia'] ?? '—') ?></td>
                    <td class="text-center"><?= (int)$tt['dias_resolucion'] ?></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$tt['total_requisitos'] ?></span></td>
                    <td><span class="badge <?= $tt['activo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $tt['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_tipo_tramite&id=<?= (int)$tt['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=requisitos&tipo_tramite_id=<?= (int)$tt['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Requisitos" data-bs-toggle="tooltip"><i class="bi bi-list-check"></i></a>
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
