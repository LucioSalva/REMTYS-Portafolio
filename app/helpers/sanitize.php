<?php
/**
 * Sanitizacion y Validacion de entradas
 */

function s(mixed $valor): string {
    if ($valor === null) return '';
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizarTexto(?string $valor): string {
    return trim(strip_tags($valor ?? ''));
}

function sanitizarEntero(mixed $valor): ?int {
    $v = filter_var($valor, FILTER_VALIDATE_INT);
    return $v !== false ? (int)$v : null;
}

function sanitizarDecimal(mixed $valor): ?float {
    $v = filter_var(str_replace(',', '.', (string)$valor), FILTER_VALIDATE_FLOAT);
    return $v !== false ? (float)$v : null;
}

function sanitizarEmail(?string $valor): ?string {
    $v = filter_var(trim($valor ?? ''), FILTER_VALIDATE_EMAIL);
    return $v !== false ? strtolower($v) : null;
}

function sanitizarRFC(?string $rfc): string {
    return strtoupper(preg_replace('/[^A-Z0-9&Ñ]/i', '', trim($rfc ?? '')));
}

function sanitizarCURP(?string $curp): string {
    return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($curp ?? '')));
}

function validarRFC(string $rfc): bool {
    return (bool)preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', strtoupper($rfc));
}

function validarCURP(string $curp): bool {
    return (bool)preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z][0-9]$/', strtoupper($curp));
}

function validarFecha(?string $fecha): bool {
    if (empty($fecha)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

function validarCP(?string $cp): bool {
    return (bool)preg_match('/^[0-9]{5}$/', trim($cp ?? ''));
}

function validarLongitudTexto(string $valor, int $min, int $max): bool {
    $len = mb_strlen(trim($valor), 'UTF-8');
    return $len >= $min && $len <= $max;
}

function validarPasswordSegura(string $password): array {
    $errores = [];

    if (strlen($password) < 8) {
        $errores[] = 'La contrasena debe tener al menos 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = 'La contrasena debe incluir al menos una letra mayuscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = 'La contrasena debe incluir al menos una letra minuscula.';
    }
    if (!preg_match('/\d/', $password)) {
        $errores[] = 'La contrasena debe incluir al menos un numero.';
    }
    if (!preg_match('/[^A-Za-z\d]/', $password)) {
        $errores[] = 'La contrasena debe incluir al menos un caracter especial.';
    }

    return $errores;
}

function estaVacio(mixed $valor): bool {
    return $valor === null || $valor === '' || (is_string($valor) && trim($valor) === '');
}

/**
 * Valida archivos subidos - extension y MIME real.
 */
function validarArchivoSubido(array $archivo, array $extensionesPermitidas, array $mimesPermitidos, int $maxBytesTamano): array {
    $errores = [];

    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Error al subir el archivo (codigo: ' . ($archivo['error'] ?? 'desconocido') . ').';
        return $errores;
    }

    if ($archivo['size'] > $maxBytesTamano) {
        $errores[] = 'El archivo excede el tamano maximo permitido (' . round($maxBytesTamano / 1048576, 1) . ' MB).';
    }

    $nombreOriginal = $archivo['name'] ?? '';
    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensionesPermitidas, true)) {
        $errores[] = 'Extension de archivo no permitida. Permitidas: ' . implode(', ', $extensionesPermitidas);
    }

    if (!function_exists('finfo_open')) {
        $errores[] = 'El servidor no soporta validación de tipo de archivo.';
        return $errores;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeReal, $mimesPermitidos, true)) {
        $errores[] = 'Tipo de archivo no permitido (MIME: ' . $mimeReal . ').';
    }

    return $errores;
}

function generarNombreArchivoSeguro(string $nombreOriginal): string {
    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    return uniqid('doc_', true) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
}
