<?php /* Admin: Lista de Expedientes */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-folder-fill me-2 text-primario"></i>Expedientes</h5>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_expediente" class="btn btn-primary">
        <i class="bi bi-folder-plus me-2"></i>Nuevo Expediente
    </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="expedientes">
            <input type="text" class="form-control" style="max-width:300px;" name="q"
                   value="<?= s($busqueda ?? '') ?>" placeholder="Número, contribuyente, RFC...">
            <select class="form-select" style="max-width:180px;" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach (['abierto','en_proceso','resuelto','cerrado','cancelado'] as $est): ?>
                <option value="<?= $est ?>" <?= ($estadoFiltro ?? '') === $est ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$est)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i></button>
            <?php if ($busqueda || $estadoFiltro): ?>
            <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= count($expedientes) ?> expediente(s)</div>
    <div class="card-body p-0">
        <?php if (empty($expedientes)): ?>
        <div class="empty-state p-4"><i class="bi bi-folder-x"></i><h5>Sin expedientes</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Contribuyente</th>
                        <th>RFC</th>
                        <th>Predio</th>
                        <th>Estado</th>
                        <th>Trámites</th>
                        <th>Apertura</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($expedientes as $exp): ?>
                <tr>
                    <td><strong><?= s($exp['numero_expediente']) ?></strong></td>
                    <td class="fs-sm"><?= s($exp['contribuyente_nombre']) ?></td>
                    <td><code class="fs-xs"><?= s($exp['rfc']) ?></code></td>
                    <td class="fs-xs text-muted"><?= s($exp['clave_catastral'] ?? '—') ?></td>
                    <td>
                        <?php
                        $cl = match($exp['estado']) {
                            'abierto'=>'badge-estado-abierto','en_proceso'=>'badge-estado-en_proceso',
                            'resuelto'=>'badge-estado-resuelto','cerrado'=>'badge-estado-cerrado',
                            'cancelado'=>'badge-estado-cancelado',default=>'bg-secondary text-white'
                        };
                        ?>
                        <span class="badge <?= $cl ?>"><?= s(str_replace('_',' ',$exp['estado'])) ?></span>
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$exp['total_tramites'] ?></span></td>
                    <td class="fs-xs text-muted"><?= s($exp['fecha_apertura']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_expediente&id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=historial&expediente_id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-dark" title="Historial" data-bs-toggle="tooltip"><i class="bi bi-clock-history"></i></a>
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
