<?php /* Admin: Historial de Expediente */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=ver_expediente&id=<?= (int)$expedienteId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= s($titulo) ?></h5>
</div>

<?php if (empty($historial)): ?>
<div class="empty-state card p-4"><i class="bi bi-clock-history"></i><h5>Sin eventos en el historial</h5></div>
<?php else: ?>
<div class="card">
    <div class="card-header"><?= count($historial) ?> evento(s) registrado(s)</div>
    <div class="card-body">
        <ul class="timeline">
        <?php foreach ($historial as $h): ?>
        <li class="timeline-item">
            <div class="timeline-icon">
                <?php
                $icon = match($h['evento']) {
                    'CREACION'         => '<i class="bi bi-folder-plus"></i>',
                    'CAMBIO_ESTADO'    => '<i class="bi bi-arrow-repeat"></i>',
                    'NUEVO_TRAMITE'    => '<i class="bi bi-file-plus"></i>',
                    'TRAMITE_ESTADO'   => '<i class="bi bi-pencil"></i>',
                    'DOCUMENTO_SUBIDO' => '<i class="bi bi-cloud-upload"></i>',
                    default            => '<i class="bi bi-dot"></i>',
                };
                echo $icon;
                ?>
            </div>
            <div class="timeline-content">
                <div class="timeline-date">
                    <?= s(substr($h['fecha'] ?? '', 0, 16)) ?>
                    <?php if ($h['nombre_completo']): ?> &mdash; <strong><?= s($h['nombre_completo']) ?></strong><?php endif; ?>
                </div>
                <div class="timeline-event"><?= s($h['evento']) ?></div>
                <?php if ($h['descripcion']): ?>
                <div class="timeline-desc"><?= s($h['descripcion']) ?></div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
