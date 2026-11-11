<?php /* Admin: Reportes y Estadísticas */ ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/?modulo=admin&accion=dashboard" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2 text-primario"></i>Reportes y Estadísticas</h5>
</div>

<div class="row g-4">

    <!-- Expedientes por Estado -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-folder2 me-2"></i>Expedientes por Estado</div>
            <div class="card-body p-0">
                <?php if (empty($stats['expedientes_por_estado'])): ?>
                <div class="empty-state p-4"><i class="bi bi-folder"></i><h5>Sin datos</h5></div>
                <?php else: ?>
                <?php
                $totalExp = array_sum(array_column($stats['expedientes_por_estado'], 'total'));
                ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['expedientes_por_estado'] as $row):
                        $pct = $totalExp > 0 ? round((int)$row['total'] * 100 / $totalExp) : 0;
                        $barClass = match($row['estado']) {
                            'abierto'    => 'bg-primary',
                            'en_proceso' => 'bg-warning',
                            'resuelto'   => 'bg-success',
                            'cerrado'    => 'bg-secondary',
                            'cancelado'  => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                    ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-sm fw-500"><?= s(str_replace('_', ' ', ucfirst($row['estado']))) ?></span>
                            <span class="badge bg-light text-dark border"><?= (int)$row['total'] ?></span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar <?= $barClass ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="px-3 py-2 fs-xs text-muted border-top">Total: <?= $totalExp ?> expedientes</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Trámites por Tipo -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Trámites por Tipo</div>
            <div class="card-body p-0">
                <?php if (empty($stats['tramites_por_tipo'])): ?>
                <div class="empty-state p-4"><i class="bi bi-file-earmark"></i><h5>Sin datos</h5></div>
                <?php else: ?>
                <?php
                $totalTram = array_sum(array_column($stats['tramites_por_tipo'], 'total'));
                $colores = ['bg-primary','bg-success','bg-info','bg-warning','bg-danger','bg-secondary','bg-dark'];
                ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['tramites_por_tipo'] as $i => $row):
                        $pct = $totalTram > 0 ? round((int)$row['total'] * 100 / $totalTram) : 0;
                        $c = $colores[$i % count($colores)];
                    ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-sm fw-500"><?= s($row['nombre']) ?></span>
                            <span class="badge bg-light text-dark border"><?= (int)$row['total'] ?></span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar <?= $c ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="px-3 py-2 fs-xs text-muted border-top">Total: <?= $totalTram ?> trámites</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Documentos por Estado de Validación -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-file-earmark-check me-2"></i>Documentos por Validación</div>
            <div class="card-body p-0">
                <?php if (empty($stats['documentos_por_estado'])): ?>
                <div class="empty-state p-4"><i class="bi bi-files"></i><h5>Sin datos</h5></div>
                <?php else: ?>
                <?php
                $totalDocs = array_sum(array_column($stats['documentos_por_estado'], 'total'));
                ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($stats['documentos_por_estado'] as $row):
                        $pct = $totalDocs > 0 ? round((int)$row['total'] * 100 / $totalDocs) : 0;
                        $barClass = match($row['estado_validacion']) {
                            'pendiente'  => 'bg-warning',
                            'aprobado'   => 'bg-success',
                            'rechazado'  => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                        $badgeClass = match($row['estado_validacion']) {
                            'pendiente'  => 'bg-warning text-dark',
                            'aprobado'   => 'bg-success',
                            'rechazado'  => 'bg-danger',
                            default      => 'bg-secondary',
                        };
                    ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge <?= $badgeClass ?> fs-xs"><?= s(ucfirst($row['estado_validacion'])) ?></span>
                            <span class="badge bg-light text-dark border"><?= (int)$row['total'] ?></span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar <?= $barClass ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="px-3 py-2 fs-xs text-muted border-top">Total (versiones activas): <?= $totalDocs ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Resumen ejecutivo -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-clipboard-data me-2"></i>Resumen Ejecutivo</div>
            <div class="card-body">
                <div class="row g-4 text-center">
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-primario"><?= array_sum(array_column($stats['expedientes_por_estado'], 'total')) ?></div>
                        <div class="fs-xs text-muted text-uppercase">Total Expedientes</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-success">
                            <?php
                            $resueltos = 0;
                            foreach ($stats['expedientes_por_estado'] as $row) {
                                if ($row['estado'] === 'resuelto') $resueltos = (int)$row['total'];
                            }
                            echo $resueltos;
                            ?>
                        </div>
                        <div class="fs-xs text-muted text-uppercase">Resueltos</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-warning">
                            <?php
                            $enProceso = 0;
                            foreach ($stats['expedientes_por_estado'] as $row) {
                                if ($row['estado'] === 'en_proceso') $enProceso = (int)$row['total'];
                            }
                            echo $enProceso;
                            ?>
                        </div>
                        <div class="fs-xs text-muted text-uppercase">En Proceso</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-info">
                            <?= array_sum(array_column($stats['tramites_por_tipo'], 'total')) ?>
                        </div>
                        <div class="fs-xs text-muted text-uppercase">Total Trámites</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-danger">
                            <?php
                            $pendientesDoc = 0;
                            foreach ($stats['documentos_por_estado'] as $row) {
                                if ($row['estado_validacion'] === 'pendiente') $pendientesDoc = (int)$row['total'];
                            }
                            echo $pendientesDoc;
                            ?>
                        </div>
                        <div class="fs-xs text-muted text-uppercase">Docs Pendientes</div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="fs-2 fw-700 text-success">
                            <?php
                            $aprobados = 0;
                            foreach ($stats['documentos_por_estado'] as $row) {
                                if ($row['estado_validacion'] === 'aprobado') $aprobados = (int)$row['total'];
                            }
                            echo $aprobados;
                            ?>
                        </div>
                        <div class="fs-xs text-muted text-uppercase">Docs Aprobados</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones de exportación -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-download me-2"></i>Exportar Datos</div>
            <div class="card-body">
                <p class="fs-sm text-muted mb-3">Genere reportes en formato imprimible para distribución interna.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-print>
                        <i class="bi bi-printer me-2"></i>Imprimir esta página
                    </button>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=expedientes" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-folder2 me-2"></i>Ver todos los expedientes
                    </a>
                    <a href="<?= BASE_URL ?>/?modulo=admin&accion=validacion_documentos" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-file-earmark-check me-2"></i>Cola de validación
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
