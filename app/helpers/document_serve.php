<?php
/**
 * Helper: servido seguro de archivos de documentos.
 *
 * Centraliza la entrega de archivos subidos (PDF/JPG/PNG) al navegador
 * aplicando defensas en profundidad:
 *   - Validacion de ruta contra path traversal (realpath + prefijo UPLOAD_PATH).
 *   - Existencia fisica en disco antes de cualquier header binario.
 *   - Whitelist de MIME (rechaza cualquier tipo fuera de PDF/JPG/PNG),
 *     con doble verificacion via finfo si el valor almacenado no coincide.
 *   - Reemplazo de los headers HTML/CSP que ya seteo index.php por los
 *     headers binarios correctos.
 *   - Sanitizacion del nombre original para Content-Disposition.
 *
 * El caller es responsable de:
 *   - Autenticar al usuario (requireAdminORevisor / requireContribuyente).
 *   - Consultar el documento desde la BD con prepared statement.
 *   - Validar la ownership cuando aplique (contribuyente solo ve sus docs).
 *
 * Luego invoca servirDocumento($doc, $disposition, $contextoLog).
 */

/**
 * MIMEs permitidos para servido (whitelist estricta).
 * Mapeo mime -> extensiones aceptadas para validacion cruzada.
 */
function mimesPermitidosServido(): array {
    return [
        'application/pdf' => ['pdf'],
        'image/jpeg'      => ['jpg', 'jpeg'],
        'image/png'       => ['png'],
    ];
}

/**
 * Sanitiza el nombre original del archivo para usarlo en
 * Content-Disposition. Elimina comillas, saltos de linea,
 * caracteres de control y limita longitud.
 */
function sanitizarNombreDescarga(string $nombre): string {
    // Elimina comillas simples/dobles, backslashes, saltos de linea y control chars
    $limpio = preg_replace('/[\x00-\x1F\x7F"\\\\\r\n]/u', '', $nombre);
    $limpio = trim((string)$limpio);
    if ($limpio === '') {
        $limpio = 'documento';
    }
    // Limite razonable para evitar headers gigantes
    if (strlen($limpio) > 200) {
        $limpio = substr($limpio, 0, 200);
    }
    return $limpio;
}

/**
 * Renderiza un 404 institucional controlado con un mensaje personalizado
 * y termina la ejecucion. Usado cuando el archivo esta registrado en BD
 * pero falta fisicamente o cualquier otro fallo de acceso.
 */
function render404Documento(string $mensaje = ''): void {
    http_response_code(404);
    // El 404 compartido usa un layout propio (sin layout admin/contrib),
    // asi que lo cargamos tal cual. El mensaje custom queda en un global
    // opcional si la vista quisiera leerlo.
    $mensaje404 = $mensaje;
    require APP_PATH . '/views/shared/404.php';
    exit;
}

/**
 * Sirve un documento al navegador aplicando todas las defensas.
 *
 * @param array  $doc          Fila de la tabla documentos (minimo:
 *                             id, contribuyente_id, nombre_original,
 *                             nombre_archivo, ruta_archivo, mime_type).
 * @param string $disposition  'inline' (preview) o 'attachment' (descarga).
 * @param string $contextoLog  Etiqueta del contexto para el audit log
 *                             (p.ej. 'DOC_VISTO_ADMIN', 'DOC_VISTO_CONTRIB').
 */
function servirDocumento(array $doc, string $disposition, string $contextoLog): void {
    // ——— Normalizacion del disposition ———
    $disposition = ($disposition === 'attachment') ? 'attachment' : 'inline';

    // ——— Construccion segura de la ruta absoluta ———
    $rutaRelativa = (string)($doc['ruta_archivo'] ?? '');
    if ($rutaRelativa === '') {
        logSeguridad('DOC_RUTA_VACIA', ['documento_id' => $doc['id'] ?? null]);
        render404Documento('El documento no tiene una ruta de archivo registrada.');
    }

    // La ruta_archivo en BD es relativa al STORAGE_PATH, estilo "uploads/{cid}/doc_xxx.pdf"
    $rutaCandidata = STORAGE_PATH . '/' . ltrim($rutaRelativa, '/\\');
    $absPath       = realpath($rutaCandidata);
    $uploadBase    = realpath(UPLOAD_PATH);

    // ——— Defensa contra path traversal ———
    if ($absPath === false || $uploadBase === false) {
        logSeguridad('DOC_RUTA_INVALIDA', [
            'documento_id' => $doc['id'] ?? null,
            'ruta'         => $rutaRelativa,
        ]);
        render404Documento('El archivo del documento no esta disponible en el servidor.');
    }
    // Normalizamos separadores para que str_starts_with funcione en Windows/Linux
    $absPathNorm    = str_replace('\\', '/', $absPath);
    $uploadBaseNorm = str_replace('\\', '/', $uploadBase);
    if (!str_starts_with($absPathNorm, $uploadBaseNorm)) {
        logSeguridad('DOC_PATH_TRAVERSAL_BLOQUEADO', [
            'documento_id' => $doc['id'] ?? null,
            'ruta'         => $rutaRelativa,
            'resolved'     => $absPathNorm,
        ]);
        render404Documento('El archivo del documento no esta disponible en el servidor.');
    }

    // ——— Existencia fisica ———
    if (!is_file($absPath)) {
        logSeguridad('DOC_ARCHIVO_FALTANTE', [
            'documento_id' => $doc['id'] ?? null,
            'ruta'         => $rutaRelativa,
        ]);
        render404Documento('El archivo del documento ya no esta disponible en el servidor. Contacte al administrador del sistema.');
    }

    // ——— Whitelist de MIME ———
    $whitelist   = mimesPermitidosServido();
    $mimeBD      = strtolower((string)($doc['mime_type'] ?? ''));
    $mimeFinal   = null;

    if (isset($whitelist[$mimeBD])) {
        $mimeFinal = $mimeBD;
    } else {
        // Doble verificacion con finfo cuando la BD no trae un MIME valido
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeReal = finfo_file($finfo, $absPath);
                finfo_close($finfo);
                if (is_string($mimeReal) && isset($whitelist[strtolower($mimeReal)])) {
                    $mimeFinal = strtolower($mimeReal);
                }
            }
        }
    }

    if ($mimeFinal === null) {
        logSeguridad('DOC_MIME_NO_PERMITIDO', [
            'documento_id' => $doc['id'] ?? null,
            'mime_bd'      => $mimeBD,
        ]);
        render404Documento('El tipo de archivo del documento no puede ser servido por razones de seguridad.');
    }

    // ——— Validacion cruzada extension vs MIME ———
    $extArchivo = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    if (!in_array($extArchivo, $whitelist[$mimeFinal], true)) {
        logSeguridad('DOC_MIME_EXT_MISMATCH', [
            'documento_id' => $doc['id'] ?? null,
            'mime'         => $mimeFinal,
            'ext'          => $extArchivo,
        ]);
        render404Documento('El tipo de archivo del documento no puede ser servido por razones de seguridad.');
    }

    // ——— Audit log del acceso ———
    logDocumento($contextoLog, [
        'documento_id'    => $doc['id'] ?? null,
        'contribuyente_id'=> $doc['contribuyente_id'] ?? null,
        'disposition'     => $disposition,
        'mime'            => $mimeFinal,
    ]);

    // ——— Reemplazo de headers HTML por headers binarios ———
    // index.php ya seteo Content-Type: text/html, CSP estricto, X-Frame-Options: DENY,
    // Cache-Control: no-store. Hay que removerlos antes de servir el binario.
    header_remove('Content-Type');
    header_remove('Content-Security-Policy');
    header_remove('Pragma');
    header_remove('Cache-Control');
    header_remove('Expires');
    header_remove('X-Frame-Options');

    // Para permitir que el iframe del modal (mismo origen) cargue el PDF,
    // relajamos el X-Frame-Options a SAMEORIGIN (sobreescribe el DENY global).
    header('X-Frame-Options: SAMEORIGIN');

    $nombreDescarga = sanitizarNombreDescarga((string)($doc['nombre_original'] ?? $doc['nombre_archivo'] ?? 'documento'));

    header('Content-Type: ' . $mimeFinal);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, no-store, must-revalidate');
    header('Content-Length: ' . filesize($absPath));
    header('Content-Disposition: ' . $disposition . '; filename="' . $nombreDescarga . '"');

    // Limpieza de cualquier buffer previo para no corromper el binario
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    readfile($absPath);
    exit;
}
