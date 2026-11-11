<?php /* Vista: Mis Expedientes */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-folder2-open me-2 text-primario"></i>Mis Expedientes</h5>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Trámite
    </a>
</div>

<?php if (empty($expedientes)): ?>
<div class="empty-state card p-4">
    <i class="bi bi-folder-x"></i>
    <h5>No tiene expedientes aún</h5>
    <p class="fs-sm">Inicie un trámite para crear su primer expediente.</p>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Iniciar Trámite
    </a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><?= count($expedientes) ?> expediente(s)</span>
        <input type="text" class="form-control form-control-sm" style="max-width:240px"
               placeholder="Buscar expediente..." data-table-search="tablaExpedientes">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaExpedientes">
                <thead>
                    <tr>
                        <th>Expediente</th>
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
                        <td>
                            <?php if ($exp['clave_catastral']): ?>
                            <div class="fs-sm"><?= s($exp['clave_catastral']) ?></div>
                            <div class="fs-xs text-muted"><?= s($exp['calle'] . ' ' . $exp['numero_exterior']) ?></div>
                            <?php else: ?>
                            <span class="text-muted fs-xs">Sin predio</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $cl = match($exp['estado']) {
                                'abierto'    => 'badge-estado-abierto',
                                'en_proceso' => 'badge-estado-en_proceso',
                                'resuelto'   => 'badge-estado-resuelto',
                                'cerrado'    => 'badge-estado-cerrado',
                                'cancelado'  => 'badge-estado-cancelado',
                                default      => 'bg-secondary text-white'
                            };
                            ?>
                            <span class="badge <?= $cl ?>"><?= s(str_replace('_',' ', $exp['estado'])) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= (int)$exp['total_tramites'] ?></span>
                            <?php if ((int)$exp['tramites_aprobados'] > 0): ?>
                            <span class="badge bg-success ms-1"><?= (int)$exp['tramites_aprobados'] ?> aprobados</span>
                            <?php endif; ?>
                        </td>
                        <td class="fs-xs text-muted"><?= s($exp['fecha_apertura']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_expediente&id=<?= (int)$exp['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
