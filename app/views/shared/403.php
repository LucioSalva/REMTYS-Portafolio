<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado — REMTYS</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body style="background:var(--color-fondo);">
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center p-4">
        <div class="mb-3" style="font-size:5rem;color:var(--color-peligro);"><i class="bi bi-shield-x"></i></div>
        <h1 class="display-4 fw-700" style="color:var(--color-peligro);">403</h1>
        <h4 class="mb-3">Acceso Denegado</h4>
        <p class="text-muted mb-4">No tiene permisos para acceder a esta sección.</p>
        <a href="<?= BASE_URL ?>/?modulo=home&accion=index" class="btn btn-primary">
            <i class="bi bi-house me-2"></i>Ir al Inicio
        </a>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
