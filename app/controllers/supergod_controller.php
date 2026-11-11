<?php
/**
 * Controlador Supergod — Consola de Monitoreo del Sistema
 * Acceso EXCLUSIVO para rol supergod.
 */

function accion_consola(): void {
    requireLogin();
    if (!isSuperGod()) {
        logAccesoDenegado('SUPERGOD_CONSOLA_DENEGADO', ['rol_actual' => getRolActual()]);
        header('Location: ' . BASE_URL . '/?modulo=auth&accion=acceso_denegado');
        exit;
    }

    logAuth('SUPERGOD_CONSOLE_ACCESS', ['accion' => 'consola']);

    $db = getDBConnection();

    // Estadísticas rápidas para el header de la consola
    $stats = obtenerEstadisticasConsola($db);

    require APP_PATH . '/views/supergod/consola.php';
}

function accion_consola_api(): void {
    requireLogin();
    if (!isSuperGod()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    $db = getDBConnection();

    // Parámetros de filtrado y paginación
    $pagina     = max(1, (int)($_GET['pagina'] ?? 1));
    $porPagina  = min(200, max(10, (int)($_GET['por_pagina'] ?? 50)));
    $nivel      = sanitizarTexto($_GET['nivel'] ?? '');
    $categoria  = sanitizarTexto($_GET['categoria'] ?? '');
    $evento     = sanitizarTexto($_GET['evento'] ?? '');
    $busqueda   = sanitizarTexto($_GET['q'] ?? '');
    $usuarioId  = ($_GET['usuario_id'] ?? '') !== '' ? (int)$_GET['usuario_id'] : null;
    $ip         = sanitizarTexto($_GET['ip'] ?? '');
    $fechaDesde = sanitizarTexto($_GET['desde'] ?? '');
    $fechaHasta = sanitizarTexto($_GET['hasta'] ?? '');
    $modulo     = sanitizarTexto($_GET['modulo'] ?? '');

    // Construir query con filtros
    $where = [];
    $params = [];

    if ($nivel !== '') {
        $where[] = 'nivel = :nivel';
        $params[':nivel'] = strtoupper($nivel);
    }
    if ($categoria !== '') {
        $where[] = 'categoria = :categoria';
        $params[':categoria'] = strtoupper($categoria);
    }
    if ($evento !== '') {
        $where[] = 'evento ILIKE :evento';
        $params[':evento'] = '%' . $evento . '%';
    }
    if ($busqueda !== '') {
        $where[] = '(descripcion ILIKE :q OR evento ILIKE :q2 OR usuario_email ILIKE :q3 OR ip ILIKE :q4)';
        $params[':q'] = '%' . $busqueda . '%';
        $params[':q2'] = '%' . $busqueda . '%';
        $params[':q3'] = '%' . $busqueda . '%';
        $params[':q4'] = '%' . $busqueda . '%';
    }
    if ($usuarioId !== null) {
        $where[] = 'usuario_id = :usuario_id';
        $params[':usuario_id'] = $usuarioId;
    }
    if ($ip !== '') {
        $where[] = 'ip = :ip';
        $params[':ip'] = $ip;
    }
    if ($fechaDesde !== '') {
        $where[] = 'created_at >= :desde';
        $params[':desde'] = $fechaDesde . ' 00:00:00';
    }
    if ($fechaHasta !== '') {
        $where[] = 'created_at <= :hasta';
        $params[':hasta'] = $fechaHasta . ' 23:59:59';
    }
    if ($modulo !== '') {
        $where[] = 'modulo = :modulo';
        $params[':modulo'] = $modulo;
    }

    $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $offset = ($pagina - 1) * $porPagina;

    // Contar total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM system_events {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Obtener eventos
    $sql = "SELECT id, created_at, nivel, categoria, evento, descripcion, ip, usuario_id, usuario_email, rol, modulo, accion, metodo_http, ruta, user_agent, contexto
            FROM system_events {$whereSQL}
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $eventos = $stmt->fetchAll();

    // Decodificar contexto JSONB
    foreach ($eventos as &$ev) {
        $ev['contexto'] = json_decode($ev['contexto'] ?? '{}', true);
        // Sanitizar cualquier dato sensible residual en contexto
        if (is_array($ev['contexto'])) {
            $ev['contexto'] = sanitizarContextoLog($ev['contexto']);
        }
    }
    unset($ev);

    echo json_encode([
        'eventos'    => $eventos,
        'total'      => $total,
        'pagina'     => $pagina,
        'por_pagina' => $porPagina,
        'paginas'    => (int)ceil($total / $porPagina),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerEstadisticasConsola(PDO $db): array {
    $stats = [];

    // Total eventos últimas 24h
    $stmt = $db->query("SELECT COUNT(*) FROM system_events WHERE created_at >= NOW() - INTERVAL '24 hours'");
    $stats['total_24h'] = (int)$stmt->fetchColumn();

    // Por nivel últimas 24h
    $stmt = $db->query("SELECT nivel, COUNT(*) as total FROM system_events WHERE created_at >= NOW() - INTERVAL '24 hours' GROUP BY nivel ORDER BY total DESC");
    $stats['por_nivel'] = $stmt->fetchAll();

    // Errores/Críticos últimas 24h
    $stmt = $db->query("SELECT COUNT(*) FROM system_events WHERE created_at >= NOW() - INTERVAL '24 hours' AND nivel IN ('ERROR', 'CRITICAL')");
    $stats['errores_24h'] = (int)$stmt->fetchColumn();

    // Logins fallidos ultimas 24h
    // Bug corregido: la precedencia AND/OR hacia que las condiciones LIKE '%FAILED%'
    // y LIKE '%INCORRECTO%' ignoraran el filtro temporal, inflando el KPI con
    // eventos historicos. Se agrupan con parentesis para aplicar la ventana de 24h
    // a todo el conjunto de disyunciones.
    $stmt = $db->query(
        "SELECT COUNT(*) FROM system_events
         WHERE created_at >= NOW() - INTERVAL '24 hours'
           AND categoria = 'AUTH'
           AND (
                evento LIKE '%FALLIDO%'
             OR evento LIKE '%FAILED%'
             OR evento LIKE '%INCORRECTO%'
           )"
    );
    $stats['logins_fallidos_24h'] = (int)$stmt->fetchColumn();

    // Total eventos
    $stmt = $db->query("SELECT COUNT(*) FROM system_events");
    $stats['total_general'] = (int)$stmt->fetchColumn();

    // Categorías distintas
    $stmt = $db->query("SELECT DISTINCT categoria FROM system_events ORDER BY categoria");
    $stats['categorias'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Módulos distintos
    $stmt = $db->query("SELECT DISTINCT modulo FROM system_events WHERE modulo IS NOT NULL ORDER BY modulo");
    $stats['modulos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $stats;
}
