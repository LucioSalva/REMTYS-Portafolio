<?php
/**
 * AuditService — Auditoría forense de cambios en entidades sensibles.
 *
 * Tablas consumidas (ver migration 010_p0_critical_hardening.sql):
 *   - audit_cambios          (INSERT/UPDATE/DELETE lógico con old/new/diff)
 *   - audit_eliminaciones    (tombstones de DELETE físico)
 *   - audit_vista_documento  (quién vio/descargó cada documento)
 *
 * Filosofía: append-only, nunca UPDATE/DELETE sobre estas tablas.
 * Todos los writes capturan contexto de request (usuario, rol, IP, UA, correlation_id).
 */

declare(strict_types=1);

final class AuditService
{
    private PDO $db;

    /** Columnas que se enmascaran aunque aparezcan en old_row/new_row. */
    private const CAMPOS_SENSIBLES = [
        'password', 'password_hash', 'contrasena', 'contrasena_hash',
        'token', 'token_hash', 'csrf_token', 'reset_token',
        'secret', 'api_key', 'db_pass',
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra un cambio lógico (INSERT/UPDATE/DELETE) con old/new snapshots.
     *
     * @param string      $tabla      Nombre de la tabla auditada (ej. 'usuarios')
     * @param string|int  $registroId PK del registro afectado (se serializa a texto)
     * @param string      $operacion  'INSERT' | 'UPDATE' | 'DELETE'
     * @param array|null  $oldRow     Snapshot antes del cambio (null para INSERT)
     * @param array|null  $newRow     Snapshot después del cambio (null para DELETE)
     * @param string|null $motivo     Justificación (requerida en DELETEs manuales)
     * @return int ID del registro en audit_cambios
     */
    public function registrarCambio(
        string $tabla,
        $registroId,
        string $operacion,
        ?array $oldRow,
        ?array $newRow,
        ?string $motivo = null
    ): int {
        $operacion = strtoupper($operacion);
        if (!in_array($operacion, ['INSERT', 'UPDATE', 'DELETE'], true)) {
            throw new InvalidArgumentException("operacion inválida: {$operacion}");
        }

        $old = $oldRow !== null ? $this->sanitizar($oldRow) : null;
        $new = $newRow !== null ? $this->sanitizar($newRow) : null;
        $diff = $this->calcularDiff($old, $new);

        $ctx = $this->contextoRequest();

        $stmt = $this->db->prepare('
            INSERT INTO audit_cambios
                (tabla, registro_id, operacion, old_row, new_row, campos_diff,
                 usuario_id, usuario_email, rol, ip, user_agent, correlation_id, motivo)
            VALUES
                (:tabla, :registro_id, :operacion,
                 CAST(:old_row AS JSONB), CAST(:new_row AS JSONB), CAST(:diff AS JSONB),
                 :usuario_id, :usuario_email, :rol, :ip, :user_agent, :correlation_id, :motivo)
            RETURNING id
        ');
        $stmt->execute([
            ':tabla'          => $tabla,
            ':registro_id'    => (string)$registroId,
            ':operacion'      => $operacion,
            ':old_row'        => $old !== null ? $this->toJson($old)  : null,
            ':new_row'        => $new !== null ? $this->toJson($new)  : null,
            ':diff'           => $diff !== null ? $this->toJson($diff) : null,
            ':usuario_id'     => $ctx['usuario_id'],
            ':usuario_email'  => $ctx['usuario_email'],
            ':rol'            => $ctx['rol'],
            ':ip'             => $ctx['ip'],
            ':user_agent'     => $ctx['user_agent'],
            ':correlation_id' => $ctx['correlation_id'],
            ':motivo'         => $motivo,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /** Atajo INSERT. */
    public function registrarInsert(string $tabla, $registroId, array $newRow, ?string $motivo = null): int
    {
        return $this->registrarCambio($tabla, $registroId, 'INSERT', null, $newRow, $motivo);
    }

    /** Atajo UPDATE. */
    public function registrarUpdate(string $tabla, $registroId, array $oldRow, array $newRow, ?string $motivo = null): int
    {
        return $this->registrarCambio($tabla, $registroId, 'UPDATE', $oldRow, $newRow, $motivo);
    }

    /** Atajo DELETE lógico (soft delete). El DELETE físico va por registrarEliminacion(). */
    public function registrarDelete(string $tabla, $registroId, array $oldRow, string $motivo): int
    {
        return $this->registrarCambio($tabla, $registroId, 'DELETE', $oldRow, null, $motivo);
    }

    /**
     * Registra tombstone para DELETE físico: fila completa como JSON en audit_eliminaciones.
     * Usar solo cuando la fila se elimina realmente de la tabla origen.
     */
    public function registrarEliminacion(
        string $tabla,
        $registroId,
        array $filaCompleta,
        string $motivo
    ): int {
        $ctx = $this->contextoRequest();
        $fila = $this->sanitizar($filaCompleta);

        $stmt = $this->db->prepare('
            INSERT INTO audit_eliminaciones
                (tabla, registro_id, fila_jsonb, usuario_id, motivo, correlation_id)
            VALUES
                (:tabla, :registro_id, CAST(:fila AS JSONB), :usuario_id, :motivo, :correlation_id)
            RETURNING id
        ');
        $stmt->execute([
            ':tabla'          => $tabla,
            ':registro_id'    => (string)$registroId,
            ':fila'           => $this->toJson($fila),
            ':usuario_id'     => $ctx['usuario_id'],
            ':motivo'         => $motivo,
            ':correlation_id' => $ctx['correlation_id'],
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Registra una vista/descarga/denegación de un documento.
     *
     * @param int         $documentoId
     * @param string      $disposition 'inline' | 'attachment' | 'denied' | 'thumbnail'
     * @param string|null $motivoDenegacion Solo para disposition='denied'
     * @param int|null    $tamanoBytes      Bytes servidos (si aplica)
     * @return int ID del registro en audit_vista_documento
     */
    public function registrarVistaDocumento(
        int $documentoId,
        string $disposition,
        ?string $motivoDenegacion = null,
        ?int $tamanoBytes = null
    ): int {
        if (!in_array($disposition, ['inline', 'attachment', 'denied', 'thumbnail'], true)) {
            throw new InvalidArgumentException("disposition inválida: {$disposition}");
        }

        $ctx = $this->contextoRequest();
        $contribuyenteId = $_SESSION['contribuyente_id'] ?? null;

        $stmt = $this->db->prepare('
            INSERT INTO audit_vista_documento
                (documento_id, usuario_id, rol, contribuyente_id, ip, user_agent,
                 disposition, motivo_denegacion, tamano_bytes, correlation_id)
            VALUES
                (:documento_id, :usuario_id, :rol, :contribuyente_id, :ip, :user_agent,
                 :disposition, :motivo_denegacion, :tamano_bytes, :correlation_id)
            RETURNING id
        ');
        $stmt->execute([
            ':documento_id'     => $documentoId,
            ':usuario_id'       => $ctx['usuario_id'],
            ':rol'              => $ctx['rol'],
            ':contribuyente_id' => $contribuyenteId,
            ':ip'               => $ctx['ip'],
            ':user_agent'       => $ctx['user_agent'],
            ':disposition'      => $disposition,
            ':motivo_denegacion'=> $motivoDenegacion,
            ':tamano_bytes'     => $tamanoBytes,
            ':correlation_id'   => $ctx['correlation_id'],
        ]);

        return (int)$stmt->fetchColumn();
    }

    // ─── Helpers privados ──────────────────────────────────────────────────

    /**
     * @return array{usuario_id:?int,usuario_email:?string,rol:?string,ip:?string,user_agent:?string,correlation_id:string}
     */
    private function contextoRequest(): array
    {
        return [
            'usuario_id'     => isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null,
            'usuario_email'  => $_SESSION['usuario_email'] ?? null,
            'rol'            => $_SESSION['usuario_rol'] ?? null,
            'ip'             => $this->clienteIp(),
            'user_agent'     => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255)
                : null,
            'correlation_id' => function_exists('correlationId') ? correlationId() : null,
        ];
    }

    private function clienteIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($ip) || $ip === '') {
            return null;
        }
        // Acotado a 45 (IPv6 max) por la columna.
        return substr($ip, 0, 45);
    }

    /**
     * Remueve/enmascara campos sensibles antes de persistir en JSONB.
     */
    private function sanitizar(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $kl = strtolower((string)$k);
            if (in_array($kl, self::CAMPOS_SENSIBLES, true)) {
                $out[$k] = '[REDACTED]';
                continue;
            }
            // Truncar strings muy largas para no inflar el JSONB.
            if (is_string($v) && strlen($v) > 2000) {
                $out[$k] = substr($v, 0, 2000) . '...[TRUNCATED]';
                continue;
            }
            $out[$k] = $v;
        }
        return $out;
    }

    /**
     * Calcula diff por llave entre old y new.
     * Para INSERT: diff = new. Para DELETE: diff = old. Para UPDATE: solo llaves distintas.
     *
     * @return array<string,array{antes:mixed,despues:mixed}>|null
     */
    private function calcularDiff(?array $old, ?array $new): ?array
    {
        if ($old === null && $new === null) {
            return null;
        }
        if ($old === null) {
            // INSERT — todas las columnas son "nuevas"
            $d = [];
            foreach ($new as $k => $v) {
                $d[$k] = ['antes' => null, 'despues' => $v];
            }
            return $d;
        }
        if ($new === null) {
            // DELETE lógico — todas las columnas son "antes"
            $d = [];
            foreach ($old as $k => $v) {
                $d[$k] = ['antes' => $v, 'despues' => null];
            }
            return $d;
        }

        // UPDATE — solo las llaves que cambiaron
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $d = [];
        foreach ($keys as $k) {
            $a = $old[$k] ?? null;
            $b = $new[$k] ?? null;
            if ($a !== $b) {
                $d[$k] = ['antes' => $a, 'despues' => $b];
            }
        }
        return $d === [] ? null : $d;
    }

    private function toJson($v): string
    {
        $json = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $json === false ? '{}' : $json;
    }
}
