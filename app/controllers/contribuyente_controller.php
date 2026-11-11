<?php
/**
 * Controlador del Portal del Contribuyente
 */

require_once APP_PATH . '/services/RequisitosService.php';
require_once APP_PATH . '/services/DocumentoService.php';
require_once APP_PATH . '/services/ExpedienteService.php';
require_once APP_PATH . '/services/FiscalService.php';
require_once APP_PATH . '/services/AuditService.php';
require_once APP_PATH . '/services/FolioService.php';
require_once APP_PATH . '/services/HistorialExpedienteService.php';
require_once APP_PATH . '/helpers/FileUpload.php';

// ——— Helper: render con layout contribuyente ———
function renderContrib(string $vista, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/contribuyente/' . $vista . '.php';
    $contenido = ob_get_clean();
    require APP_PATH . '/views/layouts/layout_contribuyente.php';
}

// ——— Helper: obtener códigos postales del catálogo municipal ———
function obtenerCodigosPostalesMunicipio(PDO $db): array {
    $stmt = $db->query('SELECT DISTINCT codigo_postal FROM catalogo_cp_colonia WHERE activo = TRUE ORDER BY codigo_postal');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ——— AJAX: colonias por código postal ———
function accion_colonias_por_cp(): void {
    requireLogin();
    $cp = sanitizarTexto($_GET['cp'] ?? '');
    if (!preg_match('/^\d{5}$/', $cp)) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    $db = getDBConnection();
    $stmt = $db->prepare('SELECT colonia FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND activo = TRUE ORDER BY colonia');
    $stmt->execute([':cp' => $cp]);
    $colonias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($colonias);
    exit;
}

// ——— Dashboard ———
function accion_dashboard(): void {
    requireContribuyente();
    $db = getDBConnection();
    $cid = getContribuyenteId();

    $stmtPredios = $db->prepare('SELECT COUNT(*) FROM predio_contribuyente WHERE contribuyente_id = :cid AND activo = TRUE');
    $stmtPredios->execute([':cid' => $cid]);
    $totalPredios = (int)$stmtPredios->fetchColumn();

    $stmtExps = $db->prepare('SELECT COUNT(*) FROM expedientes WHERE contribuyente_id = :cid');
    $stmtExps->execute([':cid' => $cid]);
    $totalExpedientes = (int)$stmtExps->fetchColumn();

    $stmtTrams = $db->prepare('SELECT COUNT(*) FROM tramites t JOIN expedientes e ON e.id = t.expediente_id WHERE e.contribuyente_id = :cid AND t.estado IN (\'iniciado\',\'en_revision\',\'documentacion_pendiente\')');
    $stmtTrams->execute([':cid' => $cid]);
    $tramitesPendientes = (int)$stmtTrams->fetchColumn();

    $stmtDocs = $db->prepare('SELECT COUNT(*) FROM documentos WHERE contribuyente_id = :cid AND es_version_activa = TRUE');
    $stmtDocs->execute([':cid' => $cid]);
    $totalDocumentos = (int)$stmtDocs->fetchColumn();

    $fiscal = new FiscalService($db);
    $saldo = $fiscal->getSaldoContribuyente($cid);

    $stmtExpRecientes = $db->prepare('
        SELECT e.id, e.numero_expediente, e.estado, e.fecha_apertura, p.clave_catastral,
               COUNT(t.id) AS tramites
        FROM expedientes e
        LEFT JOIN predios p ON p.id = e.predio_id
        LEFT JOIN tramites t ON t.expediente_id = e.id
        WHERE e.contribuyente_id = :cid
        GROUP BY e.id, e.numero_expediente, e.estado, e.fecha_apertura, p.clave_catastral
        ORDER BY e.fecha_apertura DESC LIMIT 5
    ');
    $stmtExpRecientes->execute([':cid' => $cid]);
    $expedientesRecientes = $stmtExpRecientes->fetchAll(PDO::FETCH_ASSOC);

    // Mapa centralizado de navegación para tarjetas del dashboard
    $cardLinks = [
        'predios'     => BASE_URL . '/?modulo=contribuyente&accion=predios',
        'expedientes' => BASE_URL . '/?modulo=contribuyente&accion=expedientes',
        'tramites'    => BASE_URL . '/?modulo=contribuyente&accion=tramites',
        'documentos'  => BASE_URL . '/?modulo=contribuyente&accion=documentos',
    ];

    renderContrib('dashboard', [
        'titulo'              => 'Mi Panel',
        'accion_activa'       => 'dashboard',
        'totalPredios'        => $totalPredios,
        'totalExpedientes'    => $totalExpedientes,
        'tramitesPendientes'  => $tramitesPendientes,
        'totalDocumentos'     => $totalDocumentos,
        'saldo'               => $saldo,
        'expedientesRecientes'=> $expedientesRecientes,
        'cardLinks'           => $cardLinks,
    ]);
}

// ——— Perfil ———
function accion_perfil(): void {
    requireContribuyente();
    $db = getDBConnection();
    $cid = getContribuyenteId();

    $stmt = $db->prepare('SELECT c.*, u.email FROM v_contribuyentes_detalle c JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = :cid');
    $stmt->execute([':cid' => $cid]);
    $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contribuyente) {
        setFlash('error', 'No fue posible cargar la informacion del contribuyente.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=dashboard');
        exit;
    }

    renderContrib('perfil', ['titulo' => 'Mi Perfil', 'accion_activa' => 'perfil', 'contribuyente' => $contribuyente]);
}

function accion_editar_perfil(): void {
    requireContribuyente();
    verificarCSRF();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=perfil');
        exit;
    }

    $db  = getDBConnection();
    $cid = getContribuyenteId();

    $stmtActual = $db->prepare('SELECT * FROM v_contribuyentes_detalle WHERE id = :cid LIMIT 1');
    $stmtActual->execute([':cid' => $cid]);
    $contribuyenteActual = $stmtActual->fetch(PDO::FETCH_ASSOC);

    if (!$contribuyenteActual) {
        setFlash('error', 'No fue posible localizar su perfil.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=dashboard');
        exit;
    }

    $campos = [
        'nombre_completo' => sanitizarTexto($_POST['nombre_completo'] ?? ''),
        'telefono'        => sanitizarTexto($_POST['telefono'] ?? ''),
        'email_contacto'  => sanitizarEmail($_POST['email_contacto'] ?? ''),
        'calle'           => sanitizarTexto($_POST['calle'] ?? ''),
        'numero_exterior' => sanitizarTexto($_POST['numero_exterior'] ?? ''),
        'colonia'         => sanitizarTexto($_POST['colonia'] ?? ''),
        'municipio'       => sanitizarTexto($_POST['municipio'] ?? ''),
        'estado'          => sanitizarTexto($_POST['estado'] ?? ''),
        'codigo_postal'   => sanitizarTexto($_POST['codigo_postal'] ?? ''),
    ];
    $detalleContribuyente = normalizarDetalleContribuyente($_POST);
    $tipoPersona = $contribuyenteActual['tipo_persona'] ?? 'fisica';
    $errores = [];

    if (estaVacio($campos['nombre_completo']) || !validarLongitudTexto($campos['nombre_completo'], 3, 400)) {
        $errores[] = 'El nombre completo es requerido.';
    }
    if (trim((string)($_POST['email_contacto'] ?? '')) !== '' && !$campos['email_contacto']) {
        $errores[] = 'El correo de contacto no es valido.';
    }
    if ($campos['codigo_postal'] !== '' && !validarCP($campos['codigo_postal'])) {
        $errores[] = 'El codigo postal debe tener 5 digitos.';
    }
    $errores = array_merge($errores, validarDetalleContribuyente($tipoPersona, $detalleContribuyente));

    if (!empty($errores)) {
        setFlash('error', implode(' ', $errores));
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=perfil');
        exit;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('
            UPDATE contribuyentes
            SET nombre_completo = :nombre,
                telefono = :tel,
                email_contacto = :email,
                calle = :calle,
                numero_exterior = :nex,
                colonia = :col,
                municipio = :mun,
                estado = :est,
                codigo_postal = :cp
            WHERE id = :cid
        ');
        $stmt->execute([
            ':nombre' => $campos['nombre_completo'],
            ':tel' => $campos['telefono'] ?: null,
            ':email' => $campos['email_contacto'] ?: null,
            ':calle' => $campos['calle'] ?: null,
            ':nex' => $campos['numero_exterior'] ?: null,
            ':col' => $campos['colonia'] ?: null,
            ':mun' => $campos['municipio'] ?: null,
            ':est' => $campos['estado'] ?: null,
            ':cp' => $campos['codigo_postal'] ?: null,
            ':cid' => $cid,
        ]);

        guardarDetalleContribuyente($db, $cid, $tipoPersona, $detalleContribuyente);

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Error al actualizar perfil de contribuyente: ' . $e->getMessage());
        setFlash('error', 'No fue posible actualizar su perfil. Intente nuevamente.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=perfil');
        exit;
    }

    setFlash('exito', 'Perfil actualizado correctamente.');
    header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=perfil');
    exit;
}

// â€”â€”â€” Predios â€”â€”â€”
function accion_predios(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();

    $stmt = $db->prepare('
        SELECT p.*, pc.fecha_inicio, pc.tipo_relacion
        FROM predios p
        JOIN predio_contribuyente pc ON pc.predio_id = p.id
        WHERE pc.contribuyente_id = :cid AND pc.activo = TRUE
        ORDER BY p.created_at DESC
    ');
    $stmt->execute([':cid' => $cid]);
    $predios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    renderContrib('predios', ['titulo' => 'Mis Predios', 'accion_activa' => 'predios', 'predios' => $predios]);
}

function accion_nuevo_predio(): void {
    requireContribuyente();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        _guardarPredio(null);
        return;
    }

    $db = getDBConnection();
    $codigosPostales = obtenerCodigosPostalesMunicipio($db);

    renderContrib('predio_form', [
        'titulo'       => 'Registrar Nuevo Predio',
        'accion_activa'=> 'predios',
        'predio'       => null,
        'accion_form'  => BASE_URL . '/?modulo=contribuyente&accion=nuevo_predio',
        'codigos_postales' => $codigosPostales,
    ]);
}

function accion_editar_predio(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $pid = sanitizarEntero($_GET['id'] ?? null);

    if (!$pid) {
        setFlash('error', 'Predio no válido.'); header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=predios'); exit;
    }

    // Verificar pertenencia
    $stmtCheck = $db->prepare('SELECT 1 FROM predio_contribuyente WHERE predio_id = :pid AND contribuyente_id = :cid AND activo = TRUE');
    $stmtCheck->execute([':pid' => $pid, ':cid' => $cid]);
    if (!$stmtCheck->fetchColumn()) {
        setFlash('error', 'No tiene acceso a este predio.'); header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=predios'); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        _guardarPredio($pid);
        return;
    }

    $stmtP = $db->prepare('SELECT * FROM predios WHERE id = :pid');
    $stmtP->execute([':pid' => $pid]);
    $predio = $stmtP->fetch(PDO::FETCH_ASSOC);

    $codigosPostales = obtenerCodigosPostalesMunicipio($db);

    renderContrib('predio_form', [
        'titulo'       => 'Editar Predio',
        'accion_activa'=> 'predios',
        'predio'       => $predio,
        'accion_form'  => BASE_URL . '/?modulo=contribuyente&accion=editar_predio&id=' . $pid,
        'codigos_postales' => $codigosPostales,
    ]);
}

function _guardarPredio(?int $predioId): void {
    $db  = getDBConnection();
    $cid = getContribuyenteId();

    $clave_catastral    = sanitizarTexto($_POST['clave_catastral'] ?? '');
    $cuenta_predial     = sanitizarTexto($_POST['cuenta_predial'] ?? '');
    $nombre_descripcion = sanitizarTexto($_POST['nombre_descripcion'] ?? '');
    $uso_suelo          = sanitizarTexto($_POST['uso_suelo'] ?? '');
    $superficie_total   = sanitizarDecimal($_POST['superficie_total'] ?? null);
    $valor_catastral    = sanitizarDecimal($_POST['valor_catastral'] ?? null);
    $calle              = sanitizarTexto($_POST['calle'] ?? '');
    $numero_exterior    = sanitizarTexto($_POST['numero_exterior'] ?? '');
    $colonia            = sanitizarTexto($_POST['colonia'] ?? '');
    $municipio          = APP_MUNICIPIO;
    $estado             = 'Estado de México';
    $codigo_postal      = sanitizarTexto($_POST['codigo_postal'] ?? '');

    if (estaVacio($clave_catastral) || estaVacio($cuenta_predial)) {
        setFlash('error', 'La clave catastral y cuenta predial son requeridas.');
        $url = $predioId
            ? BASE_URL . '/?modulo=contribuyente&accion=editar_predio&id=' . $predioId
            : BASE_URL . '/?modulo=contribuyente&accion=nuevo_predio';
        header('Location: ' . $url); exit;
    }

    // Validar que el código postal pertenece al catálogo municipal
    if (!estaVacio($codigo_postal)) {
        $stmtCP = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND activo = TRUE');
        $stmtCP->execute([':cp' => $codigo_postal]);
        if ((int)$stmtCP->fetchColumn() === 0) {
            setFlash('error', 'El código postal no pertenece al catálogo del municipio.');
            $url = $predioId
                ? BASE_URL . '/?modulo=contribuyente&accion=editar_predio&id=' . $predioId
                : BASE_URL . '/?modulo=contribuyente&accion=nuevo_predio';
            header('Location: ' . $url); exit;
        }
    }

    // Validar que la colonia corresponde al código postal seleccionado
    if (!estaVacio($colonia) && !estaVacio($codigo_postal)) {
        $stmtCol = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND colonia = :col AND activo = TRUE');
        $stmtCol->execute([':cp' => $codigo_postal, ':col' => $colonia]);
        if ((int)$stmtCol->fetchColumn() === 0) {
            setFlash('error', 'La colonia no corresponde al código postal seleccionado.');
            $url = $predioId
                ? BASE_URL . '/?modulo=contribuyente&accion=editar_predio&id=' . $predioId
                : BASE_URL . '/?modulo=contribuyente&accion=nuevo_predio';
            header('Location: ' . $url); exit;
        }
    }

    if ($predioId) {
        $stmt = $db->prepare('
            UPDATE predios SET clave_catastral=:ck, cuenta_predial=:cp, nombre_descripcion=:nd, uso_suelo=:us,
            superficie_total=:st, valor_catastral=:vc, calle=:cl, numero_exterior=:ne, colonia=:col,
            municipio=:mun, estado=:est, codigo_postal=:cpost WHERE id = :pid
        ');
        $stmt->execute([':ck'=>$clave_catastral,':cp'=>$cuenta_predial,':nd'=>$nombre_descripcion,
            ':us'=>$uso_suelo,':st'=>$superficie_total,':vc'=>$valor_catastral,':cl'=>$calle,
            ':ne'=>$numero_exterior,':col'=>$colonia,':mun'=>$municipio,':est'=>$estado,':cpost'=>$codigo_postal,
            ':pid'=>$predioId]);
        setFlash('exito', 'Predio actualizado correctamente.');
    } else {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO predios (clave_catastral, cuenta_predial, nombre_descripcion, uso_suelo, superficie_total,
                valor_catastral, calle, numero_exterior, colonia, municipio, estado, codigo_postal)
                VALUES (:ck,:cp,:nd,:us,:st,:vc,:cl,:ne,:col,:mun,:est,:cpost)
                RETURNING id
            ');
            $stmt->execute([':ck'=>$clave_catastral,':cp'=>$cuenta_predial,':nd'=>$nombre_descripcion,
                ':us'=>$uso_suelo,':st'=>$superficie_total,':vc'=>$valor_catastral,':cl'=>$calle,
                ':ne'=>$numero_exterior,':col'=>$colonia,':mun'=>$municipio,':est'=>$estado,':cpost'=>$codigo_postal]);
            $predioId = (int)$stmt->fetchColumn();

            $stmtRel = $db->prepare('INSERT INTO predio_contribuyente (predio_id, contribuyente_id, tipo_relacion) VALUES (:pid,:cid,\'propietario\')');
            $stmtRel->execute([':pid' => $predioId, ':cid' => $cid]);

            $db->commit();
            setFlash('exito', 'Predio registrado correctamente.');
        } catch (Exception $e) {
            $db->rollBack();
            if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
                setFlash('error', 'La clave catastral o cuenta predial ya están registradas.');
            } else {
                error_log('Error nuevo_predio: ' . $e->getMessage());
                setFlash('error', 'Error al guardar el predio.');
            }
        }
    }

    header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=predios');
    exit;
}

// ——— Documentos ———
function accion_documentos(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $docService = new DocumentoService($db);
    $documentos = $docService->getDocumentosContribuyente($cid);

    renderContrib('documentos', ['titulo' => 'Mis Documentos', 'accion_activa' => 'documentos', 'documentos' => $documentos]);
}

/**
 * Sirve el archivo fisico de un documento propio del contribuyente.
 * Verifica ownership estricta: el documento debe pertenecer al
 * contribuyente autenticado en la sesion actual. Delega el servido
 * y las defensas al helper centralizado servirDocumento().
 *
 * Parametros GET:
 *   - id         : int   (requerido) id del documento
 *   - descargar  : '0'|'1' (opcional) 1 fuerza Content-Disposition: attachment
 */
function accion_ver_documento(): void {
    requireContribuyente();

    $id = sanitizarEntero($_GET['id'] ?? null);
    if (!$id) {
        render404Documento('Identificador de documento invalido.');
    }

    $db  = getDBConnection();
    $cid = getContribuyenteId();

    $stmt = $db->prepare('SELECT id, contribuyente_id, nombre_original, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256 FROM documentos WHERE id = :id AND contribuyente_id = :cid LIMIT 1');
    $stmt->execute([':id' => $id, ':cid' => $cid]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        // Podria ser: documento inexistente o documento de OTRO contribuyente.
        // En ambos casos devolvemos 403 y logueamos como acceso denegado
        // para poder detectar IDOR en el log de seguridad.
        logAccesoDenegado('DOC_ACCESO_DENEGADO', [
            'documento_id'    => $id,
            'contribuyente_id'=> $cid,
            'contexto'        => 'contribuyente',
        ]);
        http_response_code(403);
        require APP_PATH . '/views/shared/403.php';
        exit;
    }

    $disposition = (($_GET['descargar'] ?? '0') === '1') ? 'attachment' : 'inline';
    servirDocumento($doc, $disposition, 'DOC_VISTO_CONTRIB');
}

function accion_subir_documento(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();

    // ——————————————————————————————————————————————
    // Reconstrucción del contexto del requisito.
    // REGLA: $_GET es la FUENTE DE VERDAD porque sobrevive a cualquier fallo
    // previo al parseo de $_POST (post_max_size excedido, back/forward, etc.).
    // $_POST solo se usa como fallback secundario por si un cliente antiguo
    // no propaga el contexto en la URL del action.
    // ——————————————————————————————————————————————
    $ctxTramiteId    = sanitizarEntero($_GET['tramite_id'] ?? null)    ?? sanitizarEntero($_POST['tramite_id'] ?? null);
    $ctxTipoDocId    = sanitizarEntero($_GET['tipo_doc_id'] ?? null)   ?? sanitizarEntero($_POST['tipo_doc_forzado'] ?? null);
    $ctxExpedienteId = sanitizarEntero($_GET['expediente_id'] ?? null) ?? sanitizarEntero($_POST['expediente_id'] ?? null);
    $ctxPadreDocId   = sanitizarEntero($_GET['padre_doc_id'] ?? null)  ?? sanitizarEntero($_POST['padre_documento_id'] ?? null);

    // Alias usado por el resto del flujo y por la vista
    $tramiteId = $ctxTramiteId;

    // URL de redirect recuperable — preserva TODO el contexto del requisito
    $redirectParams = '';
    if ($ctxTramiteId)    $redirectParams .= '&tramite_id='    . $ctxTramiteId;
    if ($ctxExpedienteId) $redirectParams .= '&expediente_id=' . $ctxExpedienteId;
    if ($ctxTipoDocId)    $redirectParams .= '&tipo_doc_id='   . $ctxTipoDocId;
    if ($ctxPadreDocId)   $redirectParams .= '&padre_doc_id='  . $ctxPadreDocId;
    $redirectUrl = BASE_URL . '/?modulo=contribuyente&accion=subir_documento' . $redirectParams;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();

        // Rate limiting de uploads (recuperable: respeta el contexto del requisito)
        if (!isset($_SESSION['_upload_window']) || (time() - $_SESSION['_upload_window']) > 300) {
            $_SESSION['_upload_count'] = 0;
            $_SESSION['_upload_window'] = time();
        }
        $_SESSION['_upload_count'] = ($_SESSION['_upload_count'] ?? 0) + 1;
        if ($_SESSION['_upload_count'] > 20) {
            setFlash('error', 'Demasiados archivos subidos. Espere unos minutos.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $tipoDocId      = sanitizarEntero($_POST['tipo_documento_id'] ?? null);
        $tipoDocForzado = sanitizarEntero($_POST['tipo_doc_forzado'] ?? null);
        $padreDocId     = sanitizarEntero($_POST['padre_documento_id'] ?? null);
        $folio          = sanitizarTexto($_POST['numero_folio'] ?? '');
        $fechaEmision   = sanitizarTexto($_POST['fecha_emision'] ?? '');
        $fechaVig       = sanitizarTexto($_POST['fecha_vigencia'] ?? '');
        $expId          = sanitizarEntero($_POST['expediente_id'] ?? null);
        $tramId         = sanitizarEntero($_POST['tramite_id'] ?? null);

        // $_GET es la fuente de verdad del contexto: si viene tipo_doc_id en URL,
        // domina sobre cualquier cosa que el POST traiga (incluido tipo_doc_forzado).
        $tipoDocIdUrl = sanitizarEntero($_GET['tipo_doc_id'] ?? null);
        if ($tipoDocIdUrl) {
            if ($tipoDocForzado && $tipoDocForzado !== $tipoDocIdUrl) {
                error_log('[REMTYS] SECURITY: Discrepancia tipo_doc_id URL vs tipo_doc_forzado POST. User=' . ($_SESSION['usuario_id'] ?? 0) . ' URL=' . $tipoDocIdUrl . ' POST=' . $tipoDocForzado);
            }
            $tipoDocForzado = $tipoDocIdUrl;
        }

        // Idem para expediente_id, tramite_id y padre_doc_id: si vienen por URL, sobrescriben POST
        $expIdUrl   = sanitizarEntero($_GET['expediente_id'] ?? null);
        $tramIdUrl  = sanitizarEntero($_GET['tramite_id'] ?? null);
        $padreIdUrl = sanitizarEntero($_GET['padre_doc_id'] ?? null);
        if ($expIdUrl)   { $expId     = $expIdUrl; }
        if ($tramIdUrl)  { $tramId    = $tramIdUrl; }
        if ($padreIdUrl) { $padreDocId = $padreIdUrl; }

        // Si viene desde requisito específico, forzar el tipo de documento
        // ignorando cualquier manipulación del POST
        if ($tipoDocForzado) {
            if ($tipoDocId && $tipoDocId !== $tipoDocForzado) {
                error_log('[REMTYS] SECURITY: Intento de cambiar tipo_documento_id forzado. User=' . ($_SESSION['usuario_id'] ?? 0) . ' Esperado=' . $tipoDocForzado . ' Recibido=' . $tipoDocId);
            }
            $tipoDocId = $tipoDocForzado;
        }

        // Validar ownership del expediente y tramite
        // Ownership inválido = manipulación de IDs ajenos → redirect duro a documentos.
        if ($expId) {
            $stmtExpOwn = $db->prepare('SELECT 1 FROM expedientes WHERE id = :eid AND contribuyente_id = :cid');
            $stmtExpOwn->execute([':eid' => $expId, ':cid' => $cid]);
            if (!$stmtExpOwn->fetchColumn()) {
                setFlash('error', 'Expediente no válido.');
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=documentos');
                exit;
            }
        }
        if ($tramId) {
            $stmtTramOwn = $db->prepare('SELECT 1 FROM tramites t JOIN expedientes e ON e.id = t.expediente_id WHERE t.id = :tid AND e.contribuyente_id = :cid');
            $stmtTramOwn->execute([':tid' => $tramId, ':cid' => $cid]);
            if (!$stmtTramOwn->fetchColumn()) {
                setFlash('error', 'Trámite no válido.');
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=documentos');
                exit;
            }
        }

        // Validar fechas si se proporcionan
        if ($fechaEmision !== '' && !validarFecha($fechaEmision)) {
            setFlash('error', 'La fecha de emisión no tiene un formato válido (AAAA-MM-DD).');
            header('Location: ' . $redirectUrl);
            exit;
        }
        if ($fechaVig !== '' && !validarFecha($fechaVig)) {
            setFlash('error', 'La fecha de vigencia no tiene un formato válido (AAAA-MM-DD).');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (!$tipoDocId) {
            setFlash('error', 'Seleccione el tipo de documento.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Obtener tipo documento para extensiones permitidas
        $stmtTD = $db->prepare('SELECT * FROM tipos_documento WHERE id = :id AND activo = TRUE');
        $stmtTD->execute([':id' => $tipoDocId]);
        $tipoDoc = $stmtTD->fetch(PDO::FETCH_ASSOC);

        if (!$tipoDoc) {
            setFlash('error', 'Tipo de documento no válido.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Verificar ownership del documento padre si se provee
        if ($padreDocId) {
            $stmtOwnerDoc = $db->prepare('SELECT 1 FROM documentos WHERE id = :id AND contribuyente_id = :cid AND es_version_activa = TRUE');
            $stmtOwnerDoc->execute([':id' => $padreDocId, ':cid' => $cid]);
            if (!$stmtOwnerDoc->fetchColumn()) {
                setFlash('error', 'El documento padre no es válido.');
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=documentos');
                exit;
            }
        }

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            setFlash('error', 'Debe seleccionar un archivo.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $upload = subirDocumento($_FILES['archivo'], $cid, $tipoDoc['extensiones_permitidas']);

        if (!$upload['exito']) {
            setFlash('error', $upload['error']);
            header('Location: ' . $redirectUrl);
            exit;
        }

        $docService = new DocumentoService($db);
        try {
            $docId = $docService->registrarDocumento([
                'contribuyente_id'        => $cid,
                'tipo_documento_id'       => $tipoDocId,
                'padre_documento_id'      => $padreDocId,
                'nombre_original'         => $_FILES['archivo']['name'],
                'nombre_archivo'          => $upload['nombre'],
                'ruta_archivo'            => $upload['ruta'],
                'mime_type'               => $upload['mime'],
                'tamano_bytes'            => $upload['bytes'],
                'hash_sha256'             => $upload['hash'],
                'numero_folio'            => $folio ?: null,
                'fecha_emision'           => $fechaEmision ?: null,
                'fecha_vigencia'          => $fechaVig ?: null,
                'reutilizable'            => (bool)$tipoDoc['reutilizable'],
                'exclusivo_expediente_id' => $expId,
            ]);

            if ($expId) {
                $docService->vincularDocumentoExpediente($docId, $expId, $tramId, 'requisito');
            }

            setFlash('exito', 'Documento subido correctamente. Está pendiente de validación.');
        } catch (Exception $e) {
            error_log('Error subir_documento: ' . $e->getMessage());
            setFlash('error', 'Error al registrar el documento.');
        }

        if ($tramId) {
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $tramId);
        } else {
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=documentos');
        }
        exit;
    }

    // GET — mostrar formulario
    $stmtTipos = $db->query('SELECT * FROM tipos_documento WHERE activo = TRUE ORDER BY nombre ASC');
    $tiposDocumento = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

    // Usar $ctxTipoDocId / $ctxExpedienteId / $ctxPadreDocId ya reconstruidos arriba.
    $tipoPreseleccionado = $ctxTipoDocId;
    $expedienteId        = $ctxExpedienteId;
    $padreId             = $ctxPadreDocId;

    // Si viene desde un requisito específico, obtener metadata del tipo forzado.
    // Si el tipo es inválido NO se baja silenciosamente a modo genérico:
    // se loguea, se muestra flash y se redirige a un lugar seguro. De lo contrario
    // un input roto podría transformar el formulario en genérico sin avisar.
    $tipoForzado = null;
    if ($tipoPreseleccionado) {
        $stmtTF = $db->prepare('SELECT id, nombre, extensiones_permitidas, requiere_vigencia, meses_vigencia, tamano_maximo_mb FROM tipos_documento WHERE id = :id AND activo = TRUE');
        $stmtTF->execute([':id' => $tipoPreseleccionado]);
        $tipoForzado = $stmtTF->fetch(PDO::FETCH_ASSOC);
        if (!$tipoForzado) {
            error_log('[REMTYS] subir_documento: tipo_doc_id inválido en GET. User=' . ($_SESSION['usuario_id'] ?? 0) . ' id=' . $tipoPreseleccionado);
            setFlash('error', 'El tipo de documento del requisito ya no existe o fue desactivado.');
            if ($tramiteId) {
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . (int)$tramiteId);
            } else {
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=documentos');
            }
            exit;
        }
    }

    renderContrib('subir_documento', [
        'titulo'              => 'Subir Documento',
        'accion_activa'       => 'documentos',
        'tiposDocumento'      => $tiposDocumento,
        'tipoPreseleccionado' => $tipoPreseleccionado,
        'tipoForzado'         => $tipoForzado,
        'tramiteId'           => $tramiteId,
        'expedienteId'        => $expedienteId,
        'padreId'             => $padreId,
    ]);
}

// ——— Expedientes ———
function accion_expedientes(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $expService = new ExpedienteService($db);
    $expedientes = $expService->getExpedientesContribuyente($cid);

    renderContrib('expedientes', ['titulo' => 'Mis Expedientes', 'accion_activa' => 'expedientes', 'expedientes' => $expedientes]);
}

function accion_ver_expediente(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $eid = sanitizarEntero($_GET['id'] ?? null);

    if (!$eid) { setFlash('error', 'Expediente no válido.'); header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=expedientes'); exit; }

    $expService = new ExpedienteService($db);
    $expediente = $expService->getExpedienteDetalle($eid, $cid);

    if (!$expediente) { setFlash('error', 'Expediente no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=expedientes'); exit; }

    $stmtHist = $db->prepare('SELECT h.*, u.nombre_completo AS usuario_nombre FROM historial_expediente h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.expediente_id = :eid ORDER BY h.fecha DESC');
    $stmtHist->execute([':eid' => $eid]);
    $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    renderContrib('ver_expediente', [
        'titulo'       => 'Expediente ' . s($expediente['numero_expediente']),
        'accion_activa'=> 'expedientes',
        'expediente'   => $expediente,
        'historial'    => $historial,
    ]);
}

// ——— Trámites ———
function accion_tramites(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();

    $stmt = $db->prepare('
        SELECT t.*, tt.nombre AS tipo_nombre, tt.clave AS tipo_clave,
               e.numero_expediente, p.clave_catastral
        FROM tramites t
        JOIN expedientes e ON e.id = t.expediente_id
        JOIN tipos_tramite tt ON tt.id = t.tipo_tramite_id
        LEFT JOIN predios p ON p.id = e.predio_id
        WHERE e.contribuyente_id = :cid
        ORDER BY t.created_at DESC
    ');
    $stmt->execute([':cid' => $cid]);
    $tramites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    renderContrib('tramites', ['titulo' => 'Mis Trámites', 'accion_activa' => 'tramites', 'tramites' => $tramites]);
}

function accion_nuevo_tramite(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();

        $tipoTramiteId = sanitizarEntero($_POST['tipo_tramite_id'] ?? null);
        $predioId      = sanitizarEntero($_POST['predio_id'] ?? null);
        $descripcion   = sanitizarTexto($_POST['descripcion'] ?? '');

        // Validar ownership del predio
        if ($predioId) {
            $stmtOwn = $db->prepare('SELECT 1 FROM predio_contribuyente WHERE predio_id = :pid AND contribuyente_id = :cid AND activo = TRUE');
            $stmtOwn->execute([':pid' => $predioId, ':cid' => $cid]);
            if (!$stmtOwn->fetchColumn()) {
                setFlash('error', 'No tiene acceso a este predio.');
                header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=nuevo_tramite');
                exit;
            }
        }

        if (!$tipoTramiteId) {
            setFlash('error', 'Seleccione el tipo de trámite.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=nuevo_tramite');
            exit;
        }

        $expService = new ExpedienteService($db);
        try {
            $result = $expService->crearExpedienteConTramite(
                ['contribuyente_id' => $cid, 'predio_id' => $predioId, 'descripcion' => $descripcion],
                ['tipo_tramite_id' => $tipoTramiteId],
                (int)$_SESSION['usuario_id']
            );

            setFlash('exito', 'Trámite iniciado. Expediente: ' . $result['numero_expediente']);
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $result['tramite_id']);
            exit;
        } catch (Exception $e) {
            error_log('Error nuevo_tramite: ' . $e->getMessage());
            setFlash('error', 'Error al crear el trámite. Intente nuevamente.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=nuevo_tramite');
            exit;
        }
    }

    $stmtTT = $db->query('SELECT * FROM tipos_tramite WHERE activo = TRUE ORDER BY nombre ASC');
    $tiposTramite = $stmtTT->fetchAll(PDO::FETCH_ASSOC);

    $stmtPredios = $db->prepare('
        SELECT p.* FROM predios p
        JOIN predio_contribuyente pc ON pc.predio_id = p.id
        WHERE pc.contribuyente_id = :cid AND pc.activo = TRUE
        ORDER BY p.clave_catastral ASC
    ');
    $stmtPredios->execute([':cid' => $cid]);
    $predios = $stmtPredios->fetchAll(PDO::FETCH_ASSOC);

    renderContrib('nuevo_tramite', [
        'titulo'       => 'Nuevo Trámite',
        'accion_activa'=> 'tramites',
        'tiposTramite' => $tiposTramite,
        'predios'      => $predios,
    ]);
}

// ——— PANTALLA CRÍTICA: Requisitos del Trámite ———
function accion_requisitos_tramite(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $tid = sanitizarEntero($_GET['id'] ?? null);

    if (!$tid) {
        setFlash('error', 'Trámite no válido.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=tramites');
        exit;
    }

    // Verificar acceso al trámite
    $stmtT = $db->prepare('
        SELECT t.*, tt.nombre AS tipo_tramite_nombre, tt.clave AS tipo_tramite_clave,
               e.numero_expediente, e.estado AS estado_expediente, e.id AS expediente_id,
               p.clave_catastral, p.calle, p.numero_exterior, p.colonia
        FROM tramites t
        JOIN tipos_tramite tt ON tt.id = t.tipo_tramite_id
        JOIN expedientes e ON e.id = t.expediente_id
        LEFT JOIN predios p ON p.id = e.predio_id
        WHERE t.id = :tid AND e.contribuyente_id = :cid
    ');
    $stmtT->execute([':tid' => $tid, ':cid' => $cid]);
    $tramite = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$tramite) {
        setFlash('error', 'Trámite no encontrado.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=tramites');
        exit;
    }

    $reqService = new RequisitosService($db);
    $requisitos = $reqService->evaluarRequisitos($tid, $cid, $tramite['expediente_id']);
    $completitud = $reqService->calcularCompletitud($requisitos);

    renderContrib('requisitos_tramite', [
        'titulo'       => 'Requisitos del Trámite',
        'accion_activa'=> 'tramites',
        'tramite'      => $tramite,
        'requisitos'   => $requisitos,
        'completitud'  => $completitud,
    ]);
}

/**
 * Envía un trámite a revisión.
 *
 * Precondiciones:
 *   - POST con CSRF válido.
 *   - Trámite debe pertenecer al contribuyente autenticado (ownership vía expediente).
 *   - Trámite en estado 'iniciado' (único estado desde el que el contribuyente puede enviar).
 *   - Requisitos obligatorios aprobados (listo_para_envio = true).
 *
 * Efectos transaccionales:
 *   - Reserva folio ENT en libro_oficial (FolioService).
 *   - Inserta fila en libro_entradas con sello SHA-256.
 *   - Transiciona tramite.estado: 'iniciado' → 'en_revision'.
 *   - Transiciona expediente.estado: 'abierto' → 'en_proceso' (si aplica).
 *   - Registra auditoría old/new (AuditService).
 *   - Registra evento TRAMITE_ENVIADO en historial_expediente.
 */
function accion_enviar_tramite(): void {
    runPipeline([
        'rol'    => 'contribuyente',
        'metodo' => 'POST',
        'csrf'   => true,
    ]);

    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $uid = (int)($_SESSION['usuario_id'] ?? 0);
    $tid = sanitizarEntero($_POST['tramite_id'] ?? null);

    if (!$tid) {
        setFlash('error', 'Trámite no válido.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=tramites');
        exit;
    }

    try {
        $db->beginTransaction();

        // 1) Ownership + lock en trámite y expediente.
        $stmt = $db->prepare('
            SELECT t.id              AS tramite_id,
                   t.numero_tramite,
                   t.estado          AS tramite_estado,
                   t.tipo_tramite_id,
                   e.id              AS expediente_id,
                   e.numero_expediente,
                   e.estado          AS expediente_estado,
                   e.contribuyente_id
            FROM tramites t
            JOIN expedientes e ON e.id = t.expediente_id
            WHERE t.id = :tid
              AND e.contribuyente_id = :cid
            FOR UPDATE OF t, e
        ');
        $stmt->execute([':tid' => $tid, ':cid' => $cid]);
        $ctx = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ctx === false) {
            $db->rollBack();
            logIDOR('ENVIAR_TRAMITE_SIN_OWNERSHIP', ['tramite_id' => $tid, 'contribuyente_id' => $cid]);
            setFlash('error', 'Trámite no encontrado.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=tramites');
            exit;
        }

        // 2) Estado válido para envío.
        if ($ctx['tramite_estado'] !== 'iniciado') {
            $db->rollBack();
            setFlash('error', 'Este trámite ya no puede enviarse (estado actual: ' . $ctx['tramite_estado'] . ').');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $tid);
            exit;
        }

        // 3) Requisitos obligatorios aprobados.
        $reqService  = new RequisitosService($db);
        $requisitos  = $reqService->evaluarRequisitos($tid, $cid, (int)$ctx['expediente_id']);
        $completitud = $reqService->calcularCompletitud($requisitos);
        if (empty($completitud['listo_para_envio'])) {
            $db->rollBack();
            setFlash('error', 'Aún faltan requisitos obligatorios aprobados para enviar este trámite.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $tid);
            exit;
        }

        // 4) Reservar folio oficial ENT y registrar entrada en libro_entradas.
        $folioSrv = new FolioService($db);
        $folio    = $folioSrv->reservarFolio('ENT', 'tramites', (int)$ctx['expediente_id']);

        $selloBase = $folio['numero_completo'] . '|' . $ctx['tramite_id'] . '|' . $ctx['expediente_id'] . '|' . date(DATE_ATOM);
        $sello     = hash('sha256', $selloBase);

        $stmtLib = $db->prepare('
            INSERT INTO libro_entradas (
                anio, tipo_entrada, numero_consecutivo, numero_completo,
                entidad_tipo, entidad_id,
                usuario_recepcion_id, contribuyente_id,
                correlation_id, sello_hash
            ) VALUES (
                :anio, \'TRAMITE\', :consecutivo, :numero_completo,
                \'tramites\', :entidad_id,
                :usuario_id, :contrib_id,
                :correlation_id, :sello
            )
        ');
        $stmtLib->execute([
            ':anio'            => $folio['anio'],
            ':consecutivo'     => $folio['consecutivo'],
            ':numero_completo' => $folio['numero_completo'],
            ':entidad_id'      => $ctx['tramite_id'],
            ':usuario_id'      => $uid,
            ':contrib_id'      => $cid,
            ':correlation_id'  => correlationId(),
            ':sello'           => $sello,
        ]);

        // 5) Transición de estado del trámite con auditoría.
        $auditSrv = new AuditService($db);
        $oldTram  = ['estado' => $ctx['tramite_estado']];
        $newTram  = ['estado' => 'en_revision'];

        $stmtT = $db->prepare('
            UPDATE tramites
            SET estado = \'en_revision\'
            WHERE id = :tid
        ');
        $stmtT->execute([':tid' => (int)$ctx['tramite_id']]);

        $auditSrv->registrarUpdate('tramites', (int)$ctx['tramite_id'], $oldTram, $newTram, 'Contribuyente envió el trámite a revisión');

        // 6) Transición de estado del expediente (opcional — solo si seguía en 'abierto').
        if ($ctx['expediente_estado'] === 'abierto') {
            $stmtE = $db->prepare('
                UPDATE expedientes
                SET estado = \'en_proceso\'
                WHERE id = :eid
            ');
            $stmtE->execute([':eid' => (int)$ctx['expediente_id']]);

            $auditSrv->registrarUpdate(
                'expedientes',
                (int)$ctx['expediente_id'],
                ['estado' => 'abierto'],
                ['estado' => 'en_proceso'],
                'Expediente movido a en_proceso por envío de trámite ' . $ctx['numero_tramite']
            );
        }

        // 7) Historial del expediente — evento catalogado.
        $historialSrv = new HistorialExpedienteService($db);
        $historialSrv->registrar(
            (int)$ctx['expediente_id'],
            HistorialExpedienteService::EV_TRAMITE_ENVIADO,
            'Trámite ' . $ctx['numero_tramite'] . ' enviado a revisión. Folio de entrada: ' . $folio['numero_completo'],
            [
                'tramite_id'        => (int)$ctx['tramite_id'],
                'numero_tramite'    => $ctx['numero_tramite'],
                'folio_entrada'     => $folio['numero_completo'],
                'libro_consecutivo' => $folio['consecutivo'],
                'sello_hash'        => $sello,
                'req_aprobados'     => $completitud['aprobados'],
                'req_total'         => $completitud['total_obligatorios'],
            ]
        );

        $db->commit();

        logAdmin('TRAMITE_ENVIADO', [
            'tramite_id'     => $tid,
            'expediente_id'  => (int)$ctx['expediente_id'],
            'folio_entrada'  => $folio['numero_completo'],
        ]);
        setFlash('exito', 'Trámite enviado a revisión. Folio de entrada: ' . $folio['numero_completo']);
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $tid);
        exit;

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        logError('ENVIAR_TRAMITE_FALLO', [
            'tramite_id' => $tid,
            'error'      => substr($e->getMessage(), 0, 300),
        ]);
        setFlash('error', 'No se pudo enviar el trámite. Intente nuevamente.');
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=requisitos_tramite&id=' . $tid);
        exit;
    }
}

// ——— Confirmar reutilización de documento ———
function accion_saldo(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $fiscal = new FiscalService($db);
    $saldo = $fiscal->getSaldoContribuyente($cid);
    $obligaciones = $fiscal->getObligacionesContribuyente($cid);

    renderContrib('obligaciones', [
        'titulo'       => 'Mis Obligaciones Fiscales',
        'accion_activa'=> 'obligaciones',
        'saldo'        => $saldo,
        'obligaciones' => $obligaciones,
    ]);
}

function accion_obligaciones(): void {
    accion_saldo();
}

function accion_pagos(): void {
    requireContribuyente();
    $db  = getDBConnection();
    $cid = getContribuyenteId();
    $fiscal = new FiscalService($db);
    $pagos = $fiscal->getPagosContribuyente($cid);
    $saldo = $fiscal->getSaldoContribuyente($cid);
    $obligaciones = $fiscal->getObligacionesContribuyente($cid);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $opIds = array_filter(array_map('intval', $_POST['obligacion_periodo_ids'] ?? []), fn($v) => $v > 0);

        // Proteccion contra doble submit
        $submitKey = 'pago_' . md5(implode(',', $opIds) . '_' . $cid);
        if (isset($_SESSION['_last_pago_key']) && $_SESSION['_last_pago_key'] === $submitKey
            && isset($_SESSION['_last_pago_time']) && (time() - $_SESSION['_last_pago_time']) < 30) {
            setFlash('advertencia', 'Este pago ya fue procesado. Verifique en su historial.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=pagos');
            exit;
        }

        if (count($opIds) > 50) {
            setFlash('error', 'Demasiadas obligaciones seleccionadas.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=pagos');
            exit;
        }
        $metodo = in_array($_POST['metodo_pago'] ?? '', ['efectivo','transferencia','tarjeta','cheque'])
            ? $_POST['metodo_pago'] : 'efectivo';

        if (empty($opIds)) {
            setFlash('error', 'Seleccione al menos una obligación a pagar.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=pagos');
            exit;
        }

        // Calcular monto total
        $placeholders = implode(',', array_fill(0, count($opIds), '?'));
        $stmtMonto = $db->prepare("
            SELECT SUM(op.monto + op.monto_recargos - op.monto_pagado)
            FROM obligacion_periodo op
            JOIN obligaciones_fiscales of2 ON of2.id = op.obligacion_id
            WHERE op.id IN ($placeholders) AND op.pagado = FALSE AND of2.contribuyente_id = ?
        ");
        $params = array_values($opIds);
        $params[] = $cid;
        $stmtMonto->execute($params);
        $montoTotal = (float)$stmtMonto->fetchColumn();

        if ($montoTotal <= 0) {
            setFlash('error', 'Las obligaciones seleccionadas ya están pagadas.');
            header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=pagos');
            exit;
        }

        try {
            $fiscal->registrarPago($cid, $montoTotal, $metodo, array_values($opIds));
            $_SESSION['_last_pago_key'] = $submitKey;
            $_SESSION['_last_pago_time'] = time();
            setFlash('exito', 'Pago registrado por $' . number_format($montoTotal, 2) . '. Referencia generada.');
        } catch (Exception $e) {
            error_log('Error pago: ' . $e->getMessage());
            setFlash('error', 'Error al procesar el pago.');
        }
        header('Location: ' . BASE_URL . '/?modulo=contribuyente&accion=pagos');
        exit;
    }

    renderContrib('pagos', [
        'titulo'       => 'Mis Pagos',
        'accion_activa'=> 'pagos',
        'pagos'        => $pagos,
        'saldo'        => $saldo,
        'obligaciones' => $obligaciones,
    ]);
}



