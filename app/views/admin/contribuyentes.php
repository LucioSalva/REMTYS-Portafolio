<?php /* Admin: Lista de Contribuyentes */ ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-people-fill me-2 text-primario"></i>Contribuyentes</h5>
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_contribuyente" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Nuevo Contribuyente
    </a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="" class="d-flex gap-2">
            <input type="hidden" name="modulo" value="admin">
            <input type="hidden" name="accion" value="contribuyentes">
            <input type="text" class="form-control" name="q"
                   value="<?= s($busqueda ?? '') ?>"
                   placeholder="Buscar por nombre, RFC o email...">
            <button type="submit" class="btn btn-primary px-3">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($busqueda): ?>
            <a href="<?= BASE_URL ?>/?modulo=admin&accion=contribuyentes" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><?= count($contribuyentes) ?> contribuyente(s) <?= $busqueda ? 'encontrado(s)' : 'en esta pagina' ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($contribuyentes)): ?>
        <div class="empty-state p-4">
            <i class="bi bi-people"></i>
            <h5>Sin resultados</h5>
            <?php if ($busqueda): ?>
            <p class="fs-sm">No se encontraron contribuyentes con "<?= s($busqueda) ?>".</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Contribuyente</th>
                        <th>RFC</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Predios</th>
                        <th>Expedientes</th>
                        <th>Ult. Acceso</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contribuyentes as $c): ?>
                    <tr>
                        <td>
                            <div class="fw-500"><?= s($c['nombre_completo']) ?></div>
                            <?php if ($c['tipo_persona'] === 'moral' && !empty($c['representante_legal'])): ?>
                            <div class="fs-xs text-muted">Rep. legal: <?= s($c['representante_legal']) ?></div>
                            <?php elseif ($c['tipo_persona'] === 'fisica' && !empty($c['curp'])): ?>
                            <div class="fs-xs text-muted">CURP: <?= s($c['curp']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><code class="fs-xs"><?= s($c['rfc']) ?></code></td>
                        <td class="fs-sm"><?= s($c['email']) ?></td>
                        <td>
                            <span class="badge <?= $c['tipo_persona'] === 'moral' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success' ?> fs-xs">
                                <?= $c['tipo_persona'] === 'moral' ? 'Moral' : 'Fisica' ?>
                            </span>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$c['total_predios'] ?></span></td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$c['total_expedientes'] ?></span></td>
                        <td class="fs-xs text-muted"><?= $c['ultimo_acceso'] ? s(substr($c['ultimo_acceso'], 0, 16)) : '�' ?></td>
                        <td>
                            <span class="badge <?= $c['usuario_activo'] ? 'bg-success' : 'bg-danger' ?> fs-xs">
                                <?= $c['usuario_activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_contribuyente&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/?modulo=admin&accion=editar_contribuyente&id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($next_cursor)): ?>
        <div class="card-footer text-center">
            <a href="<?= s(urlPaginacion($next_cursor)) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-down-circle me-1"></i>Cargar siguientes resultados
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
