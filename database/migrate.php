<?php
/**
 * REMTYS — Migration runner (PHP, zero dependencies).
 *
 * Usage:
 *   php database/migrate.php status       # show applied vs pending
 *   php database/migrate.php up           # apply all pending migrations
 *   php database/migrate.php up <NNN>     # apply up to migration NNN
 *   php database/migrate.php verify       # compare filesystem vs schema_migrations
 *
 * Design:
 *   - Tabla schema_migrations(version TEXT PK, filename, applied_at, checksum, duration_ms).
 *   - Cada migración corre en una transacción; si falla, ROLLBACK y abort.
 *   - Checksum SHA-256 del contenido registrado; si una migración ya aplicada cambió en disco
 *     el comando `verify` lo reporta.
 *   - Convención de nombre: NNN_<slug>.sql (NNN = 3 dígitos, estrictamente incremental).
 *   - NO soporta down/rollback por diseño: migraciones son forward-only; para revertir,
 *     crear una nueva migración que compense. Esto es intencional en BD de gobierno.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

const MIG_DIR = __DIR__ . '/migrations';

function miglog(string $msg): void
{
    fwrite(STDOUT, '[migrate] ' . $msg . PHP_EOL);
}

function migerr(string $msg): void
{
    fwrite(STDERR, '[migrate][ERROR] ' . $msg . PHP_EOL);
}

function ensureMigrationsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            version      TEXT PRIMARY KEY,
            filename     TEXT NOT NULL,
            checksum     TEXT NOT NULL,
            applied_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            duration_ms  INTEGER NOT NULL DEFAULT 0,
            applied_by   TEXT
        )
    ");
}

/** @return array<int,array{version:string,filename:string,path:string,checksum:string}> */
function discoverMigrations(): array
{
    $files = glob(MIG_DIR . '/*.sql') ?: [];
    sort($files, SORT_STRING);
    $out = [];
    foreach ($files as $path) {
        $base = basename($path);
        if (!preg_match('/^(\d{3})_([A-Za-z0-9_\-]+)\.sql$/', $base, $m)) {
            migerr("File skipped (bad name): {$base}. Expected NNN_slug.sql");
            continue;
        }
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Cannot read {$path}");
        }
        $out[] = [
            'version'  => $m[1],
            'filename' => $base,
            'path'     => $path,
            'checksum' => hash('sha256', $content),
        ];
    }
    // detect duplicate versions
    $seen = [];
    foreach ($out as $m) {
        if (isset($seen[$m['version']])) {
            throw new RuntimeException("Duplicate migration version {$m['version']}: "
                . "{$seen[$m['version']]} and {$m['filename']}");
        }
        $seen[$m['version']] = $m['filename'];
    }
    return $out;
}

/** @return array<string,array{version:string,filename:string,checksum:string,applied_at:string}> */
function appliedMigrations(PDO $db): array
{
    $stmt = $db->query('SELECT version, filename, checksum, applied_at FROM schema_migrations ORDER BY version');
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['version']] = $row;
    }
    return $out;
}

function cmdStatus(PDO $db): int
{
    ensureMigrationsTable($db);
    $disk    = discoverMigrations();
    $applied = appliedMigrations($db);

    printf("%-8s %-12s %-45s %s\n", 'VERSION', 'STATUS', 'FILENAME', 'APPLIED_AT');
    echo str_repeat('-', 90) . PHP_EOL;

    foreach ($disk as $m) {
        $v = $m['version'];
        if (isset($applied[$v])) {
            $drift = $applied[$v]['checksum'] !== $m['checksum'] ? ' [CHECKSUM_DRIFT!]' : '';
            printf("%-8s %-12s %-45s %s%s\n", $v, 'applied', $m['filename'], $applied[$v]['applied_at'], $drift);
        } else {
            printf("%-8s %-12s %-45s %s\n", $v, 'PENDING', $m['filename'], '-');
        }
    }
    // migrations in DB but missing from disk
    foreach ($applied as $v => $a) {
        $onDisk = array_filter($disk, fn($d) => $d['version'] === $v);
        if (!$onDisk) {
            printf("%-8s %-12s %-45s %s\n", $v, 'MISSING_FILE', $a['filename'], $a['applied_at']);
        }
    }
    return 0;
}

function cmdVerify(PDO $db): int
{
    ensureMigrationsTable($db);
    $disk    = discoverMigrations();
    $applied = appliedMigrations($db);
    $issues  = 0;
    foreach ($disk as $m) {
        if (isset($applied[$m['version']]) && $applied[$m['version']]['checksum'] !== $m['checksum']) {
            migerr("Checksum drift on {$m['version']} ({$m['filename']}). "
                . "Create a new migration to compensate; DO NOT edit applied migrations.");
            $issues++;
        }
    }
    foreach ($applied as $v => $a) {
        $onDisk = array_filter($disk, fn($d) => $d['version'] === $v);
        if (!$onDisk) {
            migerr("Applied migration {$v} is missing on disk ({$a['filename']}).");
            $issues++;
        }
    }
    miglog($issues === 0 ? 'verify: OK' : "verify: {$issues} issue(s) found");
    return $issues === 0 ? 0 : 1;
}

function cmdUp(PDO $db, ?string $upTo = null): int
{
    ensureMigrationsTable($db);
    $disk    = discoverMigrations();
    $applied = appliedMigrations($db);
    $ran     = 0;
    foreach ($disk as $m) {
        if (isset($applied[$m['version']])) {
            continue;
        }
        if ($upTo !== null && strcmp($m['version'], $upTo) > 0) {
            break;
        }
        miglog("Applying {$m['version']} {$m['filename']} ...");
        $sql = file_get_contents($m['path']);
        if ($sql === false || trim($sql) === '') {
            migerr("Empty or unreadable: {$m['filename']}");
            return 2;
        }
        $start = microtime(true);
        try {
            // Algunas migraciones ya traen BEGIN/COMMIT. El runner no anida transacciones:
            // si el SQL trae BEGIN se respeta; si no, lo envolvemos.
            $hasOwnTx = (bool)preg_match('/^\s*BEGIN\b/im', $sql);
            if (!$hasOwnTx) {
                $db->beginTransaction();
            }
            $db->exec($sql);
            if (!$hasOwnTx && $db->inTransaction()) {
                $db->commit();
            }
            $ms = (int)round((microtime(true) - $start) * 1000);
            $ins = $db->prepare('INSERT INTO schema_migrations (version, filename, checksum, duration_ms, applied_by)
                                 VALUES (:v,:f,:c,:d,:u)');
            $ins->execute([
                ':v' => $m['version'],
                ':f' => $m['filename'],
                ':c' => $m['checksum'],
                ':d' => $ms,
                ':u' => get_current_user(),
            ]);
            miglog("Applied {$m['version']} in {$ms} ms");
            $ran++;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            migerr("Failed {$m['version']} {$m['filename']}: " . $e->getMessage());
            return 3;
        }
    }
    miglog("Done. {$ran} migration(s) applied.");
    return 0;
}

function cmdBaseline(PDO $db): int
{
    // Marca todas las migraciones presentes en disco como ya aplicadas, SIN ejecutarlas.
    // Útil para sistemas existentes que nunca usaron el runner. Se debe usar una sola vez.
    ensureMigrationsTable($db);
    $disk = discoverMigrations();
    $applied = appliedMigrations($db);
    $n = 0;
    $ins = $db->prepare('INSERT INTO schema_migrations (version, filename, checksum, duration_ms, applied_by)
                         VALUES (:v,:f,:c,0,:u) ON CONFLICT (version) DO NOTHING');
    foreach ($disk as $m) {
        if (isset($applied[$m['version']])) {
            continue;
        }
        $ins->execute([
            ':v' => $m['version'],
            ':f' => $m['filename'],
            ':c' => $m['checksum'],
            ':u' => 'baseline',
        ]);
        $n++;
        miglog("Baselined {$m['version']} {$m['filename']}");
    }
    miglog("Baseline done. {$n} migration(s) marked as applied.");
    return 0;
}

// ---------------------- CLI entry ----------------------

$cmd = $argv[1] ?? 'status';
$arg = $argv[2] ?? null;

try {
    $db = getDBConnection();
    switch ($cmd) {
        case 'status':   exit(cmdStatus($db));
        case 'verify':   exit(cmdVerify($db));
        case 'up':       exit(cmdUp($db, $arg));
        case 'baseline': exit(cmdBaseline($db));
        default:
            migerr("Unknown command: {$cmd}");
            fwrite(STDERR, "Usage: php database/migrate.php {status|up|verify|baseline} [up-to]\n");
            exit(64);
    }
} catch (Throwable $e) {
    migerr($e->getMessage());
    exit(1);
}
