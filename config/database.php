<?php
/**
 * Configuración de base de datos PostgreSQL
 * Remtys App — Gestión Predial y Fiscal Municipal
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'remtys2_db');
define('DB_USER', getenv('DB_USER') ?: 'remtys_app');
$_dbPass = getenv('DB_PASS');
if ($_dbPass === false || $_dbPass === '') {
    $appEnv = getenv('APP_ENV') ?: 'local';
    if (!in_array($appEnv, ['local', 'development', 'docker'], true)) {
        error_log('[REMTYS] CRITICAL: DB_PASS not set in environment.');
        http_response_code(503);
        die('Error de configuración del servidor.');
    }
    // Only allow fallback in explicit local/development environments
    $_dbPass = 'admin';
}
define('DB_PASS', $_dbPass);
unset($_dbPass);

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                DB_HOST, DB_PORT, DB_NAME
            );
            $sslmode = getenv('DB_SSLMODE') ?: '';
            if ($sslmode !== '') {
                $dsn .= ';sslmode=' . $sslmode;
            }
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET client_encoding TO 'UTF8'");
            $pdo->exec("SET timezone TO 'America/Mexico_City'");
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            die(json_encode(['error' => 'Error de conexión a base de datos. Verifique la configuración.']));
        }
    }
    return $pdo;
}
