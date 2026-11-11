<?php /* Admin: Hub de Catálogos */ ?>
<div class="mb-4">
    <h5 class="mb-0"><i class="bi bi-list-ul me-2 text-primario"></i>Catálogos del Sistema</h5>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3" style="font-size:2.5rem; color:var(--color-primario);"><i class="bi bi-journal-text"></i></div>
                <h5>Tipos de Trámite</h5>
                <p class="text-muted fs-sm">Gestione los tipos de trámites disponibles, plazos y dependencias responsables.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <span class="badge bg-primary fs-sm px-3"><?= (int)$cntTiposTramite ?> registros</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top d-flex gap-2">
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_tramite" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-list me-1"></i>Ver Lista
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tipo_tramite" class="btn btn-primary flex-fill">
                    <i class="bi bi-plus me-1"></i>Nuevo
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3" style="font-size:2.5rem; color:var(--color-secundario);"><i class="bi bi-file-earmark-text"></i></div>
                <h5>Tipos de Documento</h5>
                <p class="text-muted fs-sm">Configure los tipos de documentos, vigencias y formatos aceptados.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <span class="badge bg-primary fs-sm px-3"><?= (int)$cntTiposDoc ?> registros</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top d-flex gap-2">
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=tipos_documento" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-list me-1"></i>Ver Lista
                </a>
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=nuevo_tipo_documento" class="btn btn-primary flex-fill">
                    <i class="bi bi-plus me-1"></i>Nuevo
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3" style="font-size:2.5rem; color:var(--color-exito);"><i class="bi bi-list-check"></i></div>
                <h5>Requisitos Documentales</h5>
                <p class="text-muted fs-sm">Defina qué documentos requiere cada tipo de trámite y sus condiciones.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <span class="badge bg-primary fs-sm px-3"><?= (int)$cntRequisitos ?> registros</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top d-flex gap-2">
                <a href="<?= BASE_URL ?>/?modulo=admin&accion=requisitos" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-list me-1"></i>Ver Lista
                </a>
            </div>
        </div>
    </div>
</div>
