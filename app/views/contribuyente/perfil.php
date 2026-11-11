<?php /* Vista: Mi Perfil */ ?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h5 class="mb-0"><i class="bi bi-person-circle me-2 text-primario"></i>Mi Perfil</h5>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-person me-2"></i>Datos Personales</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                    <i class="bi bi-pencil me-1"></i>Editar
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">
                            <?= $contribuyente['tipo_persona'] === 'moral' ? 'Razon Social' : 'Nombre Completo' ?>
                        </div>
                        <div class="fw-500"><?= s($contribuyente['nombre_completo']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">RFC</div>
                        <code><?= s($contribuyente['rfc']) ?></code>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Tipo Persona</div>
                        <span class="badge <?= $contribuyente['tipo_persona'] === 'moral' ? 'bg-primary' : 'bg-info text-dark' ?>">
                            <?= $contribuyente['tipo_persona'] === 'moral' ? 'Moral' : 'Fisica' ?>
                        </span>
                    </div>
                    <?php if ($contribuyente['tipo_persona'] === 'fisica' && !empty($contribuyente['curp'])): ?>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">CURP</div>
                        <code class="fs-xs"><?= s($contribuyente['curp']) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php if ($contribuyente['tipo_persona'] === 'moral'): ?>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Representante Legal</div>
                        <div><?= s($contribuyente['representante_legal'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">RFC Representante</div>
                        <code class="fs-xs"><?= s($contribuyente['rfc_representante'] ?? '—') ?></code>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Email de Acceso</div>
                        <div><?= s($contribuyente['email']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Email de Contacto</div>
                        <div><?= s($contribuyente['email_contacto'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Telefono</div>
                        <div><?= s($contribuyente['telefono'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Registro</div>
                        <div class="fs-xs text-muted"><?= s($contribuyente['created_at']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Domicilio Fiscal</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Calle</div>
                        <div><?= s($contribuyente['calle'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">No. Exterior</div>
                        <div><?= s($contribuyente['numero_exterior'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Colonia</div>
                        <div><?= s($contribuyente['colonia'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Municipio</div>
                        <div><?= s($contribuyente['municipio'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Estado</div>
                        <div><?= s($contribuyente['estado'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="fw-500 fs-xs text-muted text-uppercase mb-1">Codigo Postal</div>
                        <div><?= s($contribuyente['codigo_postal'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-primario d-inline-flex align-items-center justify-content-center mb-3" style="width:72px;height:72px;">
                    <i class="bi bi-person-fill text-white fs-1"></i>
                </div>
                <h6 class="mb-1"><?= s($contribuyente['nombre_completo']) ?></h6>
                <div class="fs-xs text-muted mb-2"><?= s($contribuyente['email']) ?></div>
                <span class="badge bg-success">Cuenta Activa</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header fs-sm">Acciones</div>
            <div class="list-group list-group-flush">
                <button class="list-group-item list-group-item-action fs-sm" data-bs-toggle="modal" data-bs-target="#modalEditarPerfil">
                    <i class="bi bi-pencil me-2 text-primary"></i>Editar datos de contacto
                </button>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=documentos" class="list-group-item list-group-item-action fs-sm">
                    <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Gestionar documentos
                </a>
                <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=obligaciones" class="list-group-item list-group-item-action fs-sm">
                    <i class="bi bi-cash-stack me-2 text-primary"></i>Ver obligaciones fiscales
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-labelledby="modalEditarPerfilLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarPerfilLabel"><i class="bi bi-pencil me-2"></i>Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/?modulo=contribuyente&accion=editar_perfil" class="needs-validation" novalidate>
                <?= campoCSRF() ?>
                <input type="hidden" name="tipo_persona" value="<?= s($contribuyente['tipo_persona']) ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label"><?= $contribuyente['tipo_persona'] === 'moral' ? 'Razon social' : 'Nombre Completo' ?> <span class="required">*</span></label>
                            <input type="text" class="form-control" name="nombre_completo" value="<?= s($contribuyente['nombre_completo']) ?>" required maxlength="255">
                        </div>
                        <?php if ($contribuyente['tipo_persona'] === 'fisica'): ?>
                        <div class="col-md-6">
                            <label class="form-label">CURP</label>
                            <input type="text" class="form-control text-uppercase" name="curp" value="<?= s($contribuyente['curp'] ?? '') ?>" maxlength="18">
                        </div>
                        <?php else: ?>
                        <div class="col-md-6">
                            <label class="form-label">Representante Legal <span class="required">*</span></label>
                            <input type="text" class="form-control" name="representante_legal" value="<?= s($contribuyente['representante_legal'] ?? '') ?>" required maxlength="400">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">RFC del Representante <span class="required">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="rfc_representante" value="<?= s($contribuyente['rfc_representante'] ?? '') ?>" required maxlength="13" pattern="[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}">
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="tel" class="form-control" name="telefono" value="<?= s($contribuyente['telefono'] ?? '') ?>" maxlength="20" placeholder="(555) 123-4567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email de Contacto</label>
                            <input type="email" class="form-control" name="email_contacto" value="<?= s($contribuyente['email_contacto'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-12"><hr class="my-1"><div class="fw-500 fs-sm mb-2">Domicilio Fiscal</div></div>
                        <div class="col-md-8">
                            <label class="form-label">Calle</label>
                            <input type="text" class="form-control" name="calle" value="<?= s($contribuyente['calle'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Exterior</label>
                            <input type="text" class="form-control" name="numero_exterior" value="<?= s($contribuyente['numero_exterior'] ?? '') ?>" maxlength="20">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Colonia</label>
                            <input type="text" class="form-control" name="colonia" value="<?= s($contribuyente['colonia'] ?? '') ?>" maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Municipio</label>
                            <input type="text" class="form-control" name="municipio" value="<?= s($contribuyente['municipio'] ?? '') ?>" maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">C.P.</label>
                            <input type="text" class="form-control" name="codigo_postal" value="<?= s($contribuyente['codigo_postal'] ?? '') ?>" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <input type="text" class="form-control" name="estado" value="<?= s($contribuyente['estado'] ?? '') ?>" maxlength="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
