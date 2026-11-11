<?php /* Admin: Tipos de Documento */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=catalogos" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-primario"></i>Tipos de Documento</h5>
    </div>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tipo_documento" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Tipo
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($tiposDoc)): ?>
        <div class="empty-state p-4"><i class="bi bi-file-earmark"></i><h5>Sin tipos de documento</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Clave</th><th>Nombre</th><th>Reutilizable</th><th>Vigencia</th><th>Meses</th><th>Formatos</th><th>Estado</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tiposDoc as $td): ?>
                <tr>
                    <td><code class="fs-xs"><?= s($td['clave']) ?></code></td>
                    <td class="fw-500"><?= s($td['nombre']) ?></td>
                    <td class="text-center">
                        <?php if ($td['reutilizable']): ?>
                        <span class="badge bg-success-subtle text-success fs-xs"><i class="bi bi-check-circle-fill me-1"></i>Sí</span>
                        <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary fs-xs">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($td['requiere_vigencia']): ?>
                        <span class="badge bg-warning-subtle text-warning fs-xs">Sí</span>
                        <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary fs-xs">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $td['meses_vigencia'] ? (int)$td['meses_vigencia'] : '—' ?></td>
                    <td class="fs-xs text-muted"><?= s($td['extensiones_permitidas']) ?></td>
                    <td><span class="badge <?= $td['activo'] ? 'bg-success' : 'bg-secondary' ?>"><?= $td['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td>
                        <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_tipo_documento&id=<?= (int)$td['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
