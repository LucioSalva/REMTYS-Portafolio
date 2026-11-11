<?php
/**
 * Controlador Backoffice Admin
 */

require_once APP_PATH . '/services/RequisitosService.php';
require_once APP_PATH . '/services/DocumentoService.php';
require_once APP_PATH . '/services/ExpedienteService.php';
require_once APP_PATH . '/services/FiscalService.php';

function renderAdmin(string $vista, array $vars = []): void {
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/admin/' . $vista . '.php';
    $contenido = ob_get_clean();
    require APP_PATH . '/views/layouts/layout_admin.php';
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
    requireAdminORevisor();
    $db = getDBConnection();

    // Dashboard unificado: 6 KPIs en una sola consulta (1 round-trip vs 6 previos).
    // Cada subquery escalar se evalua una vez y vuelve como columna.
    $stats = $db->query("
        SELECT
            (SELECT COUNT(*) FROM contribuyentes)                                AS contribuyentes,
            (SELECT COUNT(*) FROM predios WHERE activo = TRUE)                   AS predios,
            (SELECT COUNT(*) FROM expedientes)                                   AS expedientes,
            (SELECT COUNT(*) FROM tramites)                                      AS tramites,
            (SELECT COUNT(*) FROM documentos
                WHERE estado_validacion = 'pendiente'
                  AND es_version_activa = TRUE)                                  AS docs_pendientes,
            (SELECT COUNT(*) FROM tramites
                WHERE estado IN ('iniciado','en_revision','documentacion_pendiente')) AS tramites_activos
    ")->fetch(PDO::FETCH_ASSOC);

    // Normalizar a int (PDO devuelve string por defecto en PostgreSQL).
    foreach ($stats as $k => $v) {
        $stats[$k] = (int)$v;
    }

    $stmtExpRecientes = $db->query('
        SELECT e.id, e.numero_expediente, e.estado, e.fecha_apertura,
               c.nombre_completo AS contribuyente,
               p.clave_catastral
        FROM expedientes e
        JOIN contribuyentes c ON c.id = e.contribuyente_id
        LEFT JOIN predios p ON p.id = e.predio_id
        ORDER BY e.created_at DESC LIMIT 10
    ');
    $expRecientes = $stmtExpRecientes->fetchAll(PDO::FETCH_ASSOC);

    // Mapa centralizado de navegación para tarjetas del dashboard
    $cardLinks = [
        'contribuyentes'   => BASE_URL . '/?modulo=admin&accion=contribuyentes',
        'predios'          => BASE_URL . '/?modulo=admin&accion=predios',
        'expedientes'      => BASE_URL . '/?modulo=admin&accion=expedientes',
        'tramites'         => BASE_URL . '/?modulo=admin&accion=tramites',
        'docs_pendientes'  => BASE_URL . '/?modulo=admin&accion=validacion_docs',
        'tramites_activos' => BASE_URL . '/?modulo=admin&accion=tramites',
    ];

    renderAdmin('dashboard', ['titulo' => 'Dashboard', 'accion_activa' => 'dashboard', 'stats' => $stats, 'expRecientes' => $expRecientes, 'cardLinks' => $cardLinks]);
}

// ——— Contribuyentes ———
function accion_contribuyentes(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $busqueda = sanitizarTexto($_GET['q'] ?? '');

    // Paginacion keyset (helper paginacion.php). Orden por (created_at DESC, id DESC).
    $cursor = cursorDesdeRequest();

    $sqlBase = '
        SELECT c.id, c.nombre_completo, c.rfc, c.telefono, c.created_at,
               c.tipo_persona, c.representante_legal, c.curp,
               u.email, u.activo AS usuario_activo, u.ultimo_acceso,
               COALESCE(pp.total_predios, 0) AS total_predios,
               COALESCE(ee.total_expedientes, 0) AS total_expedientes
        FROM v_contribuyentes_detalle c
        JOIN usuarios u ON u.id = c.usuario_id
        LEFT JOIN LATERAL (
            SELECT COUNT(DISTINCT predio_id) AS total_predios
            FROM predio_contribuyente
            WHERE contribuyente_id = c.id AND activo = TRUE
        ) pp ON TRUE
        LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_expedientes
            FROM expedientes
            WHERE contribuyente_id = c.id
        ) ee ON TRUE
        WHERE 1=1
    ';
    $params = [];
    if ($busqueda) {
        $sqlBase .= " AND (c.nombre_completo ILIKE :q OR c.rfc ILIKE :q OR u.email ILIKE :q)";
        $params[':q'] = '%' . $busqueda . '%';
    }

    $pagina = keysetFetch($db, $sqlBase, $params, 'c.created_at', 'c.id', $cursor, 25);

    renderAdmin('contribuyentes', [
        'titulo'         => 'Contribuyentes',
        'accion_activa'  => 'contribuyentes',
        'contribuyentes' => $pagina['items'],
        'busqueda'       => $busqueda,
        'next_cursor'    => $pagina['next_cursor'],
    ]);
}

function accion_ver_contribuyente(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $cid = sanitizarEntero($_GET['id'] ?? null);
    if (!$cid) { setFlash('error', 'ID inválido.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=contribuyentes'); exit; }

    $stmt = $db->prepare('SELECT c.*, u.email, u.rol, u.activo AS usuario_activo, u.ultimo_acceso FROM v_contribuyentes_detalle c JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = :cid');
    $stmt->execute([':cid' => $cid]);
    $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contribuyente) { setFlash('error', 'Contribuyente no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=contribuyentes'); exit; }

    $stmtPredios = $db->prepare('SELECT p.*, pc.tipo_relacion, pc.fecha_inicio FROM predios p JOIN predio_contribuyente pc ON pc.predio_id = p.id WHERE pc.contribuyente_id = :cid AND pc.activo = TRUE');
    $stmtPredios->execute([':cid' => $cid]);
    $predios = $stmtPredios->fetchAll(PDO::FETCH_ASSOC);

    $expService = new ExpedienteService($db);
    $expedientes = $expService->getExpedientesContribuyente($cid);

    $fiscal = new FiscalService($db);
    $saldo = $fiscal->getSaldoContribuyente($cid);

    renderAdmin('ver_contribuyente', ['titulo' => 'Contribuyente', 'accion_activa' => 'contribuyentes',
        'contribuyente' => $contribuyente, 'predios' => $predios, 'expedientes' => $expedientes, 'saldo' => $saldo]);
}

function accion_nuevo_contribuyente(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();

        $db = getDBConnection();
        $nombre   = sanitizarTexto($_POST['nombre_completo'] ?? '');
        $email    = sanitizarEmail($_POST['email'] ?? '');
        $rfc      = sanitizarRFC($_POST['rfc'] ?? '');
        $tipo     = in_array($_POST['tipo_persona'] ?? '', ['fisica', 'moral'], true) ? $_POST['tipo_persona'] : 'fisica';
        $detalleContribuyente = normalizarDetalleContribuyente($_POST);
        $telefono = sanitizarTexto($_POST['telefono'] ?? '');
        $calle    = sanitizarTexto($_POST['calle'] ?? '');
        $municipio= sanitizarTexto($_POST['municipio'] ?? '');
        $estado   = sanitizarTexto($_POST['estado'] ?? '');
        $cp       = sanitizarTexto($_POST['codigo_postal'] ?? '');
        $errores  = [];

        if (estaVacio($nombre) || !validarLongitudTexto($nombre, 3, 400)) {
            $errores[] = 'El nombre completo es requerido.';
        }
        if (!$email) {
            $errores[] = 'El correo electronico no es valido.';
        }
        if (estaVacio($rfc) || !validarRFC($rfc)) {
            $errores[] = 'El RFC no tiene un formato valido.';
        }
        if ($cp !== '' && !validarCP($cp)) {
            $errores[] = 'El codigo postal debe tener 5 digitos.';
        }
        $errores = array_merge($errores, validarDetalleContribuyente($tipo, $detalleContribuyente));

        if (empty($errores)) {
            $chkEmail = $db->prepare('SELECT 1 FROM usuarios WHERE email = :email LIMIT 1');
            $chkEmail->execute([':email' => $email]);
            if ($chkEmail->fetchColumn()) {
                $errores[] = 'El correo electronico ya esta registrado.';
            }

            $chkRfc = $db->prepare('SELECT 1 FROM contribuyentes WHERE rfc = :rfc LIMIT 1');
            $chkRfc->execute([':rfc' => $rfc]);
            if ($chkRfc->fetchColumn()) {
                $errores[] = 'El RFC ya esta registrado en el sistema.';
            }
        }

        if (!empty($errores)) {
            setFlash('error', implode(' ', $errores));
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_contribuyente');
            exit;
        }

        $tmpPass = 'Tmp#' . substr(bin2hex(random_bytes(4)), 0, 8) . 'Aa1!';
        $hash = password_hash($tmpPass, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO usuarios (email, password_hash, nombre_completo, rol, email_verificado, email_verificado_at) VALUES (:email, :hash, :nombre, 'contribuyente', TRUE, NOW()) RETURNING id");
            $stmt->execute([':email' => $email, ':hash' => $hash, ':nombre' => $nombre]);
            $uid = (int)$stmt->fetchColumn();

            $stmt2 = $db->prepare('
                INSERT INTO contribuyentes (usuario_id, tipo_persona, nombre_completo, rfc, telefono, email_contacto, calle, municipio, estado, codigo_postal)
                VALUES (:uid, :tp, :nombre, :rfc, :tel, :email, :calle, :mun, :est, :cp)
                RETURNING id
            ');
            $stmt2->execute([
                ':uid' => $uid,
                ':tp' => $tipo,
                ':nombre' => $nombre,
                ':rfc' => $rfc,
                ':tel' => $telefono ?: null,
                ':email' => $email,
                ':calle' => $calle ?: null,
                ':mun' => $municipio ?: null,
                ':est' => $estado ?: null,
                ':cp' => $cp ?: null,
            ]);
            $contribuyenteId = (int)$stmt2->fetchColumn();

            guardarDetalleContribuyente($db, $contribuyenteId, $tipo, $detalleContribuyente);

            $db->commit();
            logAdmin('CONTRIBUYENTE_CREADO', ['contribuyente_id' => $contribuyenteId, 'email' => $email]);
            setFlash('exito', 'Contribuyente creado exitosamente. Se ha generado una contrasena temporal que debe ser comunicada al contribuyente de forma segura.');
            error_log('[REMTYS-ADMIN] Contrasena temporal generada para contribuyente ' . $email . ' (usuario_id: ' . $uid . '). Entregar de forma segura.');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear contribuyente desde admin: ' . $e->getMessage());
            setFlash('error', 'No fue posible crear el contribuyente.');
        }
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=contribuyentes');
        exit;
    }
    renderAdmin('form_contribuyente', ['titulo' => 'Nuevo Contribuyente', 'accion_activa' => 'contribuyentes', 'contribuyente' => null]);
}

function accion_editar_contribuyente(): void {
    requireAdmin();
    $db = getDBConnection();
    $cid = sanitizarEntero($_GET['id'] ?? null);
    if (!$cid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=contribuyentes'); exit; }

    $stmtActual = $db->prepare('SELECT * FROM v_contribuyentes_detalle WHERE id = :cid LIMIT 1');
    $stmtActual->execute([':cid' => $cid]);
    $contribuyente = $stmtActual->fetch(PDO::FETCH_ASSOC);
    if (!$contribuyente) {
        setFlash('error', 'Contribuyente no encontrado.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=contribuyentes');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $tipo     = in_array($_POST['tipo_persona'] ?? '', ['fisica', 'moral'], true) ? $_POST['tipo_persona'] : ($contribuyente['tipo_persona'] ?? 'fisica');
        $nombre   = sanitizarTexto($_POST['nombre_completo'] ?? '');
        $telefono = sanitizarTexto($_POST['telefono'] ?? '');
        $email    = sanitizarEmail($_POST['email_contacto'] ?? '');
        $calle    = sanitizarTexto($_POST['calle'] ?? '');
        $municipio= sanitizarTexto($_POST['municipio'] ?? '');
        $estado   = sanitizarTexto($_POST['estado'] ?? '');
        $cp       = sanitizarTexto($_POST['codigo_postal'] ?? '');
        $detalleContribuyente = normalizarDetalleContribuyente($_POST);
        $errores = [];

        if (estaVacio($nombre) || !validarLongitudTexto($nombre, 3, 400)) {
            $errores[] = 'El nombre completo es requerido.';
        }
        if (trim((string)($_POST['email_contacto'] ?? '')) !== '' && !$email) {
            $errores[] = 'El correo de contacto no es valido.';
        }
        if ($cp !== '' && !validarCP($cp)) {
            $errores[] = 'El codigo postal debe tener 5 digitos.';
        }
        $errores = array_merge($errores, validarDetalleContribuyente($tipo, $detalleContribuyente));

        if (!empty($errores)) {
            setFlash('error', implode(' ', $errores));
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_contribuyente&id=' . $cid);
            exit;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare('
                UPDATE contribuyentes
                SET nombre_completo = :n,
                    tipo_persona = :tp,
                    telefono = :t,
                    email_contacto = :e,
                    calle = :c,
                    municipio = :m,
                    estado = :est,
                    codigo_postal = :cp
                WHERE id = :cid
            ');
            $stmt->execute([
                ':n' => $nombre,
                ':tp' => $tipo,
                ':t' => $telefono ?: null,
                ':e' => $email ?: null,
                ':c' => $calle ?: null,
                ':m' => $municipio ?: null,
                ':est' => $estado ?: null,
                ':cp' => $cp ?: null,
                ':cid' => $cid,
            ]);

            guardarDetalleContribuyente($db, $cid, $tipo, $detalleContribuyente);

            $db->commit();
            setFlash('exito', 'Contribuyente actualizado.');
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_contribuyente&id=' . $cid);
            exit;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al actualizar contribuyente desde admin: ' . $e->getMessage());
            setFlash('error', 'No fue posible actualizar el contribuyente.');
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_contribuyente&id=' . $cid);
            exit;
        }
    }

    renderAdmin('form_contribuyente', ['titulo' => 'Editar Contribuyente', 'accion_activa' => 'contribuyentes', 'contribuyente' => $contribuyente]);
}

function accion_predios(): void {
    requireAdmin();
    $db = getDBConnection();
    $q = sanitizarTexto($_GET['q'] ?? '');
    $sql = 'SELECT p.*, COUNT(DISTINCT pc.contribuyente_id) AS total_propietarios FROM predios p LEFT JOIN predio_contribuyente pc ON pc.predio_id = p.id AND pc.activo=TRUE WHERE 1=1';
    $params = [];
    if ($q) { $sql .= " AND (p.clave_catastral ILIKE :q OR p.cuenta_predial ILIKE :q OR p.municipio ILIKE :q)"; $params[':q'] = '%'.$q.'%'; }
    $sql .= ' GROUP BY p.id ORDER BY p.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $predios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('predios', ['titulo' => 'Predios', 'accion_activa' => 'predios', 'predios' => $predios, 'busqueda' => $q]);
}

function accion_ver_predio(): void {
    requireAdmin();
    $db = getDBConnection();
    $pid = sanitizarEntero($_GET['id'] ?? null);
    if (!$pid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=predios'); exit; }
    $stmt = $db->prepare('SELECT * FROM predios WHERE id=:pid');
    $stmt->execute([':pid' => $pid]);
    $predio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$predio) { setFlash('error', 'Predio no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=predios'); exit; }
    $stmtH = $db->prepare('SELECT pc.*, c.nombre_completo AS contrib_nombre, c.rfc FROM predio_contribuyente pc JOIN contribuyentes c ON c.id=pc.contribuyente_id WHERE pc.predio_id=:pid ORDER BY pc.fecha_inicio DESC');
    $stmtH->execute([':pid' => $pid]);
    $historial = $stmtH->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('ver_predio', ['titulo' => 'Predio', 'accion_activa' => 'predios', 'predio' => $predio, 'historial' => $historial]);
}

function accion_nuevo_predio(): void {
    requireAdmin();
    $db = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $ck = sanitizarTexto($_POST['clave_catastral'] ?? '');
        $cp = sanitizarTexto($_POST['cuenta_predial'] ?? '');
        $colonia = sanitizarTexto($_POST['colonia'] ?? '');
        $codigo_postal = sanitizarTexto($_POST['codigo_postal'] ?? '');
        $municipio = APP_MUNICIPIO;
        $estado = 'Estado de México';

        if (estaVacio($ck) || estaVacio($cp)) { setFlash('error', 'Clave catastral y cuenta predial son requeridos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_predio'); exit; }

        // Validar que el código postal pertenece al catálogo municipal
        if (!estaVacio($codigo_postal)) {
            $stmtCP = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND activo = TRUE');
            $stmtCP->execute([':cp' => $codigo_postal]);
            if ((int)$stmtCP->fetchColumn() === 0) {
                setFlash('error', 'El código postal no pertenece al catálogo del municipio.');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_predio'); exit;
            }
        }

        // Validar que la colonia corresponde al código postal seleccionado
        if (!estaVacio($colonia) && !estaVacio($codigo_postal)) {
            $stmtCol = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND colonia = :col AND activo = TRUE');
            $stmtCol->execute([':cp' => $codigo_postal, ':col' => $colonia]);
            if ((int)$stmtCol->fetchColumn() === 0) {
                setFlash('error', 'La colonia no corresponde al código postal seleccionado.');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_predio'); exit;
            }
        }

        try {
            $stmt = $db->prepare('INSERT INTO predios (clave_catastral, cuenta_predial, nombre_descripcion, uso_suelo, superficie_total, valor_catastral, calle, numero_exterior, colonia, municipio, estado, codigo_postal) VALUES (:ck,:cp,:nd,:us,:st,:vc,:cl,:ne,:col,:mun,:est,:cpost)');
            $stmt->execute([':ck'=>$ck,':cp'=>sanitizarTexto($_POST['cuenta_predial']??''),':nd'=>sanitizarTexto($_POST['nombre_descripcion']??''),
                ':us'=>sanitizarTexto($_POST['uso_suelo']??''),':st'=>sanitizarDecimal($_POST['superficie_total']??null),
                ':vc'=>sanitizarDecimal($_POST['valor_catastral']??null),':cl'=>sanitizarTexto($_POST['calle']??''),
                ':ne'=>sanitizarTexto($_POST['numero_exterior']??''),':col'=>$colonia,
                ':mun'=>$municipio,':est'=>$estado,
                ':cpost'=>$codigo_postal]);
            setFlash('exito', 'Predio creado correctamente.');
        } catch (Exception $e) { error_log('Error nuevo_predio: ' . $e->getMessage()); setFlash('error', 'No fue posible crear el predio. Intente nuevamente.'); }
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=predios'); exit;
    }

    $codigosPostales = obtenerCodigosPostalesMunicipio($db);
    renderAdmin('form_predio', ['titulo' => 'Nuevo Predio', 'accion_activa' => 'predios', 'predio' => null, 'codigos_postales' => $codigosPostales]);
}

function accion_editar_predio(): void {
    requireAdmin();
    $db = getDBConnection();
    $pid = sanitizarEntero($_GET['id'] ?? null);
    if (!$pid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=predios'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $colonia = sanitizarTexto($_POST['colonia'] ?? '');
        $codigo_postal = sanitizarTexto($_POST['codigo_postal'] ?? '');
        $municipio = APP_MUNICIPIO;
        $estado = 'Estado de México';

        // Validar que el código postal pertenece al catálogo municipal
        if (!estaVacio($codigo_postal)) {
            $stmtCP = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND activo = TRUE');
            $stmtCP->execute([':cp' => $codigo_postal]);
            if ((int)$stmtCP->fetchColumn() === 0) {
                setFlash('error', 'El código postal no pertenece al catálogo del municipio.');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_predio&id=' . $pid); exit;
            }
        }

        // Validar que la colonia corresponde al código postal seleccionado
        if (!estaVacio($colonia) && !estaVacio($codigo_postal)) {
            $stmtCol = $db->prepare('SELECT COUNT(*) FROM catalogo_cp_colonia WHERE codigo_postal = :cp AND colonia = :col AND activo = TRUE');
            $stmtCol->execute([':cp' => $codigo_postal, ':col' => $colonia]);
            if ((int)$stmtCol->fetchColumn() === 0) {
                setFlash('error', 'La colonia no corresponde al código postal seleccionado.');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_predio&id=' . $pid); exit;
            }
        }

        $stmt = $db->prepare('UPDATE predios SET nombre_descripcion=:nd, uso_suelo=:us, superficie_total=:st, valor_catastral=:vc, calle=:cl, numero_exterior=:ne, colonia=:col, municipio=:mun, estado=:est, codigo_postal=:cpost WHERE id=:pid');
        $stmt->execute([':nd'=>sanitizarTexto($_POST['nombre_descripcion']??''),':us'=>sanitizarTexto($_POST['uso_suelo']??''),
            ':st'=>sanitizarDecimal($_POST['superficie_total']??null),':vc'=>sanitizarDecimal($_POST['valor_catastral']??null),
            ':cl'=>sanitizarTexto($_POST['calle']??''),':ne'=>sanitizarTexto($_POST['numero_exterior']??''),
            ':col'=>$colonia,':mun'=>$municipio,
            ':est'=>$estado,':cpost'=>$codigo_postal,':pid'=>$pid]);
        setFlash('exito', 'Predio actualizado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_predio&id=' . $pid); exit;
    }
    $stmt = $db->prepare('SELECT * FROM predios WHERE id=:pid'); $stmt->execute([':pid'=>$pid]);
    $predio = $stmt->fetch(PDO::FETCH_ASSOC);
    $codigosPostales = obtenerCodigosPostalesMunicipio($db);
    renderAdmin('form_predio', ['titulo' => 'Editar Predio', 'accion_activa' => 'predios', 'predio' => $predio, 'codigos_postales' => $codigosPostales]);
}

// ——— Expedientes admin ———
function accion_expedientes(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $q = sanitizarTexto($_GET['q'] ?? '');
    $estado = sanitizarTexto($_GET['estado'] ?? '');
    $sql = 'SELECT e.*, c.nombre_completo AS contribuyente_nombre, c.rfc, p.clave_catastral, COUNT(t.id) AS total_tramites FROM expedientes e JOIN contribuyentes c ON c.id=e.contribuyente_id LEFT JOIN predios p ON p.id=e.predio_id LEFT JOIN tramites t ON t.expediente_id=e.id WHERE 1=1';
    $params = [];
    if ($q) { $sql .= " AND (e.numero_expediente ILIKE :q OR c.nombre_completo ILIKE :q OR c.rfc ILIKE :q)"; $params[':q'] = '%'.$q.'%'; }
    if ($estado) { $sql .= " AND e.estado = :estado"; $params[':estado'] = $estado; }
    $sql .= ' GROUP BY e.id, c.nombre_completo, c.rfc, p.clave_catastral ORDER BY e.created_at DESC';
    $stmt = $db->prepare($sql); $stmt->execute($params);
    $expedientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('expedientes', ['titulo' => 'Expedientes', 'accion_activa' => 'expedientes', 'expedientes' => $expedientes, 'busqueda' => $q, 'estadoFiltro' => $estado]);
}

function accion_ver_expediente(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $eid = sanitizarEntero($_GET['id'] ?? null);
    if (!$eid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=expedientes'); exit; }

    $stmt = $db->prepare('SELECT e.*, c.nombre_completo AS contribuyente_nombre, c.rfc, c.id AS contrib_id, p.clave_catastral, p.calle, p.numero_exterior FROM expedientes e JOIN contribuyentes c ON c.id=e.contribuyente_id LEFT JOIN predios p ON p.id=e.predio_id WHERE e.id=:eid');
    $stmt->execute([':eid' => $eid]);
    $expediente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$expediente) { setFlash('error', 'Expediente no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=expedientes'); exit; }

    $stmtT = $db->prepare('SELECT t.*, tt.nombre AS tipo_nombre FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id WHERE t.expediente_id=:eid ORDER BY t.created_at ASC');
    $stmtT->execute([':eid' => $eid]);
    $tramites = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    $stmtH = $db->prepare('SELECT h.*, u.nombre_completo AS usuario_nombre FROM historial_expediente h LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE h.expediente_id=:eid ORDER BY h.fecha DESC');
    $stmtH->execute([':eid' => $eid]);
    $historial = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    renderAdmin('ver_expediente', ['titulo' => 'Expediente', 'accion_activa' => 'expedientes', 'expediente' => $expediente, 'tramites' => $tramites, 'historial' => $historial]);
}

function accion_nuevo_expediente(): void {
    requireAdmin();
    $db = getDBConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $cid   = sanitizarEntero($_POST['contribuyente_id'] ?? null);
        $pid   = sanitizarEntero($_POST['predio_id'] ?? null);
        $tipoT = sanitizarEntero($_POST['tipo_tramite_id'] ?? null);
        $desc  = sanitizarTexto($_POST['descripcion'] ?? '');
        if (!$cid || !$tipoT) { setFlash('error', 'Contribuyente y tipo de trámite son requeridos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_expediente'); exit; }
        $expService = new ExpedienteService($db);
        try {
            $r = $expService->crearExpedienteConTramite(['contribuyente_id'=>$cid,'predio_id'=>$pid,'descripcion'=>$desc],['tipo_tramite_id'=>$tipoT],(int)$_SESSION['usuario_id']);
            setFlash('exito','Expediente '.$r['numero_expediente'].' creado.');
            header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_expediente&id=' . $r['expediente_id']); exit;
        } catch (Exception $e) { error_log('Error nuevo_expediente: ' . $e->getMessage()); setFlash('error','No fue posible crear el expediente. Intente nuevamente.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_expediente'); exit; }
    }
    $contribs = $db->query('SELECT id, nombre_completo AS nombre, rfc FROM contribuyentes ORDER BY nombre_completo ASC')->fetchAll(PDO::FETCH_ASSOC);
    $tiposTramite = $db->query('SELECT * FROM tipos_tramite WHERE activo=TRUE ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    $predios = $db->query('SELECT id, clave_catastral, calle, numero_exterior FROM predios WHERE activo=TRUE ORDER BY clave_catastral ASC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('form_expediente', ['titulo'=>'Nuevo Expediente','accion_activa'=>'expedientes','contribs'=>$contribs,'tiposTramite'=>$tiposTramite,'predios'=>$predios]);
}

function accion_editar_expediente(): void {
    requireAdmin();
    $db = getDBConnection();
    $eid = sanitizarEntero($_GET['id'] ?? null);
    if (!$eid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=expedientes'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $estado = sanitizarTexto($_POST['estado'] ?? '');
        $obs = sanitizarTexto($_POST['observaciones'] ?? '');
        $estados = ['abierto','en_proceso','resuelto','cerrado','cancelado'];
        if (!in_array($estado, $estados)) { setFlash('error','Estado inválido.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_expediente&id='.$eid); exit; }

        // Validar transicion de estado
        $transicionesExp = [
            'abierto'    => ['en_proceso', 'cancelado'],
            'en_proceso' => ['resuelto', 'cancelado'],
            'resuelto'   => ['cerrado'],
            'cerrado'    => [],
            'cancelado'  => ['abierto'],
        ];

        // Obtener estado actual
        $stmtEstAct = $db->prepare('SELECT estado FROM expedientes WHERE id = :id');
        $stmtEstAct->execute([':id' => $eid]);
        $estadoActual = $stmtEstAct->fetchColumn();

        if ($estadoActual && $estado !== $estadoActual) {
            $permitidas = $transicionesExp[$estadoActual] ?? [];
            if (!in_array($estado, $permitidas, true)) {
                setFlash('error', 'No se puede cambiar de "' . $estadoActual . '" a "' . $estado . '".');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_expediente&id=' . $eid);
                exit;
            }
        }

        $expService = new ExpedienteService($db);
        $expService->cambiarEstadoExpediente($eid, $estado, (int)$_SESSION['usuario_id']);
        $db->prepare('UPDATE expedientes SET observaciones=:obs WHERE id=:eid')->execute([':obs'=>$obs,':eid'=>$eid]);
        setFlash('exito','Expediente actualizado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_expediente&id='.$eid); exit;
    }
    $stmt = $db->prepare('SELECT * FROM expedientes WHERE id=:eid'); $stmt->execute([':eid'=>$eid]);
    $expediente = $stmt->fetch(PDO::FETCH_ASSOC);
    renderAdmin('form_estado_expediente', ['titulo'=>'Editar Expediente','accion_activa'=>'expedientes','expediente'=>$expediente]);
}

// ——— Trámites admin ———
function accion_tramites(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $q = sanitizarTexto($_GET['q'] ?? '');
    $estado = sanitizarTexto($_GET['estado'] ?? '');
    $sql = 'SELECT t.*, tt.nombre AS tipo_nombre, e.numero_expediente, c.nombre_completo AS contrib_nombre FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id JOIN expedientes e ON e.id=t.expediente_id JOIN contribuyentes c ON c.id=e.contribuyente_id WHERE 1=1';
    $params = [];
    if ($q) { $sql .= " AND (t.numero_tramite ILIKE :q OR tt.nombre ILIKE :q OR c.nombre_completo ILIKE :q)"; $params[':q']='%'.$q.'%'; }
    if ($estado) { $sql .= " AND t.estado=:estado"; $params[':estado']=$estado; }
    $sql .= ' ORDER BY t.created_at DESC';
    $stmt = $db->prepare($sql); $stmt->execute($params);
    $tramites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('tramites', ['titulo'=>'Trámites','accion_activa'=>'tramites','tramites'=>$tramites,'busqueda'=>$q,'estadoFiltro'=>$estado]);
}

function accion_ver_tramite(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $tid = sanitizarEntero($_GET['id'] ?? null);
    if (!$tid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    $stmt = $db->prepare('SELECT t.*, tt.nombre AS tipo_nombre, tt.clave, e.numero_expediente, e.id AS expediente_id, c.nombre_completo AS contrib_nombre, c.id AS contrib_id FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id JOIN expedientes e ON e.id=t.expediente_id JOIN contribuyentes c ON c.id=e.contribuyente_id WHERE t.id=:tid');
    $stmt->execute([':tid'=>$tid]);
    $tramite = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tramite) { setFlash('error','Trámite no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    $reqService = new RequisitosService($db);
    $requisitos = $reqService->evaluarRequisitos($tid, $tramite['contrib_id'], $tramite['expediente_id']);
    $completitud = $reqService->calcularCompletitud($requisitos);
    renderAdmin('ver_tramite', ['titulo'=>'Trámite','accion_activa'=>'tramites','tramite'=>$tramite,'requisitos'=>$requisitos,'completitud'=>$completitud]);
}

function accion_nuevo_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    $eid = sanitizarEntero($_GET['expediente_id'] ?? null);
    if (!$eid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=expedientes'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $tipoT = sanitizarEntero($_POST['tipo_tramite_id'] ?? null);
        $obs   = sanitizarTexto($_POST['observaciones'] ?? '');
        if (!$tipoT) { setFlash('error','Seleccione el tipo de trámite.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_tramite&expediente_id='.$eid); exit; }
        $numT = (new ExpedienteService($db))->generarNumeroTramite();
        $stmt = $db->prepare("INSERT INTO tramites (numero_tramite, expediente_id, tipo_tramite_id, estado, observaciones, usuario_asignado_id) VALUES (:num,:eid,:tipo,'iniciado',:obs,:uid) RETURNING id");
        $stmt->execute([':num'=>$numT,':eid'=>$eid,':tipo'=>$tipoT,':obs'=>$obs,':uid'=>$_SESSION['usuario_id']]);
        $newTid = $stmt->fetchColumn();
        (new ExpedienteService($db))->registrarHistorial($eid, 'NUEVO_TRAMITE', 'Trámite '.$numT.' agregado al expediente.', (int)$_SESSION['usuario_id']);
        setFlash('exito','Trámite '.$numT.' creado.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_expediente&id='.$eid); exit;
    }
    $tiposTramite = $db->query('SELECT * FROM tipos_tramite WHERE activo=TRUE ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('form_tramite', ['titulo'=>'Nuevo Trámite','accion_activa'=>'tramites','tiposTramite'=>$tiposTramite,'expedienteId'=>$eid]);
}

function accion_editar_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    $tid = sanitizarEntero($_GET['id'] ?? null);
    if (!$tid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $estado = sanitizarTexto($_POST['estado'] ?? '');
        $obs = sanitizarTexto($_POST['observaciones'] ?? '');
        $estados = ['iniciado','en_revision','documentacion_pendiente','aprobado','rechazado','cancelado'];
        if (!in_array($estado, $estados)) { setFlash('error','Estado inválido.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_tramite&id='.$tid); exit; }

        // Validar transicion de estado
        $transicionesTram = [
            'iniciado'                => ['en_revision', 'cancelado'],
            'en_revision'             => ['documentacion_pendiente', 'aprobado', 'rechazado', 'cancelado'],
            'documentacion_pendiente' => ['en_revision', 'cancelado'],
            'aprobado'                => [],
            'rechazado'               => ['iniciado'],
            'cancelado'               => [],
        ];

        $stmtEstActT = $db->prepare('SELECT estado FROM tramites WHERE id = :id');
        $stmtEstActT->execute([':id' => $tid]);
        $estadoActualT = $stmtEstActT->fetchColumn();

        if ($estadoActualT && $estado !== $estadoActualT) {
            $permitidasT = $transicionesTram[$estadoActualT] ?? [];
            if (!in_array($estado, $permitidasT, true)) {
                setFlash('error', 'No se puede cambiar de "' . $estadoActualT . '" a "' . $estado . '".');
                header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_tramite&id=' . $tid);
                exit;
            }
        }

        $stmt = $db->prepare('UPDATE tramites SET estado=:est, observaciones=:obs WHERE id=:tid');
        $stmt->execute([':est'=>$estado,':obs'=>$obs,':tid'=>$tid]);
        // Registrar en historial
        $stmtE = $db->prepare('SELECT expediente_id FROM tramites WHERE id=:tid'); $stmtE->execute([':tid'=>$tid]);
        $eid = $stmtE->fetchColumn();
        if ($eid) { (new ExpedienteService($db))->registrarHistorial($eid,'TRAMITE_ESTADO','Trámite actualizado a: '.$estado,(int)$_SESSION['usuario_id']); }
        setFlash('exito','Trámite actualizado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=ver_tramite&id='.$tid); exit;
    }
    $stmt = $db->prepare('SELECT * FROM tramites WHERE id=:tid'); $stmt->execute([':tid'=>$tid]);
    $tramite = $stmt->fetch(PDO::FETCH_ASSOC);
    renderAdmin('form_estado_tramite', ['titulo'=>'Editar Trámite','accion_activa'=>'tramites','tramite'=>$tramite]);
}

// ——— Validación documental ———
function accion_validacion_docs(): void {
    requireAdminORevisor();
    $db = getDBConnection();
    $docService = new DocumentoService($db);
    $documentos = $docService->getDocumentosPendientes();
    renderAdmin('validacion_documentos', ['titulo'=>'Validación de Documentos','accion_activa'=>'validacion_docs','documentos'=>$documentos]);
}

function accion_validar_documento(): void {
    requireAdminORevisor();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=validacion_docs'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $docId = sanitizarEntero($_POST['documento_id'] ?? null);
    $resultado = sanitizarTexto($_POST['resultado'] ?? '');
    $obs = sanitizarTexto($_POST['observaciones'] ?? '');

    $resultadosValidos = ['aprobado','rechazado','en_revision'];
    if (!$docId || !in_array($resultado, $resultadosValidos)) {
        setFlash('error','Datos inválidos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=validacion_docs'); exit;
    }

    $docService = new DocumentoService($db);
    $motivoRect = sanitizarTexto($_POST['motivo_rectificacion'] ?? '') ?: null;
    try {
        $validacionId = $docService->registrarValidacion(
            $docId,
            (int)$_SESSION['usuario_id'],
            $resultado,
            $obs,
            $motivoRect
        );
        logAdmin('DOCUMENTO_VALIDADO', [
            'documento_id'  => $docId,
            'validacion_id' => $validacionId,
            'resultado'     => $resultado,
            'rectificacion' => $motivoRect !== null,
        ]);
        setFlash('exito','Documento ' . $resultado . ' correctamente.');
    } catch (InvalidArgumentException $e) {
        logError('VALIDACION_DOCUMENTO_ARG', ['documento_id' => $docId, 'error' => $e->getMessage()]);
        setFlash('error', $e->getMessage());
    } catch (Throwable $e) {
        logError('VALIDACION_DOCUMENTO_FALLO', ['documento_id' => $docId, 'error' => $e->getMessage()]);
        setFlash('error','Error al registrar la validación.');
    }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=validacion_docs');
    exit;
}

/**
 * Sirve el archivo fisico de un documento para previsualizacion o descarga
 * desde el backoffice administrativo. Solo admin/revisor/supergod pueden
 * acceder a cualquier documento del sistema. Delega el servido y las
 * defensas al helper centralizado servirDocumento().
 *
 * Parametros GET:
 *   - id         : int   (requerido) id del documento
 *   - descargar  : '0'|'1' (opcional) 1 fuerza Content-Disposition: attachment
 */
function accion_ver_documento(): void {
    requireAdminORevisor();

    $id = sanitizarEntero($_GET['id'] ?? null);
    if (!$id) {
        render404Documento('Identificador de documento invalido.');
    }

    $db   = getDBConnection();
    $stmt = $db->prepare('SELECT id, contribuyente_id, nombre_original, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_sha256 FROM documentos WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        logSeguridad('DOC_INEXISTENTE', ['documento_id' => $id, 'contexto' => 'admin']);
        render404Documento('El documento solicitado no existe.');
    }

    $disposition = (($_GET['descargar'] ?? '0') === '1') ? 'attachment' : 'inline';
    servirDocumento($doc, $disposition, 'DOC_VISTO_ADMIN');
}

// ——— Catálogos ———
function accion_catalogos(): void {
    requireAdmin();
    $db = getDBConnection();
    $cntTT = (int)$db->query('SELECT COUNT(*) FROM tipos_tramite')->fetchColumn();
    $cntTD = (int)$db->query('SELECT COUNT(*) FROM tipos_documento')->fetchColumn();
    $cntRD = (int)$db->query('SELECT COUNT(*) FROM requisitos_documentales')->fetchColumn();
    renderAdmin('catalogos', ['titulo'=>'Catálogos','accion_activa'=>'catalogos','cntTiposTramite'=>$cntTT,'cntTiposDoc'=>$cntTD,'cntRequisitos'=>$cntRD]);
}

function accion_tipos_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    $tiposTramite = $db->query('SELECT *, (SELECT COUNT(*) FROM requisitos_documentales rd WHERE rd.tipo_tramite_id=tt.id) AS total_requisitos FROM tipos_tramite tt ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('tipos_tramite', ['titulo'=>'Tipos de Trámite','accion_activa'=>'tipos_tramite','tiposTramite'=>$tiposTramite]);
}

function accion_nuevo_tipo_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $clave = strtoupper(sanitizarTexto($_POST['clave'] ?? ''));
        $nombre = sanitizarTexto($_POST['nombre'] ?? '');
        $desc = sanitizarTexto($_POST['descripcion'] ?? '');
        $dep = sanitizarTexto($_POST['dependencia'] ?? '');
        $dias = sanitizarEntero($_POST['dias_resolucion'] ?? 15);
        if (estaVacio($clave) || estaVacio($nombre)) { setFlash('error','Clave y nombre son requeridos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_tipo_tramite'); exit; }
        try {
            $db->prepare('INSERT INTO tipos_tramite (clave, nombre, descripcion, dependencia, dias_resolucion) VALUES (:c,:n,:d,:dep,:dias)')->execute([':c'=>$clave,':n'=>$nombre,':d'=>$desc,':dep'=>$dep,':dias'=>$dias]);
            setFlash('exito','Tipo de trámite creado.');
        } catch (Exception $e) { error_log('Error nuevo_tipo_tramite: ' . $e->getMessage()); setFlash('error', 'No fue posible crear el tipo de tramite. Intente nuevamente.'); }
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_tramite'); exit;
    }
    renderAdmin('form_tipo_tramite', ['titulo'=>'Nuevo Tipo de Trámite','accion_activa'=>'tipos_tramite','tipoTramite'=>null]);
}

function accion_editar_tipo_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    $id = sanitizarEntero($_GET['id'] ?? null);
    if (!$id) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_tramite'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $nombre = sanitizarTexto($_POST['nombre'] ?? '');
        $desc = sanitizarTexto($_POST['descripcion'] ?? '');
        $dep = sanitizarTexto($_POST['dependencia'] ?? '');
        $dias = sanitizarEntero($_POST['dias_resolucion'] ?? 15);
        $activo = isset($_POST['activo']) ? 'TRUE' : 'FALSE';
        $db->prepare('UPDATE tipos_tramite SET nombre=:n, descripcion=:d, dependencia=:dep, dias_resolucion=:dias, activo=:act WHERE id=:id')->execute([':n'=>$nombre,':d'=>$desc,':dep'=>$dep,':dias'=>$dias,':act'=>$activo,':id'=>$id]);
        setFlash('exito','Tipo de trámite actualizado.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_tramite'); exit;
    }
    $stmt = $db->prepare('SELECT * FROM tipos_tramite WHERE id=:id'); $stmt->execute([':id'=>$id]);
    $tipoTramite = $stmt->fetch(PDO::FETCH_ASSOC);
    renderAdmin('form_tipo_tramite', ['titulo'=>'Editar Tipo de Trámite','accion_activa'=>'tipos_tramite','tipoTramite'=>$tipoTramite]);
}

function accion_tipos_documento(): void {
    requireAdmin();
    $db = getDBConnection();
    $tiposDoc = $db->query('SELECT * FROM tipos_documento ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('tipos_documento', ['titulo'=>'Tipos de Documento','accion_activa'=>'tipos_documento','tiposDoc'=>$tiposDoc]);
}

function accion_nuevo_tipo_documento(): void {
    requireAdmin();
    $db = getDBConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $clave = strtoupper(sanitizarTexto($_POST['clave'] ?? ''));
        $nombre = sanitizarTexto($_POST['nombre'] ?? '');
        $desc = sanitizarTexto($_POST['descripcion'] ?? '');
        $reutilizable = isset($_POST['reutilizable']) ? 'TRUE' : 'FALSE';
        $req_vig = isset($_POST['requiere_vigencia']) ? 'TRUE' : 'FALSE';
        $meses = sanitizarEntero($_POST['meses_vigencia'] ?? null);
        $exts = sanitizarTexto($_POST['extensiones_permitidas'] ?? 'pdf,jpg,jpeg,png');
        if (estaVacio($clave) || estaVacio($nombre)) { setFlash('error','Clave y nombre son requeridos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nuevo_tipo_documento'); exit; }
        try {
            $db->prepare('INSERT INTO tipos_documento (clave,nombre,descripcion,reutilizable,requiere_vigencia,meses_vigencia,extensiones_permitidas) VALUES (:c,:n,:d,:r,:rv,:mv,:ext)')->execute([':c'=>$clave,':n'=>$nombre,':d'=>$desc,':r'=>$reutilizable,':rv'=>$req_vig,':mv'=>$meses,':ext'=>$exts]);
            setFlash('exito','Tipo de documento creado.');
        } catch (Exception $e) { error_log('Error nuevo_tipo_documento: ' . $e->getMessage()); setFlash('error', 'No fue posible crear el tipo de documento. Intente nuevamente.'); }
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_documento'); exit;
    }
    renderAdmin('form_tipo_documento', ['titulo'=>'Nuevo Tipo de Documento','accion_activa'=>'tipos_documento','tipoDoc'=>null]);
}

function accion_editar_tipo_documento(): void {
    requireAdmin();
    $db = getDBConnection();
    $id = sanitizarEntero($_GET['id'] ?? null);
    if (!$id) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_documento'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $nombre = sanitizarTexto($_POST['nombre'] ?? '');
        $desc = sanitizarTexto($_POST['descripcion'] ?? '');
        $reutilizable = isset($_POST['reutilizable']) ? 'TRUE' : 'FALSE';
        $req_vig = isset($_POST['requiere_vigencia']) ? 'TRUE' : 'FALSE';
        $meses = sanitizarEntero($_POST['meses_vigencia'] ?? null);
        $exts = sanitizarTexto($_POST['extensiones_permitidas'] ?? 'pdf,jpg,jpeg,png');
        $activo = isset($_POST['activo']) ? 'TRUE' : 'FALSE';
        $db->prepare('UPDATE tipos_documento SET nombre=:n,descripcion=:d,reutilizable=:r,requiere_vigencia=:rv,meses_vigencia=:mv,extensiones_permitidas=:ext,activo=:act WHERE id=:id')->execute([':n'=>$nombre,':d'=>$desc,':r'=>$reutilizable,':rv'=>$req_vig,':mv'=>$meses,':ext'=>$exts,':act'=>$activo,':id'=>$id]);
        setFlash('exito','Tipo de documento actualizado.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=tipos_documento'); exit;
    }
    $stmt = $db->prepare('SELECT * FROM tipos_documento WHERE id=:id'); $stmt->execute([':id'=>$id]);
    $tipoDoc = $stmt->fetch(PDO::FETCH_ASSOC);
    renderAdmin('form_tipo_documento', ['titulo'=>'Editar Tipo de Documento','accion_activa'=>'tipos_documento','tipoDoc'=>$tipoDoc]);
}

function accion_requisitos(): void {
    requireAdmin();
    $db = getDBConnection();
    $tipoTId = sanitizarEntero($_GET['tipo_tramite_id'] ?? null);
    $tiposTramite = $db->query('SELECT * FROM tipos_tramite WHERE activo=TRUE ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    $requisitos = [];
    if ($tipoTId) {
        $stmt = $db->prepare('SELECT rd.*, td.nombre AS tipo_doc_nombre, tt.nombre AS tipo_tramite_nombre FROM requisitos_documentales rd JOIN tipos_documento td ON td.id=rd.tipo_documento_id JOIN tipos_tramite tt ON tt.id=rd.tipo_tramite_id WHERE rd.tipo_tramite_id=:ttid ORDER BY rd.orden_visualizacion ASC');
        $stmt->execute([':ttid'=>$tipoTId]);
        $requisitos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    renderAdmin('requisitos', ['titulo'=>'Requisitos Documentales','accion_activa'=>'requisitos','tiposTramite'=>$tiposTramite,'requisitos'=>$requisitos,'tipoTramiteSeleccionado'=>$tipoTId]);
}

function accion_nuevo_requisito(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=requisitos'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $ttid = sanitizarEntero($_POST['tipo_tramite_id'] ?? null);
    $tdid = sanitizarEntero($_POST['tipo_documento_id'] ?? null);
    $oblig = isset($_POST['obligatorio']) ? 'TRUE' : 'FALSE';
    $espec = isset($_POST['especifico_tramite']) ? 'TRUE' : 'FALSE';
    $excl  = isset($_POST['exclusivo_expediente']) ? 'TRUE' : 'FALSE';
    $orden = sanitizarEntero($_POST['orden_visualizacion'] ?? 0);
    $desc  = sanitizarTexto($_POST['descripcion_instruccion'] ?? '');
    if (!$ttid || !$tdid) { setFlash('error','Datos incompletos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=requisitos&tipo_tramite_id=' . $ttid); exit; }
    try {
        $db->prepare('INSERT INTO requisitos_documentales (tipo_tramite_id, tipo_documento_id, obligatorio, especifico_tramite, exclusivo_expediente, descripcion_instruccion, orden_visualizacion) VALUES (:ttid,:tdid,:oblig,:espec,:excl,:desc,:orden)')->execute([':ttid'=>$ttid,':tdid'=>$tdid,':oblig'=>$oblig,':espec'=>$espec,':excl'=>$excl,':desc'=>$desc,':orden'=>$orden]);
        setFlash('exito','Requisito agregado.');
    } catch (Exception $e) { error_log('Error nuevo_requisito: ' . $e->getMessage()); setFlash('error', 'No fue posible agregar el requisito. Intente nuevamente.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=requisitos&tipo_tramite_id=' . $ttid); exit;
}

function accion_eliminar_requisito(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=requisitos'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $id = sanitizarEntero($_POST['id'] ?? null);
    $ttid = sanitizarEntero($_POST['tipo_tramite_id'] ?? null);
    if ($id) { $db->prepare('DELETE FROM requisitos_documentales WHERE id=:id')->execute([':id'=>$id]); setFlash('exito','Requisito eliminado.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=requisitos&tipo_tramite_id=' . $ttid); exit;
}

// ——— Fiscal ———
function accion_fiscal(): void {
    requireAdmin();
    $db = getDBConnection();
    $stmt = $db->query('SELECT * FROM v_saldo_contribuyente ORDER BY saldo_pendiente DESC LIMIT 20');
    $saldos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $periodos = $db->query('SELECT * FROM periodos_fiscales ORDER BY anio DESC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('fiscal', ['titulo'=>'Gestión Fiscal','accion_activa'=>'fiscal','saldos'=>$saldos,'periodos'=>$periodos]);
}

function accion_pagos(): void {
    requireAdmin();
    $db = getDBConnection();
    $stmt = $db->query('SELECT pg.*, c.nombre_completo AS contrib_nombre, c.rfc FROM pagos pg JOIN contribuyentes c ON c.id=pg.contribuyente_id ORDER BY pg.fecha DESC LIMIT 50');
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('pagos', ['titulo'=>'Pagos','accion_activa'=>'pagos','pagos'=>$pagos]);
}

// ——— Inspecciones ———
function accion_inspecciones(): void {
    requireAdmin();
    $db = getDBConnection();
    $stmt = $db->query('SELECT i.*, e.numero_expediente, c.nombre_completo AS contrib_nombre, u.nombre_completo AS inspector_nombre FROM inspecciones i JOIN expedientes e ON e.id=i.expediente_id JOIN contribuyentes c ON c.id=e.contribuyente_id LEFT JOIN usuarios u ON u.id=i.inspector_id ORDER BY i.created_at DESC');
    $inspecciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('inspecciones', ['titulo'=>'Inspecciones','accion_activa'=>'inspecciones','inspecciones'=>$inspecciones]);
}

function accion_nueva_inspeccion(): void {
    requireAdmin();
    $db = getDBConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $eid = sanitizarEntero($_POST['expediente_id'] ?? null);
        $tipo = sanitizarTexto($_POST['tipo_inspeccion'] ?? '');
        $fecha = sanitizarTexto($_POST['programada_para'] ?? '');
        $inspId = sanitizarEntero($_POST['inspector_id'] ?? null);
        if (!$eid || estaVacio($tipo)) { setFlash('error','Expediente y tipo de inspección son requeridos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=nueva_inspeccion'); exit; }
        $db->prepare('INSERT INTO inspecciones (expediente_id, tipo, programada_para, inspector_id) VALUES (:eid,:tipo,:fecha,:ins)')->execute([':eid'=>$eid,':tipo'=>$tipo,':fecha'=>$fecha?:null,':ins'=>$inspId]);
        setFlash('exito','Inspección programada.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=inspecciones'); exit;
    }
    $expedientes = $db->query("SELECT e.id, e.numero_expediente, c.nombre_completo AS contrib FROM expedientes e JOIN contribuyentes c ON c.id=e.contribuyente_id WHERE e.estado NOT IN ('cerrado','cancelado') ORDER BY e.numero_expediente DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    $inspectores = $db->query("SELECT id, nombre_completo FROM usuarios WHERE rol IN ('admin','revisor') AND activo=TRUE ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('form_inspeccion', ['titulo'=>'Nueva Inspección','accion_activa'=>'inspecciones','expedientes'=>$expedientes,'inspectores'=>$inspectores]);
}

function accion_editar_inspeccion(): void {
    requireAdmin();
    $db = getDBConnection();
    $id = sanitizarEntero($_GET['id'] ?? null);
    if (!$id) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=inspecciones'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCSRF();
        $estado = sanitizarTexto($_POST['estado'] ?? '');
        $obs = sanitizarTexto($_POST['observaciones'] ?? '');
        $realizada = sanitizarTexto($_POST['realizada_en'] ?? '');
        $estados = ['pendiente','programada','realizada','cancelada'];
        if (!in_array($estado, $estados)) { setFlash('error','Estado inválido.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=editar_inspeccion&id='.$id); exit; }
        $db->prepare('UPDATE inspecciones SET estado=:est, observaciones=:obs, realizada_en=:re WHERE id=:id')->execute([':est'=>$estado,':obs'=>$obs,':re'=>$realizada?:null,':id'=>$id]);
        setFlash('exito','Inspección actualizada.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=inspecciones'); exit;
    }
    $stmt = $db->prepare('SELECT i.*, e.numero_expediente FROM inspecciones i JOIN expedientes e ON e.id=i.expediente_id WHERE i.id=:id');
    $stmt->execute([':id'=>$id]);
    $inspeccion = $stmt->fetch(PDO::FETCH_ASSOC);
    renderAdmin('form_inspeccion_editar', ['titulo'=>'Editar Inspección','accion_activa'=>'inspecciones','inspeccion'=>$inspeccion]);
}

function accion_ver_inspeccion(): void {
    accion_editar_inspeccion();
}

// Historial y dependencias
function accion_historial(): void {
    requireAdmin();
    $db = getDBConnection();
    $eid = sanitizarEntero($_GET['expediente_id'] ?? null);
    if (!$eid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=expedientes'); exit; }
    $stmt = $db->prepare('SELECT h.*, u.nombre_completo FROM historial_expediente h LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE h.expediente_id=:eid ORDER BY h.fecha DESC');
    $stmt->execute([':eid'=>$eid]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmtExp = $db->prepare('SELECT numero_expediente FROM expedientes WHERE id=:eid'); $stmtExp->execute([':eid'=>$eid]);
    $numExp = $stmtExp->fetchColumn();
    renderAdmin('historial', ['titulo'=>'Historial Expediente '.$numExp,'accion_activa'=>'expedientes','historial'=>$historial,'expedienteId'=>$eid]);
}

function accion_dependencias_tramite(): void {
    requireAdmin();
    $db = getDBConnection();
    $tid = sanitizarEntero($_GET['tramite_id'] ?? null);
    if (!$tid) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    $stmtT = $db->prepare('SELECT t.*, tt.nombre AS tipo_nombre, e.numero_expediente FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id JOIN expedientes e ON e.id=t.expediente_id WHERE t.id=:tid');
    $stmtT->execute([':tid'=>$tid]);
    $tramite = $stmtT->fetch(PDO::FETCH_ASSOC);
    if (!$tramite) { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    $stmtDeps = $db->prepare('SELECT td.*, t2.numero_tramite, tt.nombre AS tipo_dep_nombre FROM tramite_dependencias td JOIN tramites t2 ON t2.id=td.depende_de_tramite_id JOIN tipos_tramite tt ON tt.id=t2.tipo_tramite_id WHERE td.tramite_id=:tid');
    $stmtDeps->execute([':tid'=>$tid]);
    $dependencias = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
    $candidatos = $db->prepare("SELECT t.id, t.numero_tramite, tt.nombre AS tipo_nombre, e.numero_expediente FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id JOIN expedientes e ON e.id=t.expediente_id WHERE t.id<>:tid AND t.estado='aprobado' ORDER BY t.numero_tramite DESC");
    $candidatos->execute([':tid'=>$tid]);
    $tramitesCandidatos = $candidatos->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('dependencias_tramite', ['titulo'=>'Dependencias del Trámite','accion_activa'=>'tramites','tramite'=>$tramite,'dependencias'=>$dependencias,'tramitesCandidatos'=>$tramitesCandidatos]);
}

function accion_nueva_dependencia(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $tid = sanitizarEntero($_POST['tramite_id'] ?? null);
    $depId = sanitizarEntero($_POST['depende_de_tramite_id'] ?? null);
    if (!$tid || !$depId) { setFlash('error','Datos incompletos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=dependencias_tramite&tramite_id='.$tid); exit; }
    $reqService = new RequisitosService($db);
    if ($reqService->tieneCiclo($tid, $depId)) { setFlash('error','No se puede crear esta dependencia: generaría un ciclo.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=dependencias_tramite&tramite_id='.$tid); exit; }
    try {
        $db->prepare('INSERT INTO tramite_dependencias (tramite_id, depende_de_tramite_id) VALUES (:tid,:dep)')->execute([':tid'=>$tid,':dep'=>$depId]);
        setFlash('exito','Dependencia agregada.');
    } catch (Exception $e) { error_log('Error nueva_dependencia: ' . $e->getMessage()); setFlash('error', 'No fue posible agregar la dependencia. Intente nuevamente.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=dependencias_tramite&tramite_id='.$tid); exit;
}

function accion_eliminar_dependencia(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=tramites'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $id = sanitizarEntero($_POST['id'] ?? null);
    $tid = sanitizarEntero($_POST['tramite_id'] ?? null);
    if ($id) { $db->prepare('DELETE FROM tramite_dependencias WHERE id=:id')->execute([':id'=>$id]); setFlash('exito','Dependencia eliminada.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=dependencias_tramite&tramite_id='.$tid); exit;
}

function accion_obligaciones(): void {
    requireAdmin();
    $db = getDBConnection();
    $cid = sanitizarEntero($_GET['contribuyente_id'] ?? null);
    $fiscal = new FiscalService($db);
    if ($cid) {
        $obligaciones = $fiscal->getObligacionesContribuyente($cid);
        $stmtC = $db->prepare('SELECT nombre_completo AS nombre FROM contribuyentes WHERE id=:cid');
        $stmtC->execute([':cid'=>$cid]);
        $contrib = $stmtC->fetchColumn();
    } else {
        $obligaciones = [];
        $contrib = null;
    }
    $contribs = $db->query('SELECT id, nombre_completo AS nombre FROM contribuyentes ORDER BY nombre_completo ASC')->fetchAll(PDO::FETCH_ASSOC);
    renderAdmin('obligaciones', ['titulo'=>'Obligaciones Fiscales','accion_activa'=>'fiscal','obligaciones'=>$obligaciones,'contribs'=>$contribs,'contribSeleccionado'=>$cid,'contribNombre'=>$contrib]);
}

function accion_nueva_obligacion(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=obligaciones'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $cid = sanitizarEntero($_POST['contribuyente_id'] ?? null);
    $pid = sanitizarEntero($_POST['predio_id'] ?? null);
    $tipo = sanitizarTexto($_POST['tipo'] ?? '');
    $desc = sanitizarTexto($_POST['descripcion'] ?? '');
    $monto = sanitizarDecimal($_POST['monto_base'] ?? 0);
    if (!$cid || estaVacio($tipo)) { setFlash('error','Datos incompletos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=obligaciones'); exit; }
    $db->prepare('INSERT INTO obligaciones_fiscales (contribuyente_id, predio_id, tipo, descripcion, monto_base) VALUES (:cid,:pid,:tipo,:desc,:monto)')->execute([':cid'=>$cid,':pid'=>$pid,':tipo'=>$tipo,':desc'=>$desc,':monto'=>$monto]);
    setFlash('exito','Obligación fiscal creada.');
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=obligaciones&contribuyente_id='.$cid); exit;
}

function accion_nuevo_pago(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $cid = sanitizarEntero($_POST['contribuyente_id'] ?? null);
    $monto = sanitizarDecimal($_POST['monto'] ?? 0);
    $metodo = sanitizarTexto($_POST['metodo_pago'] ?? 'efectivo');
    $ref = sanitizarTexto($_POST['referencia'] ?? '');
    if (!$cid || $monto <= 0) { setFlash('error','Datos inválidos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }

    // Proteccion contra doble submit
    $submitKey = 'admin_pago_' . md5($cid . '_' . $monto . '_' . $metodo . '_' . $ref);
    if (isset($_SESSION['_last_admin_pago_key']) && $_SESSION['_last_admin_pago_key'] === $submitKey
        && isset($_SESSION['_last_admin_pago_time']) && (time() - $_SESSION['_last_admin_pago_time']) < 30) {
        setFlash('advertencia', 'Este pago ya fue procesado. Verifique en el listado.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit;
    }

    $db->prepare('INSERT INTO pagos (contribuyente_id, monto, referencia, metodo_pago) VALUES (:cid,:monto,:ref,:met)')->execute([':cid'=>$cid,':monto'=>$monto,':ref'=>$ref?:null,':met'=>$metodo]);
    $_SESSION['_last_admin_pago_key'] = $submitKey;
    $_SESSION['_last_admin_pago_time'] = time();
    logPago('PAGO_REGISTRADO_ADMIN', ['contribuyente_id' => $cid, 'monto' => $monto, 'metodo' => $metodo]);
    setFlash('exito','Pago registrado.');
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit;
}

function accion_aplicar_pago(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $pagoId = sanitizarEntero($_POST['pago_id'] ?? null);
    $opIds = $_POST['obligacion_periodo_ids'] ?? [];
    if (!$pagoId || empty($opIds)) { setFlash('error','Datos incompletos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }

    // Proteccion contra doble submit
    $sortedIds = array_map('intval', $opIds);
    sort($sortedIds);
    $applyKey = 'admin_apply_' . md5($pagoId . '_' . implode(',', $sortedIds));
    if (isset($_SESSION['_last_admin_apply_key']) && $_SESSION['_last_admin_apply_key'] === $applyKey
        && isset($_SESSION['_last_admin_apply_time']) && (time() - $_SESSION['_last_admin_apply_time']) < 30) {
        setFlash('advertencia', 'Esta aplicación de pago ya fue procesada.');
        header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit;
    }

    // Obtener contribuyente del pago
    $stmtPago = $db->prepare('SELECT contribuyente_id FROM pagos WHERE id = :pid');
    $stmtPago->execute([':pid' => $pagoId]);
    $cidPago = $stmtPago->fetchColumn();
    if (!$cidPago) { setFlash('error', 'Pago no encontrado.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }

    // Castear y validar IDs
    $opIds = array_filter(array_map('intval', $opIds), fn($v) => $v > 0);
    if (empty($opIds)) { setFlash('error', 'Obligaciones no válidas.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit; }

    $db->beginTransaction();
    try {
        foreach ($opIds as $opId) {
            // Verificar que la obligación pertenece al contribuyente del pago
            $stmtOwner = $db->prepare('
                SELECT op.id FROM obligacion_periodo op
                JOIN obligaciones_fiscales of2 ON of2.id = op.obligacion_id
                WHERE op.id = :opid AND of2.contribuyente_id = :cid AND op.pagado = FALSE
            ');
            $stmtOwner->execute([':opid' => $opId, ':cid' => $cidPago]);
            if (!$stmtOwner->fetchColumn()) {
                logIDOR('IDOR_APLICAR_PAGO_ADMIN', ['pago_id' => $pagoId, 'op_id_rechazado' => $opId]);
                throw new RuntimeException('Obligación no válida o no pertenece al contribuyente del pago.');
            }
            $stmt = $db->prepare('SELECT monto + monto_recargos - monto_pagado AS pendiente FROM obligacion_periodo WHERE id=:id');
            $stmt->execute([':id'=>$opId]);
            $pendiente = (float)$stmt->fetchColumn();
            $db->prepare('INSERT INTO pago_aplicacion (pago_id, obligacion_periodo_id, monto_aplicado) VALUES (:pid,:opid,:monto) ON CONFLICT DO NOTHING')->execute([':pid'=>$pagoId,':opid'=>$opId,':monto'=>$pendiente]);
            $db->prepare('UPDATE obligacion_periodo SET pagado=TRUE, monto_pagado=monto+monto_recargos WHERE id=:id')->execute([':id'=>$opId]);
        }
        $db->commit();
        $_SESSION['_last_admin_apply_key'] = $applyKey;
        $_SESSION['_last_admin_apply_time'] = time();
        logPago('PAGO_APLICADO_ADMIN', ['pago_id' => $pagoId, 'obligaciones' => array_values($opIds)]);
        setFlash('exito','Pago aplicado.');
    } catch (Exception $e) { $db->rollBack(); error_log('Error aplicar_pago: ' . $e->getMessage()); setFlash('error', 'No fue posible aplicar el pago. Intente nuevamente.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=pagos'); exit;
}

function accion_nuevo_periodo(): void {
    requireAdmin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/?modulo=admin&accion=fiscal'); exit; }
    verificarCSRF();
    $db = getDBConnection();
    $anio = sanitizarEntero($_POST['anio'] ?? null);
    $desc = sanitizarTexto($_POST['descripcion'] ?? '');
    $fi = sanitizarTexto($_POST['fecha_inicio'] ?? '');
    $ff = sanitizarTexto($_POST['fecha_fin'] ?? '');
    if (!$anio || !validarFecha($fi) || !validarFecha($ff)) { setFlash('error','Datos inválidos.'); header('Location: ' . BASE_URL . '/?modulo=admin&accion=fiscal'); exit; }
    try {
        $db->prepare('INSERT INTO periodos_fiscales (anio, descripcion, fecha_inicio, fecha_fin) VALUES (:anio,:desc,:fi,:ff)')->execute([':anio'=>$anio,':desc'=>$desc,':fi'=>$fi,':ff'=>$ff]);
        setFlash('exito','Período fiscal creado.');
    } catch (Exception $e) { error_log('Error nuevo_periodo: ' . $e->getMessage()); setFlash('error', 'No fue posible crear el periodo fiscal. Intente nuevamente.'); }
    header('Location: ' . BASE_URL . '/?modulo=admin&accion=fiscal'); exit;
}

function accion_reportes(): void {
    requireAdmin();
    $db = getDBConnection();
    $stats = [
        'expedientes_por_estado' => $db->query("SELECT estado, COUNT(*) AS total FROM expedientes GROUP BY estado ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC),
        'tramites_por_tipo'      => $db->query("SELECT tt.nombre, COUNT(t.id) AS total FROM tramites t JOIN tipos_tramite tt ON tt.id=t.tipo_tramite_id GROUP BY tt.nombre ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC),
        'documentos_por_estado'  => $db->query("SELECT estado_validacion, COUNT(*) AS total FROM documentos WHERE es_version_activa=TRUE GROUP BY estado_validacion ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC),
    ];
    renderAdmin('reportes', ['titulo'=>'Reportes','accion_activa'=>'reportes','stats'=>$stats]);
}


