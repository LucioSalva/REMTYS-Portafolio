<?php
/**
 * REMTYS System Console — Supergod Monitoring View
 * Dark terminal-style event viewer with real-time refresh
 */
if (!defined('ROOT_PATH')) { header('Location: /'); exit; }

$totalGeneral = (int)($stats['total_general'] ?? 0);
$total24h     = (int)($stats['total_24h'] ?? 0);
$errores24h   = (int)($stats['errores_24h'] ?? 0);
$loginsFall   = (int)($stats['logins_fallidos_24h'] ?? 0);
$categorias   = $stats['categorias'] ?? [];
$modulos      = $stats['modulos'] ?? [];
$porNivel     = $stats['por_nivel'] ?? [];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Console - <?= s(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#x1f6e1;</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css">
    <style nonce="<?= cspNonce() ?>">
        /* ========== RESET & BASE ========== */
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --bg-primary:   #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary:  #21262d;
            --bg-critical:  #3d1117;
            --border-color: #30363d;
            --border-light: #484f58;
            --text-primary:   #e6edf3;
            --text-secondary: #8b949e;
            --text-dimmed:    #484f58;
            --color-info:     #8b949e;
            --color-success:  #3fb950;
            --color-warning:  #d29922;
            --color-error:    #f85149;
            --color-critical: #ff7b72;
            --color-vino:     #691C32;
            --color-dorado:   #BC955C;
            --font-mono:  'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            --font-ui:    'Poppins', sans-serif;
            --topbar-h:   52px;
            --filters-h:  48px;
        }
        html, body {
            margin: 0; padding: 0;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-mono);
            font-size: 13px;
            line-height: 1.5;
            height: 100%;
            overflow: hidden;
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-light); }
        ::selection { background: rgba(188,149,92,0.3); }

        /* ========== TOP BAR ========== */
        .console-topbar {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--topbar-h);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 16px;
            z-index: 100;
            user-select: none;
        }
        .console-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--color-dorado);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .console-brand i { font-size: 16px; }
        .console-stats {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
        }
        .stat-badge .stat-val {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        .stat-badge--events  { color: var(--text-secondary); }
        .stat-badge--errors  { color: var(--color-error); border-color: rgba(248,81,73,0.3); }
        .stat-badge--logins  { color: var(--color-warning); border-color: rgba(210,153,34,0.3); }
        .stat-badge--total   { color: var(--color-dorado); border-color: rgba(188,149,92,0.3); }
        .console-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .console-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 10px;
            font-family: var(--font-mono);
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .console-btn:hover { color: var(--text-primary); border-color: var(--border-light); background: var(--border-color); }
        .console-btn--active { color: var(--color-success); border-color: rgba(63,185,80,0.4); }
        .console-btn--active:hover { color: var(--color-success); }
        .console-btn i { font-size: 13px; }
        .console-link {
            color: var(--text-dimmed);
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            transition: color 0.15s;
        }
        .console-link:hover { color: var(--text-secondary); }

        /* ========== FILTERS BAR ========== */
        .console-filters {
            position: fixed;
            top: var(--topbar-h);
            left: 0; right: 0;
            height: var(--filters-h);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            z-index: 99;
            overflow-x: auto;
        }
        .filter-control {
            background: var(--bg-primary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 8px;
            font-family: var(--font-mono);
            font-size: 11px;
            outline: none;
            transition: border-color 0.15s;
            min-width: 0;
        }
        .filter-control:focus { border-color: var(--color-dorado); color: var(--text-primary); }
        select.filter-control { padding-right: 24px; cursor: pointer; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238b949e'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
        }
        select.filter-control option { background: var(--bg-secondary); color: var(--text-primary); }
        input[type="date"].filter-control { width: 130px; color-scheme: dark; }
        input[type="text"].filter-control { width: 160px; }
        .filter-sep {
            width: 1px;
            height: 24px;
            background: var(--border-color);
            flex-shrink: 0;
        }
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 6px;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .filter-btn--apply {
            background: var(--color-dorado);
            color: #000;
            border-color: var(--color-dorado);
        }
        .filter-btn--apply:hover { background: #d4ad74; }
        .filter-btn--clear {
            background: transparent;
            color: var(--text-secondary);
            border-color: var(--border-color);
        }
        .filter-btn--clear:hover { color: var(--text-primary); border-color: var(--border-light); }

        /* ========== CONSOLE BODY ========== */
        .console-body {
            position: fixed;
            top: calc(var(--topbar-h) + var(--filters-h));
            left: 0; right: 0;
            bottom: 40px;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
            background: var(--bg-primary);
        }

        /* ========== LOG LINES ========== */
        .log-line {
            display: flex;
            align-items: flex-start;
            padding: 2px 16px 2px 0;
            cursor: pointer;
            transition: background 0.1s;
            border-bottom: 1px solid transparent;
            font-size: 12.5px;
            line-height: 1.65;
        }
        .log-line:hover { background: rgba(255,255,255,0.02); }
        .log-line--critical { background: var(--bg-critical); }
        .log-line--critical:hover { background: rgba(61,17,23,0.8); }
        .log-line--expanded { background: rgba(255,255,255,0.03); border-bottom-color: var(--border-color); }
        .log-linenum {
            width: 50px;
            min-width: 50px;
            text-align: right;
            padding-right: 12px;
            color: var(--text-dimmed);
            font-size: 11px;
            user-select: none;
            flex-shrink: 0;
            line-height: 1.65;
        }
        .log-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .log-ts { color: var(--text-dimmed); }
        .log-nivel { font-weight: 600; padding: 0 2px; border-radius: 2px; }
        .log-nivel--INFO     { color: var(--color-info); }
        .log-nivel--SUCCESS  { color: var(--color-success); }
        .log-nivel--WARNING  { color: var(--color-warning); }
        .log-nivel--ERROR    { color: var(--color-error); }
        .log-nivel--CRITICAL { color: var(--color-critical); font-weight: 700; }
        .log-cat { color: var(--color-dorado); }
        .log-evento { color: var(--text-primary); }
        .log-desc { color: var(--text-secondary); }
        .log-meta { color: var(--text-dimmed); }

        /* ========== EXPANDED DETAIL ========== */
        .log-detail {
            display: none;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 16px 12px 62px;
            font-size: 11.5px;
            line-height: 1.7;
        }
        .log-detail.open { display: block; }
        .log-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 4px 24px;
        }
        .log-detail-field { display: flex; gap: 8px; }
        .log-detail-label {
            color: var(--text-dimmed);
            min-width: 100px;
            flex-shrink: 0;
            text-align: right;
        }
        .log-detail-value { color: var(--text-primary); word-break: break-all; }
        .log-detail-context {
            margin-top: 8px;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 11px;
            color: var(--color-info);
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
        .log-detail-actions { margin-top: 8px; }
        .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 2px 8px;
            font-family: var(--font-mono);
            font-size: 10px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .copy-btn:hover { color: var(--text-primary); border-color: var(--border-light); }

        /* ========== STATUS MESSAGES ========== */
        .console-status {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            color: var(--text-dimmed);
            font-size: 13px;
            gap: 8px;
        }
        .console-status i { font-size: 16px; }
        .console-loading .spinner {
            width: 14px; height: 14px;
            border: 2px solid var(--border-color);
            border-top-color: var(--color-dorado);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ========== PAGINATION BAR ========== */
        .console-pagination {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 40px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            font-size: 11px;
            z-index: 99;
        }
        .pag-info { color: var(--text-dimmed); }
        .pag-nav {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .pag-btn {
            background: var(--bg-primary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 2px 8px;
            font-family: var(--font-mono);
            font-size: 11px;
            cursor: pointer;
            transition: all 0.15s;
            min-width: 28px;
            text-align: center;
        }
        .pag-btn:hover { color: var(--text-primary); border-color: var(--border-light); }
        .pag-btn--active { background: var(--color-dorado); color: #000; border-color: var(--color-dorado); font-weight: 600; }
        .pag-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .pag-live {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-dimmed);
        }
        .pag-live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--text-dimmed);
        }
        .pag-live-dot--on { background: var(--color-success); animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{ opacity:1; } 50%{ opacity:0.4; } }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .console-stats { display: none; }
            .console-brand span { display: none; }
            .console-filters { gap: 4px; padding: 0 8px; }
            .filter-control { font-size: 10px; padding: 3px 6px; }
            .log-linenum { width: 36px; min-width: 36px; padding-right: 8px; }
            .log-line { padding-right: 8px; font-size: 11px; }
            .log-detail { padding-left: 44px; }
        }
    </style>
</head>
<body>

<!-- ==================== TOP BAR ==================== -->
<header class="console-topbar">
    <div class="console-brand">
        <i class="bi bi-terminal-fill"></i>
        <span><?= s(APP_NAME) ?> SYSTEM CONSOLE</span>
    </div>

    <div class="console-stats">
        <span class="stat-badge stat-badge--events">
            <i class="bi bi-activity"></i> 24h
            <span class="stat-val" id="statTotal24h"><?= $total24h ?></span>
        </span>
        <span class="stat-badge stat-badge--errors">
            <i class="bi bi-exclamation-triangle"></i> Errores
            <span class="stat-val" id="statErrores24h"><?= $errores24h ?></span>
        </span>
        <span class="stat-badge stat-badge--logins">
            <i class="bi bi-shield-x"></i> Login fail
            <span class="stat-val" id="statLogins24h"><?= $loginsFall ?></span>
        </span>
        <span class="stat-badge stat-badge--total">
            <i class="bi bi-database"></i> Total
            <span class="stat-val" id="statTotalGeneral"><?= $totalGeneral ?></span>
        </span>
    </div>

    <div class="console-actions">
        <button class="console-btn console-btn--active" id="btnAutoRefresh" type="button"
                title="Auto-refresh cada 10s">
            <i class="bi bi-arrow-repeat"></i> Auto
        </button>
        <button class="console-btn" id="btnRefresh" type="button" title="Recargar ahora">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
        <button class="console-btn" id="btnClear" type="button" title="Limpiar consola">
            <i class="bi bi-x-circle"></i>
        </button>
        <a href="<?= BASE_URL ?>/?modulo=admin&accion=dashboard" class="console-link" title="Volver al panel">
            <i class="bi bi-box-arrow-left"></i>
        </a>
    </div>
</header>

<!-- ==================== FILTERS BAR ==================== -->
<div class="console-filters">
    <select class="filter-control" id="filterNivel" aria-label="Filtrar por nivel">
        <option value="">Nivel</option>
        <option value="INFO">INFO</option>
        <option value="SUCCESS">SUCCESS</option>
        <option value="WARNING">WARNING</option>
        <option value="ERROR">ERROR</option>
        <option value="CRITICAL">CRITICAL</option>
    </select>

    <select class="filter-control" id="filterCategoria" aria-label="Filtrar por categoria">
        <option value="">Categoria</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= s($cat) ?>"><?= s($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <select class="filter-control" id="filterModulo" aria-label="Filtrar por modulo">
        <option value="">Modulo</option>
        <?php foreach ($modulos as $mod): ?>
            <option value="<?= s($mod) ?>"><?= s($mod) ?></option>
        <?php endforeach; ?>
    </select>

    <div class="filter-sep"></div>

    <input type="text" class="filter-control" id="filterSearch" placeholder="Buscar..." aria-label="Buscar eventos">
    <input type="date" class="filter-control" id="filterDesde" aria-label="Desde fecha">
    <input type="date" class="filter-control" id="filterHasta" aria-label="Hasta fecha">

    <div class="filter-sep"></div>

    <button class="filter-btn filter-btn--apply" id="btnFiltrar" type="button">
        <i class="bi bi-funnel-fill"></i> Filtrar
    </button>
    <button class="filter-btn filter-btn--clear" id="btnLimpiar" type="button">
        <i class="bi bi-x-lg"></i> Limpiar
    </button>
</div>

<!-- ==================== CONSOLE BODY ==================== -->
<main class="console-body" id="consoleBody" role="log" aria-label="Consola de eventos del sistema">
    <!-- Events rendered by JS -->
</main>

<!-- ==================== PAGINATION ==================== -->
<footer class="console-pagination">
    <span class="pag-info" id="pagInfo">Cargando...</span>
    <div class="pag-live">
        <span class="pag-live-dot pag-live-dot--on" id="liveDot"></span>
        <span id="liveLabel">LIVE</span>
    </div>
    <nav class="pag-nav" id="pagNav" aria-label="Paginacion de eventos"></nav>
</footer>

<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script nonce="<?= cspNonce() ?>">
(function() {
    'use strict';

    /* ==================== CONFIG ==================== */
    var API_URL    = '<?= BASE_URL ?>/?modulo=supergod&accion=consola_api';
    var POR_PAGINA = 50;
    var REFRESH_MS = 10000;

    /* ==================== STATE ==================== */
    var state = {
        pagina: 1,
        total: 0,
        paginas: 0,
        autoRefresh: true,
        refreshTimer: null,
        loading: false,
        eventos: [],
        expandedId: null
    };

    /* ==================== DOM REFS ==================== */
    var $body       = document.getElementById('consoleBody');
    var $pagInfo    = document.getElementById('pagInfo');
    var $pagNav     = document.getElementById('pagNav');
    var $liveDot    = document.getElementById('liveDot');
    var $liveLabel  = document.getElementById('liveLabel');
    var $btnAuto    = document.getElementById('btnAutoRefresh');
    var $btnRefresh = document.getElementById('btnRefresh');
    var $btnClear   = document.getElementById('btnClear');
    var $btnFiltrar = document.getElementById('btnFiltrar');
    var $btnLimpiar = document.getElementById('btnLimpiar');
    var $fNivel     = document.getElementById('filterNivel');
    var $fCategoria = document.getElementById('filterCategoria');
    var $fModulo    = document.getElementById('filterModulo');
    var $fSearch    = document.getElementById('filterSearch');
    var $fDesde     = document.getElementById('filterDesde');
    var $fHasta     = document.getElementById('filterHasta');

    /* ==================== HELPERS ==================== */
    function esc(str) {
        if (str === null || str === undefined) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatTs(raw) {
        if (!raw) return '—';
        var d = new Date(raw);
        if (isNaN(d.getTime())) return esc(raw);
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
             + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }

    function shortDesc(desc, max) {
        if (!desc) return '';
        max = max || 80;
        if (desc.length <= max) return desc;
        return desc.substring(0, max) + '...';
    }

    function nivelClass(nivel) {
        return 'log-nivel--' + (nivel || 'INFO');
    }

    function buildQueryString() {
        var params = [];
        params.push('pagina=' + state.pagina);
        params.push('por_pagina=' + POR_PAGINA);
        if ($fNivel.value)     params.push('nivel=' + encodeURIComponent($fNivel.value));
        if ($fCategoria.value) params.push('categoria=' + encodeURIComponent($fCategoria.value));
        if ($fModulo.value)    params.push('modulo=' + encodeURIComponent($fModulo.value));
        if ($fSearch.value.trim())  params.push('q=' + encodeURIComponent($fSearch.value.trim()));
        if ($fDesde.value)     params.push('desde=' + encodeURIComponent($fDesde.value));
        if ($fHasta.value)     params.push('hasta=' + encodeURIComponent($fHasta.value));
        return params.join('&');
    }

    /* ==================== RENDERING ==================== */
    function renderLoading() {
        $body.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'console-status console-loading';
        var spinner = document.createElement('div');
        spinner.className = 'spinner';
        div.appendChild(spinner);
        var txt = document.createTextNode(' Consultando eventos...');
        div.appendChild(txt);
        $body.appendChild(div);
    }

    function renderEmpty() {
        $body.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'console-status';
        var icon = document.createElement('i');
        icon.className = 'bi bi-inbox';
        div.appendChild(icon);
        div.appendChild(document.createTextNode(' Sin eventos que mostrar'));
        $body.appendChild(div);
    }

    function renderEvents(eventos) {
        $body.innerHTML = '';
        if (!eventos || eventos.length === 0) {
            renderEmpty();
            return;
        }

        var startNum = ((state.pagina - 1) * POR_PAGINA) + 1;
        var frag = document.createDocumentFragment();

        for (var i = 0; i < eventos.length; i++) {
            var ev = eventos[i];
            var lineNum = startNum + i;

            /* --- Main log line --- */
            var line = document.createElement('div');
            line.className = 'log-line';
            if (ev.nivel === 'CRITICAL') line.className += ' log-line--critical';
            line.setAttribute('data-event-id', ev.id);
            line.setAttribute('role', 'button');
            line.setAttribute('tabindex', '0');
            line.setAttribute('aria-expanded', 'false');

            /* Line number */
            var numSpan = document.createElement('span');
            numSpan.className = 'log-linenum';
            numSpan.textContent = lineNum;
            line.appendChild(numSpan);

            /* Content */
            var content = document.createElement('span');
            content.className = 'log-content';

            var tsSpan = document.createElement('span');
            tsSpan.className = 'log-ts';
            tsSpan.textContent = '[' + formatTs(ev.created_at) + '] ';
            content.appendChild(tsSpan);

            var nivelSpan = document.createElement('span');
            nivelSpan.className = 'log-nivel ' + nivelClass(ev.nivel);
            nivelSpan.textContent = '[' + (ev.nivel || '?') + '] ';
            content.appendChild(nivelSpan);

            var catSpan = document.createElement('span');
            catSpan.className = 'log-cat';
            catSpan.textContent = '[' + (ev.categoria || '-') + '] ';
            content.appendChild(catSpan);

            var evSpan = document.createElement('span');
            evSpan.className = 'log-evento';
            evSpan.textContent = (ev.evento || '') + ' ';
            content.appendChild(evSpan);

            var descSpan = document.createElement('span');
            descSpan.className = 'log-desc';
            descSpan.textContent = '— ' + shortDesc(ev.descripcion, 60) + ' ';
            content.appendChild(descSpan);

            var metaSpan = document.createElement('span');
            metaSpan.className = 'log-meta';
            var metaParts = [];
            if (ev.ip) metaParts.push(ev.ip);
            if (ev.usuario_email) metaParts.push(ev.usuario_email);
            metaSpan.textContent = metaParts.length ? '| ' + metaParts.join(' | ') : '';
            content.appendChild(metaSpan);

            line.appendChild(content);
            frag.appendChild(line);

            /* --- Detail panel --- */
            var detail = document.createElement('div');
            detail.className = 'log-detail';
            detail.id = 'detail-' + ev.id;

            var grid = document.createElement('div');
            grid.className = 'log-detail-grid';

            var fields = [
                ['ID',        ev.id],
                ['Timestamp', formatTs(ev.created_at)],
                ['Nivel',     ev.nivel],
                ['Categoria', ev.categoria],
                ['Evento',    ev.evento],
                ['IP',        ev.ip],
                ['Usuario',   (ev.usuario_email || '—') + (ev.usuario_id ? ' (#' + ev.usuario_id + ')' : '')],
                ['Rol',       ev.rol],
                ['Modulo',    ev.modulo],
                ['Accion',    ev.accion],
                ['Metodo',    ev.metodo_http],
                ['Ruta',      ev.ruta],
                ['User Agent', ev.user_agent]
            ];

            for (var f = 0; f < fields.length; f++) {
                var fieldDiv = document.createElement('div');
                fieldDiv.className = 'log-detail-field';
                var label = document.createElement('span');
                label.className = 'log-detail-label';
                label.textContent = fields[f][0] + ':';
                var value = document.createElement('span');
                value.className = 'log-detail-value';
                value.textContent = fields[f][1] || '—';
                fieldDiv.appendChild(label);
                fieldDiv.appendChild(value);
                grid.appendChild(fieldDiv);
            }
            detail.appendChild(grid);

            /* Context JSON */
            if (ev.contexto && typeof ev.contexto === 'object' && Object.keys(ev.contexto).length > 0) {
                var ctxPre = document.createElement('pre');
                ctxPre.className = 'log-detail-context';
                ctxPre.textContent = JSON.stringify(ev.contexto, null, 2);
                detail.appendChild(ctxPre);
            }

            /* Description full */
            if (ev.descripcion && ev.descripcion.length > 60) {
                var descFull = document.createElement('div');
                descFull.className = 'log-detail-field';
                descFull.style.marginTop = '6px';
                var descLabel = document.createElement('span');
                descLabel.className = 'log-detail-label';
                descLabel.textContent = 'Descripcion:';
                var descVal = document.createElement('span');
                descVal.className = 'log-detail-value';
                descVal.textContent = ev.descripcion;
                descFull.appendChild(descLabel);
                descFull.appendChild(descVal);
                detail.appendChild(descFull);
            }

            /* Copy button */
            var actionsDiv = document.createElement('div');
            actionsDiv.className = 'log-detail-actions';
            var copyBtn = document.createElement('button');
            copyBtn.className = 'copy-btn';
            copyBtn.type = 'button';
            copyBtn.setAttribute('data-event-json', JSON.stringify(ev));
            var copyIcon = document.createElement('i');
            copyIcon.className = 'bi bi-clipboard';
            copyBtn.appendChild(copyIcon);
            copyBtn.appendChild(document.createTextNode(' Copiar'));
            actionsDiv.appendChild(copyBtn);
            detail.appendChild(actionsDiv);

            frag.appendChild(detail);
        }

        $body.appendChild(frag);
    }

    function renderPagination() {
        $pagNav.innerHTML = '';
        if (state.paginas <= 1) {
            $pagInfo.textContent = state.total + ' evento' + (state.total !== 1 ? 's' : '');
            return;
        }

        var from = ((state.pagina - 1) * POR_PAGINA) + 1;
        var to = Math.min(state.pagina * POR_PAGINA, state.total);
        $pagInfo.textContent = 'Mostrando ' + from + '-' + to + ' de ' + state.total + ' eventos';

        /* Prev */
        var prevBtn = document.createElement('button');
        prevBtn.className = 'pag-btn';
        prevBtn.type = 'button';
        prevBtn.textContent = '<';
        prevBtn.disabled = state.pagina <= 1;
        prevBtn.setAttribute('data-page', state.pagina - 1);
        $pagNav.appendChild(prevBtn);

        /* Page numbers — show max 7 centered on current */
        var maxVisible = 7;
        var startP = Math.max(1, state.pagina - Math.floor(maxVisible / 2));
        var endP = Math.min(state.paginas, startP + maxVisible - 1);
        if (endP - startP + 1 < maxVisible) {
            startP = Math.max(1, endP - maxVisible + 1);
        }

        if (startP > 1) {
            var firstBtn = document.createElement('button');
            firstBtn.className = 'pag-btn';
            firstBtn.type = 'button';
            firstBtn.textContent = '1';
            firstBtn.setAttribute('data-page', '1');
            $pagNav.appendChild(firstBtn);
            if (startP > 2) {
                var dots = document.createElement('span');
                dots.className = 'pag-btn';
                dots.style.border = 'none';
                dots.style.cursor = 'default';
                dots.textContent = '...';
                $pagNav.appendChild(dots);
            }
        }

        for (var p = startP; p <= endP; p++) {
            var pageBtn = document.createElement('button');
            pageBtn.className = 'pag-btn' + (p === state.pagina ? ' pag-btn--active' : '');
            pageBtn.type = 'button';
            pageBtn.textContent = p;
            pageBtn.setAttribute('data-page', p);
            $pagNav.appendChild(pageBtn);
        }

        if (endP < state.paginas) {
            if (endP < state.paginas - 1) {
                var dots2 = document.createElement('span');
                dots2.className = 'pag-btn';
                dots2.style.border = 'none';
                dots2.style.cursor = 'default';
                dots2.textContent = '...';
                $pagNav.appendChild(dots2);
            }
            var lastBtn = document.createElement('button');
            lastBtn.className = 'pag-btn';
            lastBtn.type = 'button';
            lastBtn.textContent = state.paginas;
            lastBtn.setAttribute('data-page', state.paginas);
            $pagNav.appendChild(lastBtn);
        }

        /* Next */
        var nextBtn = document.createElement('button');
        nextBtn.className = 'pag-btn';
        nextBtn.type = 'button';
        nextBtn.textContent = '>';
        nextBtn.disabled = state.pagina >= state.paginas;
        nextBtn.setAttribute('data-page', state.pagina + 1);
        $pagNav.appendChild(nextBtn);
    }

    /* ==================== FETCH ==================== */
    function fetchEvents() {
        if (state.loading) return;
        state.loading = true;
        renderLoading();

        var url = API_URL + '&' + buildQueryString();

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function(data) {
            state.loading = false;
            state.eventos = data.eventos || [];
            state.total   = data.total || 0;
            state.pagina  = data.pagina || 1;
            state.paginas = data.paginas || 0;

            renderEvents(state.eventos);
            renderPagination();
        })
        .catch(function(err) {
            state.loading = false;
            $body.innerHTML = '';
            var div = document.createElement('div');
            div.className = 'console-status';
            var icon = document.createElement('i');
            icon.className = 'bi bi-exclamation-triangle';
            icon.style.color = 'var(--color-error)';
            div.appendChild(icon);
            div.appendChild(document.createTextNode(' Error al cargar: ' + err.message));
            $body.appendChild(div);
            $pagInfo.textContent = 'Error';
        });
    }

    /* ==================== AUTO-REFRESH ==================== */
    function startAutoRefresh() {
        stopAutoRefresh();
        state.autoRefresh = true;
        state.refreshTimer = setInterval(fetchEvents, REFRESH_MS);
        $btnAuto.classList.add('console-btn--active');
        $liveDot.classList.add('pag-live-dot--on');
        $liveLabel.textContent = 'LIVE';
    }

    function stopAutoRefresh() {
        state.autoRefresh = false;
        if (state.refreshTimer) {
            clearInterval(state.refreshTimer);
            state.refreshTimer = null;
        }
        $btnAuto.classList.remove('console-btn--active');
        $liveDot.classList.remove('pag-live-dot--on');
        $liveLabel.textContent = 'PAUSED';
    }

    function toggleAutoRefresh() {
        if (state.autoRefresh) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    }

    /* ==================== EVENT HANDLERS ==================== */

    /* Toggle expanded detail on line click */
    $body.addEventListener('click', function(e) {
        /* Copy button */
        var copyTarget = e.target.closest('.copy-btn');
        if (copyTarget) {
            e.stopPropagation();
            var json = copyTarget.getAttribute('data-event-json');
            if (json) {
                try {
                    var parsed = JSON.parse(json);
                    var text = JSON.stringify(parsed, null, 2);
                    navigator.clipboard.writeText(text).then(function() {
                        var origText = copyTarget.textContent;
                        copyTarget.textContent = '';
                        var checkIcon = document.createElement('i');
                        checkIcon.className = 'bi bi-check2';
                        copyTarget.appendChild(checkIcon);
                        copyTarget.appendChild(document.createTextNode(' Copiado'));
                        setTimeout(function() {
                            copyTarget.textContent = '';
                            var clipIcon = document.createElement('i');
                            clipIcon.className = 'bi bi-clipboard';
                            copyTarget.appendChild(clipIcon);
                            copyTarget.appendChild(document.createTextNode(' Copiar'));
                        }, 1500);
                    });
                } catch(_) {}
            }
            return;
        }

        /* Expand/collapse log line */
        var line = e.target.closest('.log-line');
        if (!line) return;

        var eventId = line.getAttribute('data-event-id');
        if (!eventId) return;

        var detail = document.getElementById('detail-' + eventId);
        if (!detail) return;

        var isOpen = detail.classList.contains('open');

        /* Close any previously open detail */
        var allOpen = $body.querySelectorAll('.log-detail.open');
        for (var i = 0; i < allOpen.length; i++) {
            allOpen[i].classList.remove('open');
            var prevLine = allOpen[i].previousElementSibling;
            if (prevLine) {
                prevLine.classList.remove('log-line--expanded');
                prevLine.setAttribute('aria-expanded', 'false');
            }
        }

        if (!isOpen) {
            detail.classList.add('open');
            line.classList.add('log-line--expanded');
            line.setAttribute('aria-expanded', 'true');
            state.expandedId = eventId;
        } else {
            state.expandedId = null;
        }
    });

    /* Keyboard: Enter to toggle expand */
    $body.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var line = e.target.closest('.log-line');
            if (line) line.click();
        }
    });

    /* Auto-refresh toggle */
    $btnAuto.addEventListener('click', toggleAutoRefresh);

    /* Manual refresh */
    $btnRefresh.addEventListener('click', function() {
        fetchEvents();
    });

    /* Clear console visually */
    $btnClear.addEventListener('click', function() {
        $body.innerHTML = '';
        var div = document.createElement('div');
        div.className = 'console-status';
        var icon = document.createElement('i');
        icon.className = 'bi bi-terminal';
        div.appendChild(icon);
        div.appendChild(document.createTextNode(' Consola limpiada — esperando nuevos eventos'));
        $body.appendChild(div);
        $pagInfo.textContent = '0 eventos';
        $pagNav.innerHTML = '';
    });

    /* Apply filters */
    $btnFiltrar.addEventListener('click', function() {
        state.pagina = 1;
        fetchEvents();
    });

    /* Enter key in search field triggers filter */
    $fSearch.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            state.pagina = 1;
            fetchEvents();
        }
    });

    /* Clear all filters */
    $btnLimpiar.addEventListener('click', function() {
        $fNivel.value = '';
        $fCategoria.value = '';
        $fModulo.value = '';
        $fSearch.value = '';
        $fDesde.value = '';
        $fHasta.value = '';
        state.pagina = 1;
        fetchEvents();
    });

    /* Pagination clicks (delegated) */
    $pagNav.addEventListener('click', function(e) {
        var btn = e.target.closest('.pag-btn');
        if (!btn || btn.disabled) return;
        var page = parseInt(btn.getAttribute('data-page'), 10);
        if (isNaN(page) || page < 1 || page > state.paginas) return;
        state.pagina = page;
        fetchEvents();
        $body.scrollTop = 0;
    });

    /* ==================== INIT ==================== */
    fetchEvents();
    startAutoRefresh();
})();
</script>
</body>
</html>