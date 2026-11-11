<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contrasena - REMTYS</title>
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
        <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="auth-back-link"><i class="bi bi-arrow-left"></i> Volver al inicio de sesion</a>

        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-key-fill"></i></div>
            <h1>Recuperar contrasena</h1>
            <p>Le enviaremos un enlace seguro a su correo registrado</p>
        </div>

        <div class="auth-note auth-note--subtle mb-4">
            <div class="auth-note-icon"><i class="bi bi-shield-lock"></i></div>
            <div>
                <strong>Proceso seguro y verificable.</strong>
                <p>Si la cuenta existe y esta activa, recibira un correo con un enlace que vence en 1 hora.</p>
            </div>
        </div>

        <?php mostrarFlash(); ?>

        <form method="POST" action="<?= BASE_URL ?>/?modulo=auth&accion=procesar_recuperacion" class="needs-validation" novalidate>
            <?= campoCSRF() ?>

            <div class="mb-4">
                <label for="email" class="form-label">Correo electronico registrado <span class="required">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="correo@ejemplo.com"
                           value="<?= s($_POST['email'] ?? '') ?>"
                           required autofocus autocomplete="email" maxlength="120">
                    <div class="invalid-feedback">Ingrese un correo valido.</div>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-info-circle me-1"></i>Ingrese el mismo correo con el que se registro en REMTYS.
                </small>
            </div>

            <button type="submit" class="btn-custom btn-custom-vino w-100 py-2 mb-3">
                <i class="bi bi-send-check me-2"></i>Enviar enlace de recuperacion
            </button>

            <div class="text-center">
                <small class="text-muted">
                    <a href="<?= BASE_URL ?>/?modulo=auth&accion=login" class="text-decoration-none fw-500">
                        Regresar a iniciar sesion
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
