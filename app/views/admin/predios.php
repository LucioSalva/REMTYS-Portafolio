<?php /* Admin: Lista de Predios */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-map-fill me-2 text-primario"></i>Predios</h5>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_predio" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Predio
    </a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="predios">
            <input type="text" class="form-control" name="q" value="<?= s($busqueda ?? '') ?>"
                   placeholder="Clave catastral, cuenta predial, municipio...">
            <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i></button>
            <?php if ($busqueda): ?><a href="<?= BASE_URL ?>/?modulo=admin&accion=predios" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= count($predios) ?> predio(s)</div>
    <div class="card-body p-0">
        <?php if (empty($predios)): ?>
        <div class="empty-state p-4"><i class="bi bi-map"></i><h5>Sin predios</h5></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Clave Catastral</th>
                        <th>Cuenta Predial</th>
                        <th>Nombre / Descripción</th>
                        <th>Ubicación</th>
                        <th>Uso de Suelo</th>
                        <th>Propietarios</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($predios as $p): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_predio&id=<?= (int)$p['id'] ?>" class="fw-500 text-decoration-none"><?= s($p['clave_catastral']) ?></a></td>
                    <td class="fs-xs"><?= s($p['cuenta_predial']) ?></td>
                    <td class="fs-sm"><?= s($p['nombre_descripcion'] ?? '—') ?></td>
                    <td class="fs-xs"><?= s($p['colonia'] . ', ' . $p['municipio']) ?></td>
                    <td class="fs-xs"><?= s($p['uso_suelo'] ?? '—') ?></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$p['total_propietarios'] ?></span></td>
                    <td>
                        <span class="badge <?= $p['activo'] ? 'bg-success' : 'bg-secondary' ?> fs-xs">
                            <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_predio&id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_predio&id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
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
