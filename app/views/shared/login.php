<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion - REMTYS</title>
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
        <a href="<?= BASE_URL ?>" class="auth-back-link"><i class="bi bi-arrow-left"></i> Volver al inicio</a>

        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-building-fill"></i></div>
            <h1>REMTYS</h1>
            <p>Sistema de Gestion Predial y Fiscal Municipal</p>
        </div>

        <div class="auth-note auth-note--subtle mb-4">
            <div class="auth-note-icon"><i class="bi bi-envelope-check"></i></div>
            <div>
                <strong>Si acaba de registrarse, active primero su cuenta.</strong>
                <p>Revise su correo y confirme el enlace antes de intentar ingresar al portal.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= s($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php mostrarFlash(); ?>

        <?php if (!empty($correoPendienteVerificacion)): ?>
        <div class="verification-panel mb-4">
            <div class="verification-panel-icon">
                <i class="bi bi-envelope-check-fill"></i>
            </div>
            <div>
                <h2 class="verification-panel-title">Verifique su correo antes de ingresar</h2>
                <p class="verification-panel-text mb-2"><?= s($mensajePendiente ?? 'Su cuenta aun esta pendiente de verificacion.') ?></p>
                <p class="verification-panel-text mb-3">Cuenta pendiente: <strong><?= s($correoPendienteVerificacion) ?></strong></p>
                <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=reenviar_verificacion" class="verification-panel-form">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="email" value="<?= s($correoPendienteVerificacion) ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-send-check me-2"></i>Reenviar correo de verificacion
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=procesar_login" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electronico <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="correo@ejemplo.com"
                           value="<?= s($prefillEmail ?? ($_POST['email'] ?? '')) ?>"
                           required autofocus autocomplete="email">
                    <div class="invalid-feedback">Ingrese un correo valido.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contrasena <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Ingrese su contrasena" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="password">
                        <i class="bi bi-eye"></i>
                    </button>
                    <div class="invalid-feedback">La contrasena es requerida.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesion
            </button>

            <div class="text-center mb-2">
                <a href="<?= BASE_URL ?>/?modulo=auth&accion=recuperar_password" class="text-decoration-none fw-500" style="color:#691C32;">
                    <i class="bi bi-question-circle me-1"></i>&iquest;Olvido su contrasena?
                </a>
            </div>

            <div class="text-center">
                <small class="text-muted">&iquest;No tiene cuenta?
                    <a href="<?= BASE_URL ?>/?modulo=auth&accion=registro" class="text-decoration-none fw-500">Registrarse</a>
                </small>
            </div>
        </form>

    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>

