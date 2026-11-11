<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Contribuyente - REMTYS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="register-page">
<div class="container register-shell">
    <div class="register-header">
        <a href="<?= BASE_URL ?>" class="auth-back-link auth-back-link--center"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        <div class="register-header-badge">
            <i class="bi bi-building-fill"></i>
            <span>REMTYS</span>
        </div>
        <h1 class="register-title">Registro de Contribuyente</h1>
        <p class="register-subtitle">Complete el formulario para habilitar su acceso al portal de tramites.</p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= s($error) ?></div>
    <?php endif; ?>

    <div class="card register-card">
        <div class="card-body p-4 p-lg-5">
            <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=procesar_registro" class="needs-validation" novalidate>
                <?= campoCSRF() ?>

                <div class="form-section-title">Tipo de Persona</div>
                <div class="mb-3">
                    <label for="tipo_persona" class="form-label">Tipo de persona <span class="required">*</span></label>
                    <select class="form-select" id="tipo_persona" name="tipo_persona">
                        <option value="fisica" <?= ($_POST['tipo_persona'] ?? 'fisica') === 'fisica' ? 'selected' : '' ?>>Persona Fisica</option>
                        <option value="moral" <?= ($_POST['tipo_persona'] ?? '') === 'moral' ? 'selected' : '' ?>>Persona Moral (Empresa)</option>
                    </select>
                </div>

                <div class="form-section-title">Datos Generales</div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="nombre_completo" class="form-label">Nombre completo / Razon social <span class="required">*</span></label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                               value="<?= s($_POST['nombre_completo'] ?? '') ?>"
                               placeholder="Apellido Paterno Materno Nombre(s)"
                               required minlength="3" maxlength="400">
                        <div class="invalid-feedback">El nombre es requerido y debe tener al menos 3 caracteres.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="rfc" class="form-label">RFC <span class="required">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="rfc" name="rfc"
                               value="<?= s($_POST['rfc'] ?? '') ?>"
                               placeholder="XAXX010101000" required maxlength="13"
                               pattern="[A-Z�&]{3,4}[0-9]{6}[A-Z0-9]{3}">
                        <div class="invalid-feedback">Ingrese un RFC valido.</div>
                    </div>
                    <div class="col-md-6 campo-fisica">
                        <label for="curp" class="form-label">CURP</label>
                        <input type="text" class="form-control text-uppercase" id="curp" name="curp"
                               value="<?= s($_POST['curp'] ?? '') ?>"
                               placeholder="XAXX010101HDFYYY00" maxlength="18">
                    </div>
                    <div class="col-md-6 campo-moral">
                        <label for="representante_legal" class="form-label">Representante legal <span class="required">*</span></label>
                        <input type="text" class="form-control" id="representante_legal" name="representante_legal"
                               value="<?= s($_POST['representante_legal'] ?? '') ?>"
                               data-requiredMoral="true" maxlength="400"
                               placeholder="Nombre del representante legal">
                    </div>
                    <div class="col-md-6 campo-moral">
                        <label for="rfc_representante" class="form-label">RFC del representante <span class="required">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="rfc_representante" name="rfc_representante"
                               value="<?= s($_POST['rfc_representante'] ?? '') ?>"
                               data-requiredMoral="true" maxlength="13"
                               pattern="[A-Z�&]{3,4}[0-9]{6}[A-Z0-9]{3}"
                               placeholder="XAXX010101000">
                        <div class="invalid-feedback">Ingrese un RFC valido para el representante legal.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Telefono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono"
                               value="<?= s($_POST['telefono'] ?? '') ?>"
                               placeholder="10 digitos" maxlength="20">
                    </div>
                </div>

                <div class="form-section-title">Domicilio Fiscal</div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="calle" class="form-label">Calle</label>
                        <input type="text" class="form-control" id="calle" name="calle"
                               value="<?= s($_POST['calle'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label for="numero_exterior" class="form-label">Numero Ext.</label>
                        <input type="text" class="form-control" id="numero_exterior" name="numero_exterior"
                               value="<?= s($_POST['numero_exterior'] ?? '') ?>" maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label for="colonia" class="form-label">Colonia</label>
                        <input type="text" class="form-control" id="colonia" name="colonia"
                               value="<?= s($_POST['colonia'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label for="municipio" class="form-label">Municipio</label>
                        <input type="text" class="form-control" id="municipio" name="municipio"
                               value="<?= s($_POST['municipio'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="col-md-8">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado"
                               value="<?= s($_POST['estado'] ?? '') ?>" maxlength="100">
                    </div>
                    <div class="col-md-4">
                        <label for="codigo_postal" class="form-label">C.P.</label>
                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal"
                               value="<?= s($_POST['codigo_postal'] ?? '') ?>"
                               placeholder="00000" maxlength="5" pattern="[0-9]{5}">
                        <div class="invalid-feedback">Ingrese un codigo postal de 5 digitos.</div>
                    </div>
                </div>

                <div class="form-section-title">Acceso al Sistema</div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="email" class="form-label">Correo electronico <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= s($_POST['email'] ?? '') ?>"
                               placeholder="correo@ejemplo.com" required autocomplete="email">
                        <div class="form-note mt-2"><i class="bi bi-envelope-check"></i> Enviaremos un enlace de verificacion a este correo antes de permitir el acceso al portal.</div>
                        <div class="form-text">Debe poder recibir correos en esta direccion.</div>
                        <div class="invalid-feedback">Ingrese un correo valido.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contrasena <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Cree una contrasena segura"
                                   required minlength="8" autocomplete="new-password"
                                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}"
                                   title="Use minimo 8 caracteres con mayuscula, minuscula, numero y caracter especial.">
                            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">La contrasena debe cumplir la politica de seguridad.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirmar Contrasena <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   placeholder="Repita la contrasena" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password_confirm">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">Las contrasenas deben coincidir.</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="password-strength" data-password-strength>
                            <div class="password-strength-bar">
                                <span class="password-strength-fill" data-password-strength-fill data-strength="empty"></span>
                            </div>
                            <div class="password-strength-label" data-password-strength-label>Use una contrasena robusta para proteger su cuenta.</div>
                            <ul class="password-rules mb-0">
                                <li class="password-rule" data-password-rule="length">Minimo 8 caracteres</li>
                                <li class="password-rule" data-password-rule="upper">Al menos una mayuscula</li>
                                <li class="password-rule" data-password-rule="lower">Al menos una minuscula</li>
                                <li class="password-rule" data-password-rule="number">Al menos un numero</li>
                                <li class="password-rule" data-password-rule="special">Al menos un caracter especial</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning mt-4 mb-0"><i class="bi bi-envelope-check-fill me-2"></i>La cuenta se activara cuando confirme el enlace enviado a su correo.</div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary py-2">
                        <i class="bi bi-person-plus-fill me-2"></i>Crear cuenta y enviar verificacion
                    </button>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">&iquest;Ya tiene cuenta? <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="text-decoration-none fw-500">Iniciar sesion</a></small>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>



