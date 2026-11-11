<?php
/**
 * Helper de carga segura de archivos
 */

define('MIME_MAP', [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
]);

function subirDocumento(array $archivo, int $contribuyenteId, string $extensionesPermitidas): array {
    $resultado = ['exito' => false, 'error' => null, 'ruta' => null, 'nombre' => null, 'mime' => null, 'hash' => null, 'bytes' => 0];

    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        $codigos = [1=>'El archivo excede el límite del servidor.',2=>'El archivo excede el límite del formulario.',
                    3=>'El archivo solo fue parcialmente cargado.',4=>'No se seleccionó archivo.',6=>'Falta carpeta temporal.',
                    7=>'No se pudo escribir el archivo en disco.',8=>'Extensión PHP bloqueó la carga.'];
        $resultado['error'] = $codigos[$archivo['error']] ?? 'Error desconocido al cargar el archivo.';
        return $resultado;
    }

    $maxBytes = MAX_UPLOAD_BYTES;
    if ($archivo['size'] > $maxBytes) {
        $resultado['error'] = 'El archivo excede el tamaño máximo permitido (' . round($maxBytes / 1048576, 0) . ' MB).';
        return $resultado;
    }

    $extPermitidas = array_map('trim', explode(',', strtolower($extensionesPermitidas)));
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $extPermitidas, true)) {
        $resultado['error'] = 'Extensión no permitida. Solo: ' . implode(', ', $extPermitidas);
        return $resultado;
    }

    // Validar MIME real — finfo es obligatorio; rechazar si no está disponible
    if (!function_exists('finfo_open')) {
        logError('FINFO_NO_DISPONIBLE');
        $resultado['error'] = 'El servidor no soporta validación de tipo de archivo. Contacte al administrador.';
        return $resultado;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    $mimesPermitidos = array_filter(array_map(fn($e) => MIME_MAP[$e] ?? null, $extPermitidas));
    if (!in_array($mimeReal, $mimesPermitidos, true)) {
        logSeguridad('UPLOAD_MIME_RECHAZADO', ['contribuyente_id' => $contribuyenteId, 'mime_detectado' => $mimeReal, 'extension' => $ext]);
        $resultado['error'] = 'El tipo real del archivo no coincide con la extensión declarada.';
        return $resultado;
    }
    $resultado['mime'] = $mimeReal;

    // Nombre seguro único
    $nombreSeguro = 'doc_' . $contribuyenteId . '_' . uniqid('', true) . '.' . $ext;
    $dirDestino   = UPLOAD_PATH . DIRECTORY_SEPARATOR . $contribuyenteId;

    if (!is_dir($dirDestino)) {
        if (!mkdir($dirDestino, 0750, true)) {
            $resultado['error'] = 'No se pudo crear el directorio de almacenamiento.';
            return $resultado;
        }
    }

    $rutaCompleta = $dirDestino . DIRECTORY_SEPARATOR . $nombreSeguro;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $resultado['error'] = 'No se pudo mover el archivo al servidor.';
        return $resultado;
    }

    $resultado['exito']  = true;
    $resultado['ruta']   = 'uploads/' . $contribuyenteId . '/' . $nombreSeguro;
    $resultado['nombre'] = $nombreSeguro;
    $resultado['hash']   = hash_file('sha256', $rutaCompleta);
    $resultado['bytes']  = $archivo['size'];
    $resultado['mime']   = $resultado['mime'] ?? mime_content_type($rutaCompleta);

    logDocumento('DOCUMENTO_SUBIDO', ['contribuyente_id' => $contribuyenteId, 'archivo' => $nombreSeguro, 'mime' => $resultado['mime'], 'bytes' => $resultado['bytes']]);

    return $resultado;
}
