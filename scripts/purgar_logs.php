<?php
/**
 * REMTYS - Runner de retencion para tablas append-only.
 *
 * Invoca las funciones SQL instaladas por la migracion 012:
 *   - purgar_system_events(p_dias_info, p_dias_warning, p_dias_error)
 *   - purgar_rate_limits_inactivos(p_dias_sin_actividad)
 *
 * Uso (cron, docker entrypoint, scheduler externo):
 *   php scripts/purgar_logs.php                          # valores por defecto
 *   php scripts/purgar_logs.php --info=60 --warn=120     # sobrescribir ventanas
 *   php scripts/purgar_logs.php --dry-run                # solo reportar volumenes
 *
 * Salida: JSON en stdout con filas eliminadas por tier + timing total.
 * Codigo de salida: 0 exito | 1 error recuperable (se registra en log) | 2 error fatal.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/audit.php';

function argFlag(array $argv, string $nombre, int $defecto): int {
    foreach ($argv as $a) {
        if (preg_match('/^--' . preg_quote($nombre, '/') . '=(\d+)$/', $a, $m)) {
            return (int)$m[1];
        }
    }
    return $defecto;
}

function hasFlag(array $argv, string $nombre): bool {
    foreach ($argv as $a) {
        if ($a === '--' . $nombre) return true;
    }
    return false;
}

$argv = $_SERVER['argv'] ?? [];
$dryRun      = hasFlag($argv, 'dry-run');
$diasInfo    = argFlag($argv, 'info', 90);
$diasWarning = argFlag($argv, 'warn', 180);
$diasError   = argFlag($argv, 'error', 365);
$diasRate    = argFlag($argv, 'rate', 7);

$t0 = microtime(true);

try {
    $db = getDBConnection();
} catch (Throwable $e) {
    fwrite(STDERR, '[purgar_logs] No se pudo conectar a BD: ' . $e->getMessage() . PHP_EOL);
    exit(2);
}

$resultado = [
    'inicio'     => date('c'),
    'dry_run'    => $dryRun,
    'parametros' => [
        'dias_info'    => $diasInfo,
        'dias_warning' => $diasWarning,
        'dias_error'   => $diasError,
        'dias_rate'    => $diasRate,
    ],
    'purga_system_events' => [],
    'rate_limits_purgados' => 0,
    'volumen_actual'       => [],
];

try {
    if ($dryRun) {
        // Solo reportar volumen que SERIA eliminado, sin DELETE.
        $sql = "
            SELECT
                SUM(CASE WHEN nivel IN ('INFO','SUCCESS') AND created_at < NOW() - (:di || ' days')::INTERVAL THEN 1 ELSE 0 END) AS info,
                SUM(CASE WHEN nivel = 'WARNING' AND created_at < NOW() - (:dw || ' days')::INTERVAL THEN 1 ELSE 0 END) AS warning,
                SUM(CASE WHEN nivel IN ('ERROR','CRITICAL') AND created_at < NOW() - (:de || ' days')::INTERVAL THEN 1 ELSE 0 END) AS errorcrit,
                COUNT(*) AS total_tabla
            FROM system_events
        ";
        $st = $db->prepare($sql);
        $st->execute([':di' => $diasInfo, ':dw' => $diasWarning, ':de' => $diasError]);
        $resultado['purga_system_events'] = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $st = $db->prepare("
            SELECT COUNT(*) FROM rate_limits
            WHERE ultimo_intento < NOW() - (:dr || ' days')::INTERVAL
              AND (bloqueado_hasta IS NULL OR bloqueado_hasta < NOW())
        ");
        $st->execute([':dr' => $diasRate]);
        $resultado['rate_limits_purgados'] = (int)$st->fetchColumn();
    } else {
        // Ejecutar purga real.
        $st = $db->prepare('SELECT * FROM purgar_system_events(:di, :dw, :de)');
        $st->execute([':di' => $diasInfo, ':dw' => $diasWarning, ':de' => $diasError]);
        $resultado['purga_system_events'] = $st->fetchAll(PDO::FETCH_ASSOC);

        $st = $db->prepare('SELECT purgar_rate_limits_inactivos(:dr)');
        $st->execute([':dr' => $diasRate]);
        $resultado['rate_limits_purgados'] = (int)$st->fetchColumn();

        // Registrar en system_events como evento de mantenimiento (recursivo controlado:
        // una sola linea de mantenimiento por invocacion).
        auditLog('INFO', 'SISTEMA', 'RETENCION_LOGS_APLICADA', [
            'purga_system_events'  => $resultado['purga_system_events'],
            'rate_limits_purgados' => $resultado['rate_limits_purgados'],
            'parametros'           => $resultado['parametros'],
        ]);
    }

    // Volumen vigente para observabilidad.
    $st = $db->query("
        SELECT nivel, COUNT(*) AS total
        FROM system_events
        GROUP BY nivel
        ORDER BY nivel
    ");
    $resultado['volumen_actual'] = $st->fetchAll(PDO::FETCH_ASSOC);

    $resultado['duracion_ms'] = (int)round((microtime(true) - $t0) * 1000);
    $resultado['fin']         = date('c');

    fwrite(STDOUT, json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, sprintf(
        "[purgar_logs] Error: %s (%s:%d)\n",
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    ));
    // Registrar el fallo para no perderlo en caso de cron silencioso.
    try {
        auditLog('ERROR', 'SISTEMA', 'RETENCION_LOGS_FALLO', [
            'mensaje' => substr($e->getMessage(), 0, 300),
            'archivo' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
    } catch (Throwable $ignored) {}
    exit(1);
}
