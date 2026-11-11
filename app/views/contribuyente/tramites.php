<?php /* Vista: Mis Tramites */ ?>
<div class="page-intro">
    <div class="page-intro-content">
        <span class="page-intro-eyebrow">Seguimiento de gestion</span>
        <h1 class="page-intro-title">Mis Tramites</h1>
        <p class="page-intro-text">Consulte cada solicitud, su expediente asociado y los requisitos pendientes para avanzar sin perder claridad en el proceso.</p>
    </div>
    <div class="page-intro-actions">
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nuevo tramite
        </a>
    </div>
</div>

<?php if (empty($tramites)): ?>
<div class="empty-state card p-4">
    <i class="bi bi-file-earmark-x"></i>
    <h5>No tiene tramites registrados</h5>
    <p class="fs-sm">Inicie un nuevo tramite para comenzar su gestion dentro del portal.</p>
    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Iniciar tramite
    </a>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
        <span><?= count($tramites) ?> tramite(s) registrados</span>
        <input type="text" class="form-control form-control-sm" style="max-width: 260px;"
               placeholder="Buscar tramite..." data-table-search="tablaTramites">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tablaTramites">
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Tipo de Tramite</th>
                        <th>Predio</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tramites as $t):
                    $badgeClass = match($t['estado']) {
                        'iniciado' => 'bg-secondary',
                        'documentacion_pendiente' => 'bg-warning text-dark',
                        'en_revision' => 'bg-info text-dark',
                        'aprobado' => 'bg-success',
                        'rechazado' => 'bg-danger',
                        'cancelado' => 'bg-dark',
                        default => 'bg-secondary',
                    };
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=ver_expediente&id=<?= (int)$t['expediente_id'] ?>" class="fw-500 text-decoration-none">
                            <?= s($t['numero_expediente']) ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-500"><?= s($t['tipo_nombre']) ?></div>
                        <code class="fs-xs"><?= s($t['tipo_clave']) ?></code>
                    </td>
                    <td class="fs-xs text-muted"><?= s($t['clave_catastral'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= s(str_replace('_', ' ', $t['estado'])) ?>
                        </span>
                    </td>
                    <td class="fs-xs text-muted"><?= s(substr($t['created_at'], 0, 10)) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=requisitos_tramite&id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list-check me-1"></i>Requisitos
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
