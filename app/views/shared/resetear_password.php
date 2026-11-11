<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definir nueva contrasena - REMTYS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-card">
        <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="auth-back-link"><i class="bi bi-arrow-left"></i> Cancelar</a>

        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h1>Nueva contrasena</h1>
            <p>Defina una contrasena segura para su cuenta</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= s($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php mostrarFlash(); ?>

        <div class="auth-note auth-note--subtle mb-4">
            <div class="auth-note-icon"><i class="bi bi-info-circle"></i></div>
            <div>
                <strong>Requisitos de contrasena</strong>
                <p class="mb-0">Minimo 8 caracteres, debe incluir mayuscula, minuscula, numero y caracter especial.</p>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=procesar_reset" class="needs-validation" novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="token" value="<?= s($token ?? '') ?>">

            <div class="mb-3">
                <label for="password" class="form-label">Nueva contrasena <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Ingrese la nueva contrasena" required
                           minlength="8" maxlength="128" autocomplete="new-password" autofocus>
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="password">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">La contrasena debe tener al menos 8 caracteres.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="password_confirm" class="form-label">Confirmar nueva contrasena <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                           placeholder="Repita la contrasena" required
                           minlength="8" maxlength="128" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="password_confirm">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">Repita la misma contrasena para confirmar.</div>
                </div>
            </div>

            <button type="submit" class="btn-custom btn-custom-vino w-100 py-2 mb-3">
                <i class="bi bi-check2-circle me-2"></i>Actualizar contrasena
            </button>

            <div class="text-center">
                <small class="text-muted">
                    <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="text-decoration-none fw-500">
                        Cancelar y regresar al inicio de sesion
                    </a>
                </small>
            </div>
        </form>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
