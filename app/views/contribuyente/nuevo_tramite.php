<?php /* Formulario: Nuevo tramite */ ?>
<div class="page-intro mb-4">
    <div>
        <span class="section-eyebrow">Inicio de gestion</span>
        <h2>Iniciar nuevo tramite</h2>
        <p>Seleccione el tipo de gestion, relacione el predio correspondiente y genere su expediente con una captura clara y guiada.</p>
    </div>
    <div class="page-intro-actions">
        <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=tramites" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i>
            <span>Volver a tramites</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="surface-panel surface-panel-elevated h-100">
            <div class="panel-heading panel-heading-with-border">
                <div>
                    <span class="section-eyebrow">Captura del tramite</span>
                    <h3>Datos de la solicitud</h3>
                </div>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_tramite" class="needs-validation" novalidate>
                <?= campoCSRF() ?>

                <div class="mb-4">
                    <label class="form-label">Tipo de tramite <span class="required">*</span></label>
                    <select class="form-select" name="tipo_tramite_id" required id="tipoTramiteSelect">
                        <option value="">Seleccione el tramite a realizar</option>
                        <?php foreach ($tiposTramite as $tt): ?>
                        <option value="<?= (int)$tt['id'] ?>"
                                data-descripcion="<?= s($tt['descripcion']) ?>"
                                data-dependencia="<?= s($tt['dependencia']) ?>"
                                data-dias="<?= (int)$tt['dias_resolucion'] ?>">
                            <?= s($tt['nombre']) ?> (<?= s($tt['clave']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="tramiteInfo" class="info-banner info-banner-muted mt-3 d-none">
                        <div class="info-banner-icon"><i class="bi bi-info-circle-fill"></i></div>
                        <div>
                            <div class="fw-600 mb-1" id="tramiteDescripcion"></div>
                            <div class="info-banner-meta">
                                <span><i class="bi bi-building"></i> <span id="tramiteDependencia"></span></span>
                                <span><i class="bi bi-clock"></i> Plazo estimado: <span id="tramiteDias"></span> dias habiles</span>
                            </div>
                        </div>
                    </div>
                    <div class="invalid-feedback">Seleccione un tipo de tramite.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Predio relacionado</label>
                    <select class="form-select" name="predio_id">
                        <option value="">Sin predio especifico</option>
                        <?php foreach ($predios as $predio): ?>
                        <option value="<?= (int)$predio['id'] ?>">
                            <?= s($predio['clave_catastral']) ?> - <?= s($predio['calle'] . ' ' . $predio['numero_exterior']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Relacione el predio cuando el tramite dependa de una cuenta o clave catastral especifica.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Descripcion u observaciones</label>
                    <textarea class="form-control" name="descripcion" rows="4"
                              placeholder="Describa brevemente el motivo, alcance o detalles relevantes del tramite."
                              maxlength="500"></textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-plus"></i>
                        <span>Generar expediente y continuar</span>
                    </button>
                    <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=tramites" class="btn btn-outline-primary">Cancelar</a>
                </div>
            </form>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="surface-panel mb-4">
            <div class="panel-heading panel-heading-with-border">
                <div>
                    <span class="section-eyebrow">Como funciona</span>
                    <h3>Ruta del tramite</h3>
                </div>
            </div>
            <div class="process-track process-track-compact">
                <article class="process-track-item is-current">
                    <span class="process-track-number">1</span>
                    <div>
                        <h4>Seleccione el tipo</h4>
                        <p>Elija la gestion que desea realizar para cargar el contexto correcto.</p>
                    </div>
                </article>
                <article class="process-track-item is-current">
                    <span class="process-track-number">2</span>
                    <div>
                        <h4>Vincule el predio</h4>
                        <p>Asocie la cuenta catastral cuando aplique al procedimiento.</p>
                    </div>
                </article>
                <article class="process-track-item is-current">
                    <span class="process-track-number">3</span>
                    <div>
                        <h4>Revise requisitos</h4>
                        <p>El sistema mostrara documentos obligatorios, vigencias y acciones siguientes.</p>
                    </div>
                </article>
            </div>
        </section>

        <?php if (!empty($predios)): ?>
        <section class="surface-panel h-100">
            <div class="panel-heading panel-heading-with-border">
                <div>
                    <span class="section-eyebrow">Predios disponibles</span>
                    <h3>Sus inmuebles vinculados</h3>
                </div>
            </div>
            <div class="mini-property-list">
                <?php foreach ($predios as $p): ?>
                <article class="mini-property-item">
                    <strong><?= s($p['clave_catastral']) ?></strong>
                    <span><?= s($p['calle'] . ' ' . $p['numero_exterior']) ?></span>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <div class="alert alert-warning alert-soft mt-0 fs-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            No tiene predios registrados. Si el tramite depende de un inmueble, registre uno antes de continuar.
            <a href="<?= BASE_URL ?>/?modulo=contribuyente&accion=nuevo_predio" class="alert-link ms-1">Registrar predio</a>
        </div>
        <?php endif; ?>
    </div>
</div>
